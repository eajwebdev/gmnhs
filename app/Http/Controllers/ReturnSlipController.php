<?php

namespace App\Http\Controllers;

use App\Models\Accountable;
use App\Models\School;
use App\Models\EnduserProperty;
use App\Models\Log;
use App\Models\Office;
use App\Models\ReturnSlip;
use App\Models\ReturnSlipItem;
use App\Models\ReturnSlipLog;
use App\Models\Setting;
use App\Services\Reports\IirupExcelBuilder;
use App\Services\Reports\PropertyReturnSlipExcelBuilder;
use App\Services\Reports\PropertyTransferReportExcelBuilder;
use Barryvdh\Snappy\Facades\SnappyPdf as Snappy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReturnSlipController extends Controller
{
    /**
     * Condition values a property can carry once it is released back out of Supply.
     */
    public const CONDITIONS = [
        'Good Condition',
        'Needing Repair',
        'Obsolete',
        'Not used since purchase',
        'Damaged',
        'No Longer Needed',
    ];

    public const STATUS_RETURNED = 'Returned';
    public const STATUS_TRANSFERRED = 'Transferred';
    public const STATUS_UNSERVICEABLE = 'Unserviceable';
    public const STATUS_OBSOLETE = 'Obsolete';
    public const STATUS_CANCELLED = 'Cancelled';

    /**
     * accountable.accnt_role values. GMNHS now uses ordinary end users and office
     * heads only; the legacy custodian value is kept only for old records.
     */
    public const ROLE_STAFF = 0;
    public const ROLE_OFFICE_HEAD = 1;
    public const ROLE_CUSTODIAN = 2;

    /**
     * Transfer types printed on the Property Transfer Report (GAM Appendix 76),
     * exactly as they appear on the paper form.
     */
    public const TRANSFER_TYPES = ['Donation', 'Relocate', 'Reassignment', 'Others'];

    /**
     * Signatories pre-printed on public/Supply-Form-11-Property-Transfer-Report.xlsx.
     */
    public const PTR_APPROVED_BY = ['name' => 'BENJAMIN R. DELA TORRE', 'designation' => 'AOV/Supply Officer designate'];
    public const PTR_SUPPLY_OFFICER = ['name' => 'GABRIEL N. SORIANO', 'designation' => 'AOIII/Supply Officer II'];
    public const PTR_PROPERTY_CUSTODIAN = ['name' => 'HANNAH C. VALDEZ', 'designation' => 'ADAS II/Property Custodian'];

    /**
     * Who the report may name under RELEASED/ISSUED BY. Leaving the form's
     * select blank keeps the default: the end user who gave the property up.
     */
    public const PTR_RELEASED_BY_OPTIONS = [
        'property_custodian' => self::PTR_PROPERTY_CUSTODIAN,
        'supply_officer' => self::PTR_SUPPLY_OFFICER,
    ];

    /**
     * Signatories and instructions pre-printed on
     * public/Supply-Form-10-PROPERTY-RETURN-SLIP-REVISED (for DRAR).xlsx.
     */
    public const PRS_RECEIVED_BY = ['name' => 'HANNAH C. VALDEZ', 'designation' => 'ADAS II/Property Custodian'];
    public const PRS_RECORDED_BY = ['name' => 'GABRIEL N. SORIANO', 'designation' => 'AOIII/Supply Officer II'];
    public const PRS_APPROVED_BY = ['name' => 'BENJAMIN R. DELA TORRE', 'designation' => 'AOV/Supply Officer designate'];

    public const PRS_INSTRUCTIONS = [
        'This form shall be prepared in six (6) copies.',
        'Please attach two (2) copies of ARE/PAR for each item.',
        'Please use separate RS form for unserviceable Motor Vehicle(s).',
    ];

    /**
     * Lines the blank return slip provides before it spills onto a second page.
     */
    public const PRS_ROWS_PER_PAGE = 18;

    /**
     * Administrators and Supply Officers hold the property once it is returned, so
     * they are the only roles allowed to transfer it out or tag it unserviceable.
     */
    private function isSupply(): bool
    {
        return in_array(auth()->user()->role, ['Administrator', 'Supply Officer']);
    }

    public function index()
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        $user = auth()->user();

        // The module lists one row per returned item so every item carries its own
        // Transfer / Unserviceable button.
        $returns = $this->returnedItemQuery()
            ->when(!$this->isSupply(), function ($query) use ($user) {
                return $query->where('return_slips.user_id', $user->id);
            })
            ->orderBy('return_slip_items.id', 'desc')
            ->get();

        $awaitingCount = $returns->filter(function ($row) {
            return in_array($row->item_status, [self::STATUS_RETURNED, 'Pending', ''], true) || $row->item_status === null;
        })->count();
        $transferredCount = $returns->where('item_status', self::STATUS_TRANSFERRED)->count();
        $unserviceableCount = $returns->where('item_status', self::STATUS_UNSERVICEABLE)->count();
        $obsoleteCount = $returns->where('item_status', self::STATUS_OBSOLETE)->count();

        $endusers = $this->enduserOptions();
        $offices = Office::orderBy('office_name')->get();
        $conditions = self::CONDITIONS;

        // Everything the "New Return" modal needs. The property list itself is
        // fetched on demand through propertyOptions() so the page stays light.
        $defaultEnduser = $this->currentUserAsEnduser();
        $canChooseEnduser = $this->isSupply();
        $canRecordReturn = $canChooseEnduser || $defaultEnduser !== null;
        $enduserBlockedMessage = $canRecordReturn ? null : $this->missingEnduserMessage();

        // The property list is loaded through propertyOptions() once an end user is
        // chosen, since it is scoped to that person's accountability (or their whole
        // school) and reports its own count from there.
        //
        // A custodian picking for themselves needs their id up front so the picker
        // can scope its first search without them choosing a name.
        $fixedEnduserId = $canChooseEnduser ? null : optional($defaultEnduser)->id;

        return view('return_slips.index', compact(
            'setting',
            'returns',
            'awaitingCount',
            'transferredCount',
            'unserviceableCount',
            'obsoleteCount',
            'endusers',
            'offices',
            'conditions',
            'defaultEnduser',
            'canChooseEnduser',
            'canRecordReturn',
            'enduserBlockedMessage',
            'fixedEnduserId'
        ));
    }

    /**
     * Returns are recorded from a modal on the list, so the old standalone create
     * page just sends people to the list.
     */
    public function create()
    {
        return redirect()->route('returnSlips.index');
    }

    /**
     * Select2 remote source for the property picker, scoped to whoever is handing
     * the property in: an end user's own items, or every item in a custodian's
     * school. The list is searched and paged because a school can hold hundreds.
     */
    public function propertyOptions(Request $request)
    {
        $term = trim((string) $request->input('q'));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 30;

        $enduser = $this->returnEnduser($request->input('enduser_id'));

        // Nothing is returnable until the modal knows whose property it is.
        if (!$enduser) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false],
                'total' => 0,
                'scope' => null,
            ]);
        }

        $query = $this->returnablePropertiesQuery($enduser);
        $total = (clone $query)->count('enduser_property.id');

        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('enduser_property.property_no_generated', 'like', $like)
                    ->orWhere('enduser_property.item_descrip', 'like', $like)
                    ->orWhere('items.item_name', 'like', $like)
                    ->orWhere('enduser_property.serial_number', 'like', $like)
                    ->orWhere('enduser_property.item_model', 'like', $like)
                    ->orWhere('offices.office_name', 'like', $like)
                    ->orWhere('accountables.person_accnt', 'like', $like)
                    ->orWhere('endusers.person_accnt', 'like', $like);
            });
        }

        $rows = $query->orderBy('items.item_name')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get();

        $hasMore = $rows->count() > $perPage;

        return response()->json([
            'results' => $rows->take($perPage)->map(function ($row) {
                $descrip = trim((string) $row->item_descrip);
                $descrip = ($descrip === '' || strcasecmp($descrip, 'n/a') === 0)
                    ? $row->item_name
                    : $descrip;

                $propertyNo = $row->property_no_generated ?: 'No property no.';

                return [
                    'id' => $row->pid,
                    'text' => $propertyNo.' — '.$descrip,
                    'property_no' => $propertyNo,
                    'descrip' => $descrip,
                    'serial_number' => $row->serial_number ?: 'N/A',
                    'office_name' => $row->office_name,
                    // Both capacities are shown, because which column carries the
                    // selected person depends on their role.
                    'accountable_name' => $row->accountable_name ?: 'Unassigned',
                    'enduser_name' => $row->enduser_name ?: 'Unassigned',
                    'condition' => $row->remarks ?: 'No Status',
                ];
            })->values(),
            'pagination' => ['more' => $hasMore],
            'total' => $total,
            'scope' => $this->returnScopeLabel($enduser, $total),
        ]);
    }

    /**
     * Resolves the person the return is being recorded under. Custodians can only
     * ever be themselves; Supply may record on anyone's behalf.
     */
    private function returnEnduser($enduserId)
    {
        if (!$this->isSupply()) {
            return $this->currentUserAsEnduser();
        }

        return $enduserId ? Accountable::find($enduserId) : null;
    }

    /**
     * Explains, in the picker, how wide the list the user is looking at is.
     */
    private function returnScopeLabel($enduser, int $total): string
    {
        $suffix = $total.' item'.($total === 1 ? '' : 's').' available';

        if ($this->isCustodian($enduser)) {
            $schoolId = $this->enduserSchoolId($enduser);
            $school = $schoolId ? optional(School::find($schoolId))->school_name : null;

            return 'Held by, or anywhere in, '.($school ?: 'the school offices').' - '.$suffix;
        }

        return 'Accountable for or assigned to '.trim((string) $enduser->person_accnt).' — '.$suffix;
    }

    /**
     * The SCHOOL an accountable person belongs to, via their office.
     */
    private function enduserSchoolId($enduser)
    {
        if (!$enduser || !$enduser->off_id) {
            return null;
        }

        $office = Office::find($enduser->off_id);

        return $office ? $office->school_id : null;
    }

    /**
     * Record a return covering one or more properties handed in together by the
     * same person. One slip per hand-over, one item row per property, and its own
     * log entry each so every property keeps an independent trail.
     */
    public function store(Request $request)
    {
        $canChooseEnduser = $this->isSupply();

        $validated = $request->validate([
            'property_ids' => 'required|array|min:1',
            'property_ids.*' => 'required|integer',
            'returned_by_id' => ($canChooseEnduser ? 'required|integer' : 'nullable|integer'),
            'reason' => 'nullable|string|max:1000',
        ], [
            'property_ids.required' => 'Select at least one property being returned.',
            'property_ids.min' => 'Select at least one property being returned.',
            'returned_by_id.required' => 'Select who returned the items.',
        ]);

        // Custodians always return under their own name; whatever the form posted
        // is discarded so the field cannot be tampered with.
        if ($canChooseEnduser) {
            $accountable = Accountable::find($validated['returned_by_id']);

            if (!$accountable) {
                return redirect()->back()->withInput()->with('error', 'The selected end user no longer exists.');
            }
        } else {
            $accountable = $this->currentUserAsEnduser();

            if (!$accountable) {
                return redirect()->back()->withInput()->with('error', $this->missingEnduserMessage());
            }
        }

        $requestedIds = array_values(array_unique(array_map('intval', $validated['property_ids'])));

        // Re-resolved against the same scope the picker used, so a tampered or
        // stale id cannot pull in property this person is not accountable for.
        $properties = $this->returnablePropertiesQuery($accountable)
            ->whereIn('enduser_property.id', $requestedIds)
            ->get();

        if ($properties->isEmpty()) {
            return redirect()->back()->withInput()->with(
                'error',
                'None of the selected properties are available for return. They may already be in an open return.'
            );
        }

        $returnedByName = $accountable->person_accnt;

        $slip = DB::transaction(function () use ($validated, $properties, $accountable, $returnedByName) {
            $user = auth()->user();

            $slip = ReturnSlip::create([
                'user_id' => $user->id,
                'requested_by' => trim($user->fname.' '.$user->lname),
                'school_id' => $user->school_id,
                'returned_by_id' => $accountable->id,
                'returned_by_name' => $returnedByName,
                'received_by' => $user->id,
                'returned_at' => now(),
                'request_type' => 'Return',
                'target_office_id' => null,
                'reason' => $validated['reason'] ?? null,
                'status' => self::STATUS_RETURNED,
            ]);

            foreach ($properties as $property) {
                $previousRemarks = $property->remarks;

                $item = ReturnSlipItem::create([
                    'return_slip_id' => $slip->id,
                    'enduser_property_id' => $property->pid,
                    'property_no_generated' => $property->property_no_generated,
                    'item_name' => $property->item_name,
                    'serial_number' => $property->serial_number,
                    'current_office_id' => $property->office_id,
                    'current_location_id' => $property->location,
                    'current_status' => $previousRemarks,
                    'previous_remarks' => $previousRemarks,
                    'status' => self::STATUS_RETURNED,
                ]);

                EnduserProperty::where('id', $property->pid)->update([
                    'remarks' => self::STATUS_RETURNED,
                    'date_return' => now()->toDateString(),
                ]);

                $this->recordLog($slip, $item, 'Returned', 'Item returned to Supply by '.($returnedByName ?: 'unspecified person').'.', [
                    'from_value' => $previousRemarks,
                    'to_value' => self::STATUS_RETURNED,
                    'remarks' => $validated['reason'] ?? null,
                ]);
            }

            return $slip;
        });

        $recorded = $properties->count();
        $skipped = count($requestedIds) - $recorded;

        $message = $recorded.' item'.($recorded === 1 ? '' : 's').' recorded as "Returned" and waiting for Supply action.';

        if ($skipped > 0) {
            $message .= ' '.$skipped.' selected item'.($skipped === 1 ? ' was' : 's were')
                .' skipped because they are no longer available for return.';
        }

        return redirect()->route('returnSlips.show', $slip->id)->with('success', $message);
    }

    public function show($id)
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        $slip = $this->slipQuery()->where('return_slips.id', $id)->firstOrFail();

        if (!$this->isSupply() && $slip->user_id !== auth()->id()) {
            abort(403);
        }

        $items = ReturnSlipItem::where('return_slip_id', $id)
            ->leftJoin('offices', 'return_slip_items.current_office_id', '=', 'offices.id')
            ->leftJoin('offices as target_offices', 'return_slip_items.transferred_to_office_id', '=', 'target_offices.id')
            ->select(
                'return_slip_items.*',
                'offices.office_name as current_office_name',
                'target_offices.office_name as transferred_to_office_name'
            )
            ->get();

        $logs = ReturnSlipLog::where('return_slip_id', $id)->orderBy('id', 'desc')->get();
        $endusers = $this->enduserOptions();
        $offices = Office::orderBy('office_name')->get();
        $conditions = self::CONDITIONS;

        return view('return_slips.show', compact('setting', 'slip', 'items', 'logs', 'endusers', 'offices', 'conditions'));
    }

    /**
     * Hand a returned item over to a specific end user.
     */
    public function transferItem(Request $request, $itemId)
    {
        if (!$this->isSupply()) {
            abort(403);
        }

        $validated = $request->validate([
            'enduser_id' => 'required|integer',
            'office_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'condition' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
        ], [
            'enduser_id.required' => 'Select the end user who will receive the item.',
            'condition.required' => 'Select the condition the item is released in.',
        ]);

        $item = $this->awaitingItemOrFail($itemId);
        $slip = ReturnSlip::findOrFail($item->return_slip_id);

        $enduser = Accountable::find($validated['enduser_id']);

        if (!$enduser) {
            return redirect()->back()->with('error', 'The selected end user no longer exists.');
        }

        if (!in_array($validated['condition'], self::CONDITIONS, true)) {
            return redirect()->back()->with('error', 'Invalid condition selected.');
        }

        $officeId = ($validated['office_id'] ?? null) ?: ($enduser->off_id ?: $item->current_office_id);
        $locationId = ($validated['location_id'] ?? null) ?: null;

        DB::transaction(function () use ($request, $validated, $slip, $item, $enduser, $officeId, $locationId) {
            // The receiver takes over both columns: person_accnt is what the
            // properties list shows as PERSON ACCOUNTABLE and person_accnt1 the
            // END USER, so a transfer that touched only the latter left the old
            // holder still named as accountable. person_accnt_name is the
            // denormalised copy the inventory screens read.
            $update = [
                'person_accnt' => $enduser->id,
                'person_accnt_name' => $enduser->person_accnt,
                'person_accnt1' => $enduser->id,
                'remarks' => $validated['condition'],
            ];

            if ($officeId) {
                $update['office_id'] = $officeId;
            }

            if ($locationId) {
                $update['location'] = $locationId;
            }

            EnduserProperty::where('id', $item->enduser_property_id)->update($update);

            $item->update([
                'status' => self::STATUS_TRANSFERRED,
                'action_type' => self::STATUS_TRANSFERRED,
                'transferred_to_enduser_id' => $enduser->id,
                'transferred_to_enduser_name' => $enduser->person_accnt,
                'transferred_to_office_id' => $officeId,
                'transferred_to_location_id' => $locationId,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
                'action_remarks' => $validated['remarks'] ?? null,
            ]);

            $slip->update([
                'request_type' => 'Transfer',
                'target_office_id' => $officeId,
            ]);

            $this->recordLog($slip, $item, 'Transferred', 'Item transferred to end user '.$enduser->person_accnt.'.', [
                'from_value' => self::STATUS_RETURNED,
                'to_value' => $validated['condition'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $this->syncSlipStatus($slip->id);
        });

        return redirect()->route('returnSlips.show', $slip->id)
            ->with('success', 'Item transferred to '.$enduser->person_accnt.'.');
    }

    /**
     * Tag a returned item as unserviceable instead of releasing it back out.
     */
    public function unserviceableItem(Request $request, $itemId)
    {
        return $this->writeOffItem($request, $itemId, self::STATUS_UNSERVICEABLE);
    }

    /**
     * Tag a returned item as obsolete instead of releasing it back out.
     */
    public function obsoleteItem(Request $request, $itemId)
    {
        return $this->writeOffItem($request, $itemId, self::STATUS_OBSOLETE);
    }

    /**
     * Close a returned item by stamping a final condition onto the property.
     */
    private function writeOffItem(Request $request, $itemId, string $condition)
    {
        if (!$this->isSupply()) {
            abort(403);
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $item = $this->awaitingItemOrFail($itemId);
        $slip = ReturnSlip::findOrFail($item->return_slip_id);

        DB::transaction(function () use ($validated, $slip, $item, $condition) {
            EnduserProperty::where('id', $item->enduser_property_id)->update([
                'remarks' => $condition,
            ]);

            $item->update([
                'status' => $condition,
                'action_type' => $condition,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
                'action_remarks' => $validated['remarks'] ?? null,
            ]);

            $slip->update(['request_type' => $condition]);

            $this->recordLog($slip, $item, $condition, 'Item remarks updated to '.$condition.'.', [
                'from_value' => self::STATUS_RETURNED,
                'to_value' => $condition,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $this->syncSlipStatus($slip->id);
        });

        return redirect()->route('returnSlips.show', $slip->id)
            ->with('success', 'Item marked as '.$condition.'.');
    }

    /**
     * Undo a return that was logged by mistake and put the old remarks back.
     */
    public function cancelItem(Request $request, $itemId)
    {
        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $item = $this->awaitingItemOrFail($itemId);
        $slip = ReturnSlip::findOrFail($item->return_slip_id);

        if (!$this->isSupply() && $slip->user_id !== auth()->id()) {
            abort(403);
        }

        DB::transaction(function () use ($validated, $slip, $item) {
            $restored = $item->previous_remarks ?: ($item->current_status ?: 'Good Condition');

            EnduserProperty::where('id', $item->enduser_property_id)->update([
                'remarks' => $restored,
                'date_return' => null,
            ]);

            $item->update([
                'status' => self::STATUS_CANCELLED,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'action_remarks' => $validated['remarks'] ?? null,
            ]);

            $this->recordLog($slip, $item, 'Cancelled', 'Return cancelled, property remarks restored.', [
                'from_value' => self::STATUS_RETURNED,
                'to_value' => $restored,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $this->syncSlipStatus($slip->id);
        });

        return redirect()->route('returnSlips.show', $slip->id)->with('success', 'Return cancelled and property restored.');
    }

    public function deleteItem($itemId)
    {
        $item = $this->awaitingItemOrFail($itemId);
        $slip = ReturnSlip::findOrFail($item->return_slip_id);

        if (!$this->isSupply() && $slip->user_id !== auth()->id()) {
            abort(403);
        }

        DB::transaction(function () use ($slip, $item) {
            $restored = $item->previous_remarks ?: ($item->current_status ?: 'Good Condition');

            EnduserProperty::where('id', $item->enduser_property_id)->update([
                'remarks' => $restored,
                'date_return' => null,
            ]);

            $this->recordLog($slip, $item, 'Deleted', 'Return record deleted, property remarks restored.', [
                'from_value' => self::STATUS_RETURNED,
                'to_value' => $restored,
            ]);

            $item->delete();

            if (!ReturnSlipItem::where('return_slip_id', $slip->id)->exists()) {
                $slip->delete();

                return;
            }

            $this->syncSlipStatus($slip->id);
        });

        if (!ReturnSlip::where('id', $slip->id)->exists()) {
            return redirect()->route('returnSlips.index')->with('success', 'Return record deleted and property restored.');
        }

        return redirect()->route('returnSlips.show', $slip->id)->with('success', 'Return item deleted and property restored.');
    }

    /**
     * Full audit trail across every return, for item tracking.
     */
    public function logs(Request $request)
    {
        if (!$this->isSupply()) {
            abort(403);
        }

        $setting = Setting::firstOrNew(['id' => 1]);

        $logs = ReturnSlipLog::query()
            ->when($request->filled('property'), function ($query) use ($request) {
                return $query->where('property_no_generated', 'like', '%'.$request->input('property').'%');
            })
            ->orderBy('id', 'desc')
            ->limit(1000)
            ->get();

        return view('return_slips.logs', compact('setting', 'logs'));
    }

    // =====================================================================
    // IIRUP (Appendix 74)
    // Inventory and Inspection Report of Unserviceable Property, filled from
    // public/IIRUP - FORM.xlsx and sourced from the return-slip logs.
    // =====================================================================

    public function iirupReportForm(Request $request)
    {
        if (!$this->isSupply()) {
            abort(403);
        }

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|in:all,'.self::STATUS_UNSERVICEABLE.','.self::STATUS_OBSOLETE,
        ], [
            'date_to.after_or_equal' => 'The "to" date cannot be earlier than the "from" date.',
        ]);

        $setting = Setting::firstOrNew(['id' => 1]);
        $filters = $this->iirupReportOptions($request);
        $data = $this->buildIirupReportPreviewData($filters);
        $statuses = [self::STATUS_UNSERVICEABLE, self::STATUS_OBSOLETE];

        return view('return_slips.iirup_report_form', compact('setting', 'filters', 'data', 'statuses'));
    }

    public function iirupReportGenerate(Request $request)
    {
        if (!$this->isSupply()) {
            abort(403);
        }

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|in:all,'.self::STATUS_UNSERVICEABLE.','.self::STATUS_OBSOLETE,
            'fund_cluster' => 'nullable|string|max:100',
            'accountable_name' => 'nullable|string|max:150',
            'designation' => 'nullable|string|max:150',
            'station' => 'nullable|string|max:150',
            'format' => 'nullable|in:pdf,excel',
        ], [
            'date_to.after_or_equal' => 'The "to" date cannot be earlier than the "from" date.',
        ]);

        @set_time_limit(0);

        $data = $this->buildIirupReportData($this->iirupReportOptions($request));
        $filename = $this->iirupReportFilename($data);

        if ($request->input('format') === 'pdf') {
            $pdf = Snappy::loadView('return_slips.iirup_report_pdf', $data)
                // Long bond paper: 8.5 x 13 inches, rendered landscape.
                ->setOption('page-width', '330.2mm')
                ->setOption('page-height', '215.9mm')
                ->setOption('disable-smart-shrinking', true)
                ->setOption('no-stop-slow-scripts', true)
                ->setOption('quiet', true)
                ->setOption('margin-top', '8mm')
                ->setOption('margin-bottom', '17mm')
                ->setOption('margin-left', '8mm')
                ->setOption('margin-right', '8mm');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'.pdf"',
            ]);
        }

        $spreadsheet = (new IirupExcelBuilder())->build($data);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildIirupReportData(array $opts): array
    {
        $items = $this->iirupReportRows($opts);

        return [
            'items' => $items,
            'total_cost' => (float) $items->sum('total_cost'),
            'item_count' => $items->count(),
            'fund_cluster' => trim((string) $opts['fund_cluster']),
            'accountable_name' => trim((string) $opts['accountable_name']),
            'designation' => trim((string) $opts['designation']),
            'station' => trim((string) $opts['station']),
            'status' => $opts['status'],
            'scope_label' => $this->iirupReportScopeLabel($opts),
            'period_label' => $this->transferReportPeriodLabel($opts),
            'date_from' => $opts['date_from'],
            'date_to' => $opts['date_to'],
            'generated_at' => now()->format('F d, Y h:i A'),
            'generated_by' => $this->currentUserFullName(),
            'letterhead' => $this->transferReportLetterhead(),
        ];
    }

    private function buildIirupReportPreviewData(array $opts): array
    {
        $query = $this->iirupReportBaseQuery($opts);
        $items = $query->paginate(50)->appends($this->iirupReportQueryParams($opts));
        $items->setCollection($items->getCollection()->map(fn ($row) => $this->mapIirupReportRow($row)));

        return [
            'items' => $items,
            'total_cost' => $this->iirupReportTotalCost($opts),
            'item_count' => $items->total(),
            'fund_cluster' => trim((string) $opts['fund_cluster']),
            'accountable_name' => trim((string) $opts['accountable_name']),
            'designation' => trim((string) $opts['designation']),
            'station' => trim((string) $opts['station']),
            'status' => $opts['status'],
            'scope_label' => $this->iirupReportScopeLabel($opts),
            'period_label' => $this->transferReportPeriodLabel($opts),
            'date_from' => $opts['date_from'],
            'date_to' => $opts['date_to'],
            'generated_at' => now()->format('F d, Y h:i A'),
            'generated_by' => $this->currentUserFullName(),
            'letterhead' => $this->transferReportLetterhead(),
        ];
    }

    private function iirupReportRows(array $opts)
    {
        return $this->iirupReportBaseQuery($opts)
            ->get()
            ->map(fn ($row) => $this->mapIirupReportRow($row));
    }

    private function iirupReportBaseQuery(array $opts)
    {
        return $this->iirupReportFilteredQuery($opts)
            ->select(
                'return_slip_logs.id as log_id',
                'return_slip_logs.action',
                'return_slip_logs.remarks as log_remarks',
                'return_slip_logs.created_at as log_created_at',
                'return_slip_items.id as item_id',
                'return_slip_items.property_no_generated',
                'return_slip_items.item_name',
                'return_slip_items.serial_number',
                'return_slip_items.action_remarks',
                'return_slips.id as slip_id',
                'enduser_property.item_descrip',
                'enduser_property.item_model',
                'enduser_property.qty',
                'enduser_property.item_cost',
                'enduser_property.total_cost',
                'enduser_property.date_acquired',
                'items.item_name as catalog_item_name',
                'units.unit_name',
                'offices.office_name'
            )
            ->orderBy('return_slip_logs.created_at')
            ->orderBy('return_slip_logs.id');
    }

    private function iirupReportFilteredQuery(array $opts)
    {
        return ReturnSlipLog::query()
            ->join('return_slip_items', 'return_slip_logs.return_slip_item_id', '=', 'return_slip_items.id')
            ->join('return_slips', 'return_slip_logs.return_slip_id', '=', 'return_slips.id')
            ->leftJoin('enduser_property', 'return_slip_logs.enduser_property_id', '=', 'enduser_property.id')
            ->leftJoin('items', 'enduser_property.item_id', '=', 'items.id')
            ->leftJoin('units', 'enduser_property.unit_id', '=', 'units.id')
            ->leftJoin('offices', 'return_slip_items.current_office_id', '=', 'offices.id')
            ->whereIn('return_slip_logs.action', [self::STATUS_UNSERVICEABLE, self::STATUS_OBSOLETE])
            ->when($opts['status'] !== 'all', function ($query) use ($opts) {
                return $query->where('return_slip_logs.action', $opts['status']);
            })
            ->when(!empty($opts['date_from']), function ($query) use ($opts) {
                return $query->whereDate('return_slip_logs.created_at', '>=', $opts['date_from']);
            })
            ->when(!empty($opts['date_to']), function ($query) use ($opts) {
                return $query->whereDate('return_slip_logs.created_at', '<=', $opts['date_to']);
            });
    }

    private function mapIirupReportRow($row): array
    {
        $name = $row->item_name ?: $row->catalog_item_name;
        $qty = (float) str_replace(',', '', (string) ($row->qty ?? 0));
        $unitCost = (float) str_replace(',', '', (string) ($row->item_cost ?? 0));
        $totalCost = (float) str_replace(',', '', (string) ($row->total_cost ?? 0));

        if ($totalCost <= 0) {
            $totalCost = $unitCost * max(1, $qty);
        }

        return [
            'date_acquired' => $this->transferReportDate($row->date_acquired),
            'item_name' => (string) $name,
            'description' => $this->transferReportDescription($row->item_descrip, $name),
            'model' => trim(str_ireplace('Model:', '', (string) $row->item_model)),
            'property_no' => (string) ($row->property_no_generated ?: ''),
            'serial_number' => trim((string) $row->serial_number),
            'qty' => $qty > 0 ? $qty : 1,
            'unit_name' => (string) ($row->unit_name ?: ''),
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'status' => (string) $row->action,
            'remarks' => (string) ($row->log_remarks ?: $row->action_remarks ?: ''),
            'log_date' => $row->log_created_at ? Carbon::parse($row->log_created_at)->format('m/d/Y') : '',
            'slip_no' => 'RS-'.str_pad((string) $row->slip_id, 5, '0', STR_PAD_LEFT),
            'office_name' => (string) ($row->office_name ?: ''),
        ];
    }

    private function iirupReportTotalCost(array $opts): float
    {
        $query = $this->iirupReportFilteredQuery($opts)
            ->selectRaw("
                SUM(
                    CASE
                        WHEN CAST(REPLACE(COALESCE(enduser_property.total_cost, 0), ',', '') AS DECIMAL(15,2)) > 0
                            THEN CAST(REPLACE(COALESCE(enduser_property.total_cost, 0), ',', '') AS DECIMAL(15,2))
                        ELSE CAST(REPLACE(COALESCE(enduser_property.item_cost, 0), ',', '') AS DECIMAL(15,2))
                            * GREATEST(1, CAST(REPLACE(COALESCE(enduser_property.qty, 1), ',', '') AS DECIMAL(15,2)))
                    END
                ) as aggregate
            ");

        return (float) ($query->value('aggregate') ?? 0);
    }

    private function iirupReportQueryParams(array $opts): array
    {
        return array_filter([
            'date_from' => $opts['date_from'],
            'date_to' => $opts['date_to'],
            'status' => $opts['status'] !== 'all' ? $opts['status'] : null,
            'fund_cluster' => $opts['fund_cluster'],
            'accountable_name' => $opts['accountable_name'],
            'designation' => $opts['designation'],
            'station' => $opts['station'],
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function iirupReportOptions(Request $request): array
    {
        return [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'status' => $request->input('status') ?: 'all',
            'fund_cluster' => (string) $request->input('fund_cluster'),
            'accountable_name' => (string) $request->input('accountable_name', 'BENJAMIN R. DELA TORRE'),
            'designation' => (string) $request->input('designation', 'Administrative Officer V/Head Supply Unit'),
            'station' => (string) $request->input('station', 'Gil Montilla National High School'),
        ];
    }

    private function iirupReportScopeLabel(array $opts): string
    {
        if ($opts['status'] === self::STATUS_UNSERVICEABLE) {
            return 'Items logged as Unserviceable';
        }

        if ($opts['status'] === self::STATUS_OBSOLETE) {
            return 'Items logged as Obsolete';
        }

        return 'Items logged as Unserviceable or Obsolete';
    }

    private function iirupReportFilename(array $data): string
    {
        $status = $data['status'] === 'all' ? 'Unserviceable-and-Obsolete' : $data['status'];
        $date = now()->format('Ymd-His');

        return 'IIRUP-'.$status.'-'.$date;
    }

    // =====================================================================
    // Property Return Slip (Supply Form 10)
    // The slip the end user submits when handing property back, laid out to
    // match public/Supply-Form-10-PROPERTY-RETURN-SLIP-REVISED (for DRAR).xlsx.
    // =====================================================================

    /**
     * Report builder: pick a return, preview the slip as PDF in the page, open it
     * in its own tab, or pull the .xlsx that mirrors the blank Supply Form 10.
     *
     * The same page the Property Transfer Report uses, narrowed to one return —
     * a return slip documents a single hand-back, so there is nothing to filter
     * beyond choosing which return to print.
     */
    public function slipReportForm(Request $request)
    {
        $setting = Setting::firstOrNew(['id' => 1]);

        $slips = $this->slipReportOptions();

        // A slip may be pre-selected from the list or the details page so the
        // preview is already rendered when the page opens.
        $selectedId = $request->filled('slip')
            ? (int) $request->input('slip')
            : (int) ($slips->first()['id'] ?? 0);

        if ($selectedId && !$slips->contains(fn ($slip) => $slip['id'] === $selectedId)) {
            $selectedId = (int) ($slips->first()['id'] ?? 0);
        }

        return view('return_slips.slip_report_form', compact('setting', 'slips', 'selectedId'));
    }

    /**
     * What the selected return would print, so the form can say what the slip
     * covers before the PDF is rendered.
     */
    public function slipReportSummary(Request $request)
    {
        $slip = $this->slipQuery()->where('return_slips.id', $request->input('slip'))->first();

        if (!$slip || (!$this->isSupply() && $slip->user_id !== auth()->id())) {
            return response()->json([
                'ok' => false,
                'message' => 'Select a return that you are allowed to print.',
            ], 422);
        }

        $data = $this->buildReturnSlipReportData($slip);

        return response()->json([
            'ok' => true,
            'slip_no' => $data['slip_no'],
            'item_count' => $data['items']->count(),
            'total_value' => number_format($data['total_value'], 2),
            'end_user' => $data['end_user'],
            'office_name' => $data['office_name'],
            'submitted_at' => $data['submitted_at'],
            'status' => (string) $slip->status,
            'reason' => $data['reason'],
            // The blank form gives PRS_ROWS_PER_PAGE lines; more than that spills
            // onto a second sheet, which the clerk should know before printing.
            'pages' => max(1, (int) ceil($data['items']->count() / self::PRS_ROWS_PER_PAGE)),
        ]);
    }

    /**
     * The returns the current user may print, newest first, labelled the way the
     * picker shows them. Supply sees every return, everyone else only their own.
     */
    private function slipReportOptions()
    {
        return $this->slipQuery()
            ->when(!$this->isSupply(), function ($query) {
                return $query->where('return_slips.user_id', auth()->id());
            })
            ->withCount(['items' => function ($query) {
                return $query->where('status', '!=', self::STATUS_CANCELLED);
            }])
            ->orderBy('return_slips.id', 'desc')
            ->limit(500)
            ->get()
            ->map(function ($slip) {
                $date = $slip->returned_at ?: $slip->created_at;

                return [
                    'id' => (int) $slip->id,
                    'slip_no' => 'RS-'.str_pad((string) $slip->id, 5, '0', STR_PAD_LEFT),
                    'end_user' => trim((string) ($slip->returned_by_display ?: 'Unspecified')),
                    'office_name' => (string) ($slip->target_office_name ?: ''),
                    'date' => $date ? Carbon::parse($date)->format('M d, Y') : '',
                    'status' => (string) $slip->status,
                    'item_count' => (int) $slip->items_count,
                ];
            });
    }

    /**
     * Prints one return as the official Property Return Slip: PDF inline for
     * previewing, or the .xlsx built from the blank Supply Form 10.
     */
    public function returnSlipReport(Request $request, $id)
    {
        $slip = $this->slipQuery()->where('return_slips.id', $id)->firstOrFail();

        // Same rule as viewing the slip: Supply sees everything, everyone else
        // only the returns they recorded.
        if (!$this->isSupply() && $slip->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'format' => 'nullable|in:pdf,excel',
        ]);

        $data = $this->buildReturnSlipReportData($slip);
        $filename = 'Property-Return-Slip-'.$data['slip_no'];

        if ($request->input('format') === 'excel') {
            $spreadsheet = (new PropertyReturnSlipExcelBuilder())->build($data);
            $writer = new Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $pdf = Snappy::loadView('return_slips.return_slip_pdf', $data)
            ->setOption('page-size', 'Letter')
            ->setOption('orientation', 'Portrait')
            ->setOption('disable-smart-shrinking', true)
            ->setOption('no-stop-slow-scripts', true)
            ->setOption('quiet', true)
            ->setOption('margin-top', '8mm')
            ->setOption('margin-bottom', '8mm')
            ->setOption('margin-left', '8mm')
            ->setOption('margin-right', '8mm');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'.pdf"',
        ]);
    }

    private function buildReturnSlipReportData($slip): array
    {
        $items = $this->returnSlipReportItems($slip->id);

        $submittedAt = $slip->returned_at ?: $slip->created_at;

        return [
            'slip' => $slip,
            'slip_no' => 'RS-'.str_pad((string) $slip->id, 5, '0', STR_PAD_LEFT),
            'items' => $items,
            'total_value' => (float) $items->sum('total_value'),
            'rows_per_page' => self::PRS_ROWS_PER_PAGE,
            // "Prepared and Submitted by" on the form is the end user handing the
            // property back, not the Supply clerk keying it in.
            'end_user' => strtoupper(trim((string) ($slip->returned_by_display ?: $slip->returned_by_name))),
            'office_name' => (string) ($items->first()['office_name'] ?? ''),
            'submitted_at' => $submittedAt ? Carbon::parse($submittedAt)->format('F d, Y') : '',
            'reason' => trim((string) $slip->reason),
            'instructions' => self::PRS_INSTRUCTIONS,
            'received_by' => self::PRS_RECEIVED_BY,
            'recorded_by' => self::PRS_RECORDED_BY,
            'approved_by' => self::PRS_APPROVED_BY,
            'letterhead' => $this->transferReportLetterhead(),
            'generated_at' => now()->format('F d, Y h:i A'),
            'generated_by' => $this->currentUserFullName(),
        ];
    }

    /**
     * The properties on one return, with the acquisition details the slip prints.
     * Cancelled items are left off because that return was undone.
     */
    private function returnSlipReportItems($slipId)
    {
        return ReturnSlipItem::query()
            ->where('return_slip_items.return_slip_id', $slipId)
            ->where('return_slip_items.status', '!=', self::STATUS_CANCELLED)
            ->leftJoin('enduser_property', 'return_slip_items.enduser_property_id', '=', 'enduser_property.id')
            ->leftJoin('items', 'enduser_property.item_id', '=', 'items.id')
            ->leftJoin('units', 'enduser_property.unit_id', '=', 'units.id')
            ->leftJoin('offices', 'return_slip_items.current_office_id', '=', 'offices.id')
            ->leftJoin('accountable as endusers', 'enduser_property.person_accnt1', '=', 'endusers.id')
            ->leftJoin('accountable as accountables', 'enduser_property.person_accnt', '=', 'accountables.id')
            ->select(
                'return_slip_items.id as item_id',
                'return_slip_items.property_no_generated',
                'return_slip_items.item_name',
                'return_slip_items.serial_number',
                'return_slip_items.status as item_status',
                'return_slip_items.previous_remarks',
                'enduser_property.item_descrip',
                'enduser_property.item_model',
                'enduser_property.qty',
                'enduser_property.item_cost',
                'enduser_property.total_cost',
                'enduser_property.date_acquired',
                'items.item_name as catalog_item_name',
                'units.unit_name',
                'offices.office_name',
                'endusers.person_accnt as enduser_name',
                'accountables.person_accnt as accountable_name'
            )
            ->orderBy('return_slip_items.id')
            ->get()
            ->map(function ($row) {
                $name = $row->item_name ?: $row->catalog_item_name;
                $qty = (float) str_replace(',', '', (string) ($row->qty ?? 0));
                $unitValue = (float) str_replace(',', '', (string) ($row->item_cost ?? 0));
                $totalValue = (float) str_replace(',', '', (string) ($row->total_cost ?? 0));

                // total_cost is not always populated, so fall back to the product.
                if ($totalValue <= 0) {
                    $totalValue = $unitValue * max(1, $qty);
                }

                return [
                    'qty' => $qty > 0 ? $qty : 1,
                    'unit_name' => (string) ($row->unit_name ?: ''),
                    'item_name' => (string) $name,
                    'descrip' => $this->transferReportDescription($row->item_descrip, $name),
                    'model' => trim(str_ireplace('Model:', '', (string) $row->item_model)),
                    'serial_number' => trim((string) $row->serial_number),
                    'unit_value' => $unitValue,
                    'total_value' => $totalValue,
                    'property_no' => (string) ($row->property_no_generated ?: ''),
                    'date_acquired' => $this->transferReportDate($row->date_acquired),
                    // No fund code is recorded anywhere in the system, so the column
                    // is left blank for the Supply clerk to fill in by hand.
                    'fund_code' => '',
                    'enduser_name' => (string) ($row->enduser_name ?: $row->accountable_name ?: ''),
                    'office_name' => (string) ($row->office_name ?: ''),
                    'condition' => (string) ($row->previous_remarks ?: ''),
                ];
            });
    }

    // =====================================================================
    // Property Transfer Report (GAM Appendix 76)
    // Built from the return-slip audit trail, laid out to match
    // public/Supply-Form-11-Property-Transfer-Report.xlsx.
    // =====================================================================

    /**
     * Report builder: pick the end user who handed the property back and the end
     * user who received it, then preview the PTR as PDF or pull the .xlsx that
     * mirrors the blank Supply Form 11.
     */
    public function transferReportForm()
    {
        if (!$this->isSupply()) {
            abort(403);
        }

        $setting = Setting::firstOrNew(['id' => 1]);
        $endusers = $this->transferReportEnduserOptions();
        $custodians = collect();
        $others = $endusers->values();
        $transferTypes = self::TRANSFER_TYPES;
        $releasedByOptions = self::PTR_RELEASED_BY_OPTIONS;

        return view('return_slips.transfer_report_form', compact(
            'setting',
            'endusers',
            'custodians',
            'others',
            'transferTypes',
            'releasedByOptions'
        ));
    }

    /**
     * What the current selection would produce, so the form can say how many
     * items are covered before anything is rendered.
     */
    public function transferReportSummary(Request $request)
    {
        if (!$this->isSupply()) {
            abort(403);
        }

        $fromPerson = $this->transferReportPerson($request->input('from_enduser_id'));
        $toPerson = $this->transferReportPerson($request->input('to_enduser_id'));

        if (!$fromPerson || !$toPerson) {
            return response()->json([
                'ok' => false,
                'message' => 'Select both the end user who returned the property and the one who received it.',
            ], 422);
        }

        $data = $this->buildTransferReportData($fromPerson, $toPerson, $this->transferReportOptions($request));

        return response()->json([
            'ok' => true,
            'scope' => $data['scope_label'],
            'item_count' => $data['items']->count(),
            'total_amount' => number_format($data['total_amount'], 2),
            'from' => $data['from_officer']['name'],
            'from_designation' => $data['from_officer']['designation'],
            'to' => $data['to_officer']['name'],
            'to_designation' => $data['to_officer']['designation'],
            'from_is_custodian' => $data['from_is_custodian'],
            'to_is_custodian' => $data['to_is_custodian'],
            'same_person' => $data['same_person'],
        ]);
    }

    /**
     * Renders the PTR inline as PDF (the preview) or streams the .xlsx.
     */
    public function transferReportGenerate(Request $request)
    {
        if (!$this->isSupply()) {
            abort(403);
        }

        $request->validate([
            'from_enduser_id' => 'required|integer',
            'to_enduser_id' => 'required|integer|different:from_enduser_id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'fund_cluster' => 'nullable|string|max:100',
            'ptr_no' => 'required|string|max:100',
            'ptr_date' => 'nullable|date',
            'transfer_type' => 'nullable|in:'.implode(',', self::TRANSFER_TYPES),
            'transfer_type_other' => 'nullable|string|max:150',
            'reason' => 'nullable|string|max:2000',
            'released_by' => 'nullable|in:'.implode(',', array_keys(self::PTR_RELEASED_BY_OPTIONS)),
            'format' => 'nullable|in:pdf,excel',
        ], [
            'from_enduser_id.required' => 'Select the end user who returned the property.',
            'to_enduser_id.required' => 'Select the end user who received the property.',
            'to_enduser_id.different' => 'The receiving end user must be different from the one who returned the property.',
            'ptr_no.required' => 'Enter the PTR No. the report will be filed under.',
            'date_to.after_or_equal' => 'The "to" date cannot be earlier than the "from" date.',
        ]);

        $fromPerson = $this->transferReportPerson($request->input('from_enduser_id'));
        $toPerson = $this->transferReportPerson($request->input('to_enduser_id'));

        if (!$fromPerson || !$toPerson) {
            return redirect()->route('returnSlips.transferReport')
                ->with('error', 'One of the selected end users no longer exists.');
        }

        $data = $this->buildTransferReportData($fromPerson, $toPerson, $this->transferReportOptions($request));
        $filename = $this->transferReportFilename($data);

        if ($request->input('format') === 'excel') {
            $spreadsheet = (new PropertyTransferReportExcelBuilder())->build($data);
            $writer = new Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $pdf = Snappy::loadView('return_slips.transfer_report_pdf', $data)
            ->setOption('page-size', 'A4')
            ->setOption('orientation', 'Portrait')
            ->setOption('disable-smart-shrinking', true)
            ->setOption('no-stop-slow-scripts', true)
            ->setOption('quiet', true)
            ->setOption('margin-top', '8mm')
            ->setOption('margin-bottom', '8mm')
            ->setOption('margin-left', '10mm')
            ->setOption('margin-right', '10mm');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'.pdf"',
        ]);
    }

    /**
     * Everything the PDF view and the Excel builder need, so both render the
     * same document from the same numbers.
     *
     * One PTR documents one hand-over: $fromPerson gave the property up and
     * $toPerson took it on. Both sides are read off the return-slip trail.
     */
    private function buildTransferReportData($fromPerson, $toPerson, array $opts): array
    {
        $fromIsCustodian = $this->isCustodian($fromPerson);
        $toIsCustodian = $this->isCustodian($toPerson);
        $fromSchool = $this->transferReportSchoolName($fromPerson);
        $toSchool = $this->transferReportSchoolName($toPerson);

        $items = $this->transferReportRows($fromPerson, $toPerson, $opts);

        $fromBlock = [
            'name' => strtoupper(trim((string) $fromPerson->person_accnt)),
            'designation' => $this->transferReportDesignation($fromPerson, $fromSchool),
        ];

        $toBlock = [
            'name' => strtoupper(trim((string) $toPerson->person_accnt)),
            'designation' => $this->transferReportDesignation($toPerson, $toSchool),
        ];

        // Moving property to another SCHOOL is a relocation; passing it to another
        // person inside the same SCHOOL is a reassignment.
        $transferType = $opts['transfer_type']
            ?: (($toIsCustodian || $fromSchool !== $toSchool) ? 'Relocate' : 'Reassignment');

        $reason = trim((string) $opts['reason']);

        if ($reason === '') {
            $reason = 'Property returned to the Supply Office by '.trim((string) $fromPerson->person_accnt)
                .' and released to '.trim((string) $toPerson->person_accnt)
                .($toSchool ? ' of '.$toSchool : '').'.';
        }

        return [
            'from_person' => $fromPerson,
            'to_person' => $toPerson,
            'from_is_custodian' => $fromIsCustodian,
            'to_is_custodian' => $toIsCustodian,
            'same_person' => (int) $fromPerson->id === (int) $toPerson->id,
            'from_school_name' => $fromSchool,
            'to_school_name' => $toSchool,
            'scope_label' => $this->transferReportScopeLabel($fromPerson, $toPerson, $fromSchool, $toSchool),
            'items' => $items,
            'total_amount' => (float) $items->sum('amount'),
            // The PTR's two accountable officers are the two end users themselves.
            'from_officer' => $fromBlock,
            'to_officer' => $toBlock,
            'fund_cluster' => trim((string) $opts['fund_cluster']),
            'ptr_no' => trim((string) $opts['ptr_no']),
            'ptr_date' => Carbon::parse($opts['ptr_date'] ?: now())->format('F d, Y'),
            'transfer_type' => $transferType,
            'transfer_type_other' => trim((string) $opts['transfer_type_other']),
            'transfer_types' => self::TRANSFER_TYPES,
            'reason' => $reason,
            'date_from' => $opts['date_from'],
            'date_to' => $opts['date_to'],
            'period_label' => $this->transferReportPeriodLabel($opts),
            // The previous holder releases, the new holder receives, and Supply
            // (which held the property in between) approves. The form can name a
            // Supply signatory under RELEASED/ISSUED BY instead of the holder.
            'approved_by' => self::PTR_APPROVED_BY,
            'released_by' => self::PTR_RELEASED_BY_OPTIONS[trim((string) ($opts['released_by'] ?? ''))] ?? $fromBlock,
            'received_by' => $toBlock,
            'generated_at' => now()->format('F d, Y h:i A'),
            'generated_by' => $this->currentUserFullName(),
            'letterhead' => $this->transferReportLetterhead(),
        ];
    }

    /**
     * Spells out what the two selections widened to, since picking a custodian
     * covers their whole school rather than just them.
     */
    private function transferReportScopeLabel($fromPerson, $toPerson, ?string $fromSchool, ?string $toSchool): string
    {
        $from = $this->isCustodian($fromPerson)
            ? 'returned from '.($fromSchool ?: 'the school offices')
            : 'returned by '.trim((string) $fromPerson->person_accnt);

        $to = $this->isCustodian($toPerson)
            ? 'released to '.($toSchool ?: 'the school offices')
            : 'released to '.trim((string) $toPerson->person_accnt);

        return 'Items '.$from.' and '.$to;
    }

    /**
     * wkhtmltopdf fetches images over HTTP, which breaks whenever APP_URL and the
     * real base path disagree, so the letterhead is inlined instead.
     */
    private function transferReportLetterhead(): string
    {
        $path = public_path('logo.png');

        if (!is_file($path)) {
            return asset('logo.png');
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }

    /**
     * One row per property that travelled the whole way from $fromPerson to
     * $toPerson, read off the return-slip trail.
     */
    private function transferReportRows($fromPerson, $toPerson, array $opts)
    {
        return $this->transferReportLogQuery($fromPerson, $toPerson, $opts)
            ->orderBy('transferred_log.created_at')
            ->orderBy('transferred_log.id')
            ->get()
            // A property can travel this route more than once; the newest pair of
            // log entries is the hand-over the document should describe.
            ->keyBy('item_id')
            ->values()
            ->map(function ($row) {
                $name = $row->item_name ?: $row->catalog_item_name;

                return [
                    'date_acquired' => $this->transferReportDate($row->date_acquired),
                    'property_no' => $row->property_no_generated ?: 'N/A',
                    'item_name' => $name,
                    'descrip' => $this->transferReportDescription($row->item_descrip, $name),
                    'model' => trim(str_ireplace('Model:', '', (string) $row->item_model)),
                    'serial_number' => trim((string) $row->serial_number),
                    'unit_name' => $row->unit_name,
                    'amount' => (float) str_replace(',', '', (string) ($row->item_cost ?? 0)),
                    // The PTR states the condition the receiving end user takes the
                    // property on in, which the release log records as to_value.
                    'condition' => $row->released_condition ?: ($row->item_status ?: 'N/A'),
                    'returned_condition' => (string) ($row->returned_condition ?: ''),
                    'returned_at' => $row->returned_log_at ? Carbon::parse($row->returned_log_at)->format('M d, Y') : '',
                    'released_at' => $row->transferred_log_at ? Carbon::parse($row->transferred_log_at)->format('M d, Y') : '',
                    'from_name' => (string) ($row->returned_by_display ?: ''),
                    'from_office_name' => (string) ($row->from_office_name ?: ''),
                    'to_name' => (string) ($row->transferred_to_enduser_name ?: $row->to_enduser_name ?: ''),
                    'to_office_name' => (string) ($row->to_office_name ?: $row->to_enduser_office_name ?: ''),
                    'remarks' => (string) ($row->release_remarks ?: $row->slip_reason ?: ''),
                    'slip_no' => 'RS-'.str_pad((string) $row->slip_id, 5, '0', STR_PAD_LEFT),
                ];
            });
    }

    /**
     * The trail drives the report. Each qualifying property has two log entries
     * against the same return-slip item: the "Returned" entry naming who gave it
     * up, and the "Transferred" entry naming who took it on. Joining both is what
     * lets the report be filtered by end user on each side at once.
     */
    private function transferReportLogQuery($fromPerson, $toPerson, array $opts)
    {
        $query = ReturnSlipItem::query()
            ->join('return_slips', 'return_slip_items.return_slip_id', '=', 'return_slips.id')
            ->join('return_slip_logs as returned_log', function ($join) {
                $join->on('returned_log.return_slip_item_id', '=', 'return_slip_items.id')
                    ->where('returned_log.action', '=', self::STATUS_RETURNED);
            })
            ->join('return_slip_logs as transferred_log', function ($join) {
                $join->on('transferred_log.return_slip_item_id', '=', 'return_slip_items.id')
                    ->where('transferred_log.action', '=', self::STATUS_TRANSFERRED);
            })
            ->leftJoin('enduser_property', 'return_slip_items.enduser_property_id', '=', 'enduser_property.id')
            ->leftJoin('items', 'enduser_property.item_id', '=', 'items.id')
            ->leftJoin('units', 'enduser_property.unit_id', '=', 'units.id')
            ->leftJoin('offices as from_offices', 'return_slip_items.current_office_id', '=', 'from_offices.id')
            ->leftJoin('offices as to_offices', 'return_slip_items.transferred_to_office_id', '=', 'to_offices.id')
            ->leftJoin('accountable as to_enduser', 'return_slip_items.transferred_to_enduser_id', '=', 'to_enduser.id')
            ->leftJoin('offices as to_enduser_offices', 'to_enduser.off_id', '=', 'to_enduser_offices.id')
            ->leftJoin('accountable as returned_by', 'return_slips.returned_by_id', '=', 'returned_by.id')
            // A cancelled return never happened, so it does not belong on a
            // transfer document.
            ->where('return_slip_items.status', '!=', self::STATUS_CANCELLED)
            ->select(
                'return_slip_items.id as item_id',
                'return_slip_items.property_no_generated',
                'return_slip_items.item_name',
                'return_slip_items.serial_number',
                'return_slip_items.status as item_status',
                'return_slip_items.transferred_to_enduser_name',
                'returned_log.from_value as returned_condition',
                'returned_log.created_at as returned_log_at',
                'transferred_log.to_value as released_condition',
                'transferred_log.remarks as release_remarks',
                'transferred_log.created_at as transferred_log_at',
                'transferred_log.id as transferred_log_id',
                'return_slips.id as slip_id',
                'return_slips.reason as slip_reason',
                DB::raw("COALESCE(return_slips.returned_by_name, returned_by.person_accnt) as returned_by_display"),
                'enduser_property.item_descrip',
                'enduser_property.item_model',
                'enduser_property.item_cost',
                'enduser_property.date_acquired',
                'items.item_name as catalog_item_name',
                'units.unit_name',
                'from_offices.office_name as from_office_name',
                'to_offices.office_name as to_office_name',
                'to_enduser.person_accnt as to_enduser_name',
                'to_enduser_offices.office_name as to_enduser_office_name'
            );

        $this->applyTransferReportFromFilter($query, $fromPerson);
        $this->applyTransferReportToFilter($query, $toPerson);

        // The release is the event the PTR documents, so the window applies to it.
        if (!empty($opts['date_from'])) {
            $query->whereDate('transferred_log.created_at', '>=', $opts['date_from']);
        }

        if (!empty($opts['date_to'])) {
            $query->whereDate('transferred_log.created_at', '<=', $opts['date_to']);
        }

        return $query;
    }

    /**
     * Who gave the property up. A custodian stands for their whole school, so the
     * office the property sat in when it was returned decides the match.
     */
    private function applyTransferReportFromFilter($query, $person): void
    {
        if ($this->isCustodian($person)) {
            $schoolId = $this->transferReportSchoolId($person);

            $query->where(function ($inner) use ($schoolId, $person) {
                $inner->where('return_slips.returned_by_id', $person->id);

                if ($schoolId) {
                    $inner->orWhere('from_offices.school_id', $schoolId);
                }
            });

            return;
        }

        $name = strtoupper(trim((string) $person->person_accnt));

        $query->where(function ($inner) use ($person, $name) {
            $inner->where('return_slips.returned_by_id', $person->id);

            // Older slips only recorded the name, not the accountable id.
            if ($name !== '') {
                $inner->orWhereRaw("UPPER(TRIM(COALESCE(return_slips.returned_by_name, ''))) = ?", [$name]);
            }
        });
    }

    /**
     * Who took the property on. A custodian again stands for their whole school,
     * so anything released to an office or end user inside it counts.
     */
    private function applyTransferReportToFilter($query, $person): void
    {
        if ($this->isCustodian($person)) {
            $schoolId = $this->transferReportSchoolId($person);

            $query->where(function ($inner) use ($schoolId, $person) {
                $inner->where('return_slip_items.transferred_to_enduser_id', $person->id);

                if ($schoolId) {
                    $inner->orWhere('to_offices.school_id', $schoolId)
                        ->orWhere('to_enduser_offices.school_id', $schoolId);
                }
            });

            return;
        }

        $query->where('return_slip_items.transferred_to_enduser_id', $person->id);
    }

    /**
     * The dropdown lists ordinary end users and office heads for the standalone school.
     */
    private function transferReportEnduserOptions()
    {
        $schools = School::pluck('school_name', 'id');

        return Accountable::query()
            ->leftJoin('offices', 'accountable.off_id', '=', 'offices.id')
            ->select(
                'accountable.id',
                'accountable.person_accnt',
                'accountable.accnt_role',
                'accountable.off_id',
                'offices.office_name',
                'offices.school_id'
            )
            ->orderBy('accountable.person_accnt')
            ->get()
            ->map(function ($row) use ($schools) {
                $schoolId = $row->school_id;

                $row->school_id = $schoolId;
                $row->school_name = $schoolId ? ($schools[$schoolId] ?? null) : null;
                $row->is_custodian = false;
                $row->role_label = (int) $row->accnt_role === self::ROLE_OFFICE_HEAD ? 'Office Head' : 'End User';

                return $row;
            });
    }

    private function transferReportPerson($id)
    {
        if (!$id) {
            return null;
        }

        return Accountable::query()
            ->leftJoin('offices', 'accountable.off_id', '=', 'offices.id')
            ->select(
                'accountable.id',
                'accountable.person_accnt',
                'accountable.accnt_role',
                'accountable.off_id',
                'offices.office_name',
                'offices.office_abbr',
                'offices.school_id'
            )
            ->where('accountable.id', $id)
            ->first();
    }

    private function transferReportOptions(Request $request): array
    {
        return [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'fund_cluster' => (string) $request->input('fund_cluster'),
            'ptr_no' => (string) $request->input('ptr_no'),
            'ptr_date' => $request->input('ptr_date'),
            'transfer_type' => in_array($request->input('transfer_type'), self::TRANSFER_TYPES, true)
                ? $request->input('transfer_type')
                : null,
            'transfer_type_other' => (string) $request->input('transfer_type_other'),
            'reason' => (string) $request->input('reason'),
            'released_by' => (string) $request->input('released_by'),
        ];
    }

    private function isCustodian($person): bool
    {
        return false;
    }

    private function transferReportSchoolId($person)
    {
        return $person->school_id;
    }

    private function transferReportSchoolName($person): ?string
    {
        $schoolId = $this->transferReportSchoolId($person);

        if (!$schoolId) {
            return $person->office_name ?: null;
        }

        return optional(School::find($schoolId))->school_name ?: ($person->office_name ?: null);
    }

    private function transferReportDesignation($person, ?string $schoolName): string
    {
        if ($this->isCustodian($person)) {
            return trim('Property Custodian'.($schoolName ? ', '.$schoolName : ''));
        }

        $role = (int) ($person->accnt_role ?? 0) === self::ROLE_OFFICE_HEAD ? 'Office Head' : 'End User';

        return trim($role.($person->office_name ? ', '.$person->office_name : ''));
    }

    private function transferReportPeriodLabel(array $opts): string
    {
        $from = !empty($opts['date_from']) ? Carbon::parse($opts['date_from'])->format('M d, Y') : null;
        $to = !empty($opts['date_to']) ? Carbon::parse($opts['date_to'])->format('M d, Y') : null;

        if ($from && $to) {
            return $from.' – '.$to;
        }

        if ($from) {
            return 'From '.$from;
        }

        if ($to) {
            return 'Up to '.$to;
        }

        return 'All recorded dates';
    }

    private function transferReportFilename(array $data): string
    {
        $slug = function ($person) {
            return trim(preg_replace('/[^A-Za-z0-9]+/', '-', (string) $person->person_accnt), '-');
        };

        return 'PTR-'.$slug($data['from_person']).'-to-'.$slug($data['to_person']);
    }

    private function transferReportDate($value): string
    {
        if (!$value || $value === '0000-00-00') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('m/d/Y');
        } catch (\Exception $e) {
            return (string) $value;
        }
    }

    /**
     * Property descriptions are sometimes blank or literally "N/A", in which case
     * the catalogue item name is the better description.
     */
    private function transferReportDescription($descrip, $fallback): string
    {
        $descrip = trim((string) $descrip);

        if ($descrip === '' || strcasecmp($descrip, 'n/a') === 0) {
            return (string) $fallback;
        }

        return $descrip;
    }

    /**
     * Writes both the return-slip audit trail and the system-wide log entry.
     */
    private function recordLog(ReturnSlip $slip, ?ReturnSlipItem $item, string $action, string $description, array $extra = []): void
    {
        $user = auth()->user();

        ReturnSlipLog::create([
            'return_slip_id' => $slip->id,
            'return_slip_item_id' => $item->id ?? null,
            'enduser_property_id' => $item->enduser_property_id ?? null,
            'property_no_generated' => $item->property_no_generated ?? null,
            'user_id' => $user->id,
            'user_name' => trim($user->fname.' '.$user->lname),
            'user_role' => $user->role,
            'action' => $action,
            'description' => $description,
            'from_value' => $extra['from_value'] ?? null,
            'to_value' => $extra['to_value'] ?? null,
            'remarks' => $extra['remarks'] ?? null,
        ]);

        Log::create([
            'school_id' => $user->school_id,
            'user_id' => $user->id,
            'module_id' => $item->enduser_property_id ?? $slip->id,
            'module' => 'return_slip',
            'action' => strtolower($action),
        ]);
    }

    private function awaitingItemOrFail($itemId): ReturnSlipItem
    {
        return ReturnSlipItem::where(function ($query) {
                $query->whereIn('status', [self::STATUS_RETURNED, 'Pending', ''])
                    ->orWhereNull('status');
            })
            ->findOrFail($itemId);
    }

    /**
     * Properties that can be handed back by $enduser: their own assigned items,
     * whatever the remarks, as long as the item is not already sitting in an open
     * return. Legacy role-2 records no longer widen access in this standalone version.
     *
     * Passing no end user leaves the list unscoped, which is only used for counting.
     */
    private function returnablePropertiesQuery($enduser = null)
    {
        $user = auth()->user();

        $query = EnduserProperty::query()
            ->join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            ->leftJoin('offices as location_offices', 'enduser_property.location', '=', 'location_offices.id')
            ->leftJoin('accountable as endusers', 'enduser_property.person_accnt1', '=', 'endusers.id')
            ->leftJoin('accountable as accountables', 'enduser_property.person_accnt', '=', 'accountables.id')
            ->select(
                'enduser_property.*',
                'enduser_property.id as pid',
                'offices.office_name',
                'location_offices.office_name as location_name',
                'items.item_name',
                'endusers.id as enduser_id',
                'endusers.person_accnt as enduser_name',
                'accountables.id as accountable_id',
                'accountables.person_accnt as accountable_name'
            )
            ->where('enduser_property.deleted', 0)
            // Guard against the same physical item being returned twice while an
            // earlier return is still awaiting Supply action.
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('return_slip_items')
                    ->whereColumn('return_slip_items.enduser_property_id', 'enduser_property.id')
                    ->whereIn('return_slip_items.status', [self::STATUS_RETURNED, 'Pending']);
            });

        // Supply holds every school; everyone else only sees their own school.
        if (!$this->isSupply()) {
            $query->where(function ($inner) use ($user) {
                $inner->where('offices.school_id', $user->school_id)
                    ->orWhere('offices.school_id', $user->school_id);
            });
        }

        if ($enduser) {
            $this->scopePropertiesToEnduser($query, $enduser);
        }

        return $query;
    }

    /**
     * Narrows a returnable-property query to one person's accountability.
     *
     * A property records two people: person_accnt is the accountable officer and
     * person_accnt1 the end user actually holding it. Which one carries a given
     * person depends on their role - office heads are usually the accountable
     * officer, staff the end user - so a person's items are matched on either column.
     */
    private function scopePropertiesToEnduser($query, $enduser): void
    {
        $schoolId = $this->isCustodian($enduser) ? $this->enduserSchoolId($enduser) : null;

        $query->where(function ($inner) use ($enduser, $schoolId) {
            $inner->where('enduser_property.person_accnt', $enduser->id)
                ->orWhere('enduser_property.person_accnt1', $enduser->id);

            // Legacy role-2 records keep just their own items rather than silently
            // listing every property in the system.
            if ($schoolId) {
                $inner->orWhere('offices.school_id', $schoolId);
            }
        });
    }

    private function missingEnduserMessage(): string
    {
        return 'Your name ('.$this->currentUserFullName().') is not on the Accountable Persons list yet, '
            .'so returns cannot be recorded under your account. Ask Supply to add you first.';
    }

    private function currentUserFullName(): string
    {
        $user = auth()->user();

        return trim($user->fname.' '.$user->lname);
    }

    /**
     * The signed-in custodian's matching end user record, if they are on the
     * accountable list. Users and accountables are only linked by name.
     */
    private function currentUserAsEnduser()
    {
        $fullName = $this->currentUserFullName();

        if ($fullName === '') {
            return null;
        }

        return Accountable::whereRaw('UPPER(TRIM(person_accnt)) = ?', [strtoupper($fullName)])->first();
    }

    /**
     * accnt_role is carried so the pickers can distinguish office heads from end users.
     */
    private function enduserOptions()
    {
        return Accountable::leftJoin('offices', 'accountable.off_id', '=', 'offices.id')
            ->select(
                'accountable.id',
                'accountable.person_accnt',
                'accountable.accnt_role',
                'accountable.off_id',
                'offices.office_name'
            )
            ->orderBy('accountable.person_accnt')
            ->get();
    }

    private function slipQuery()
    {
        return ReturnSlip::query()
            ->leftJoin('users', 'return_slips.user_id', '=', 'users.id')
            ->leftJoin('schools as source_school', 'return_slips.school_id', '=', 'source_school.id')
            ->leftJoin('offices', 'return_slips.target_office_id', '=', 'offices.id')
            ->leftJoin('accountable as returned_by', 'return_slips.returned_by_id', '=', 'returned_by.id')
            ->select(
                'return_slips.*',
                DB::raw("CONCAT(COALESCE(users.fname, ''), ' ', COALESCE(users.lname, '')) as recorder_name"),
                'source_school.school_name as source_school_name',
                'offices.office_name as target_office_name',
                DB::raw("COALESCE(return_slips.returned_by_name, returned_by.person_accnt) as returned_by_display")
            );
    }

    /**
     * One row per returned item, carrying the slip context each row needs.
     */
    private function returnedItemQuery()
    {
        return ReturnSlipItem::query()
            ->join('return_slips', 'return_slip_items.return_slip_id', '=', 'return_slips.id')
            ->leftJoin('users', 'return_slips.user_id', '=', 'users.id')
            ->leftJoin('schools as source_school', 'return_slips.school_id', '=', 'source_school.id')
            ->leftJoin('offices as item_offices', 'return_slip_items.current_office_id', '=', 'item_offices.id')
            ->leftJoin('offices as target_offices', 'return_slip_items.transferred_to_office_id', '=', 'target_offices.id')
            ->leftJoin('accountable as returned_by', 'return_slips.returned_by_id', '=', 'returned_by.id')
            ->select(
                'return_slip_items.*',
                'return_slip_items.id as item_id',
                'return_slip_items.status as item_status',
                'return_slips.id as slip_id',
                'return_slips.user_id as slip_user_id',
                'return_slips.reason',
                'return_slips.returned_at',
                'return_slips.created_at as slip_created_at',
                DB::raw("CONCAT(COALESCE(users.fname, ''), ' ', COALESCE(users.lname, '')) as recorder_name"),
                'source_school.school_name as source_school_name',
                'item_offices.office_name as current_office_name',
                'target_offices.office_name as transferred_to_office_name',
                DB::raw("COALESCE(return_slips.returned_by_name, returned_by.person_accnt) as returned_by_display")
            );
    }

    private function syncSlipStatus($slipId): void
    {
        $items = ReturnSlipItem::where('return_slip_id', $slipId)->get();

        if ($items->isEmpty()) {
            return;
        }

        $awaiting = $items->filter(function ($item) {
            return $item->isAwaitingAction();
        })->count();

        if ($awaiting > 0) {
            ReturnSlip::where('id', $slipId)->update(['status' => self::STATUS_RETURNED]);

            return;
        }

        $statuses = $items->pluck('status')->unique();

        ReturnSlip::where('id', $slipId)->update([
            'status' => $statuses->count() === 1 ? $statuses->first() : 'Completed',
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);
    }
}


