<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\EnduserProperty;
use App\Models\Purchases;
use App\Models\Office;
use App\Models\property;
use App\Models\Properties;
use App\Models\Unit;
use App\Models\Item; 
use App\Models\School;
use App\Models\Category;
use App\Models\User;
use App\Models\Accountable;
use Carbon\Carbon;
use PDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Barryvdh\Snappy\Facades\SnappyPdf as Snappy; 

class ReportsController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesLegacyReportRoutes;

    private function mainSchoolExcludedOfficeIds()
    {
        return [];
    }

    /**
     * Offices the signed-in user may report on, or null when they may report on
     * every school office.
     *
     * Administrators and Supply Officers work system-wide. Everyone else is pinned
     * to the single GMNHS school record, so a request naming another school's
     * office cannot pull data outside their assigned access. The office picker
     * already hides other offices; this is the
     * server-side half, since the form's values can be edited before they are sent.
     */
    private function allowedReportOfficeIds(): ?array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        if (in_array($user->role, ['Administrator', 'Supply Officer'], true)) {
            return null;
        }

        $schoolId = $user->school_id;

        if (!$schoolId) {
            return [];
        }

        if ((string) $schoolId === '1') {
            return Office::where('school_id', $schoolId)
                ->pluck('id')
                ->map(function ($id) { return (int) $id; })
                ->all();
        }

        return Office::where('school_id', $schoolId)
            ->pluck('id')
            ->map(function ($id) { return (int) $id; })
            ->all();
    }

    /**
     * Drops the placeholders the form can send ("All", blank) and returns a
     * plain list, so single and multiple office selections share one path.
     */
    private function normalizeOfficeIds($officeId): array
    {
        $officeIds = is_array($officeId) ? $officeId : [$officeId];

        return array_values(array_filter($officeIds, function ($id) {
            return $id !== null && $id !== '' && $id !== 'All';
        }));
    }

    /**
     * Narrows a requested office selection to what the user is allowed to see.
     * An empty selection means "their own school" rather than "every office".
     */
    private function restrictOfficeIds(array $officeIds, ?array $allowedIds): array
    {
        if ($allowedIds === null) {
            return $officeIds;
        }

        if (empty($officeIds)) {
            return $allowedIds;
        }

        return array_values(array_intersect(
            array_map('intval', $officeIds),
            $allowedIds
        ));
    }

    private function reportOfficeScopeIds(Office $office)
    {
        if ((string) $office->id === '1') {
            return Office::where('school_id', $office->school_id ?: 1)
                ->pluck('id')
                ->all();
        }

        return [$office->id];
    }

    private function applyOfficeFilter($query, $officeId)
    {
        $officeIds = $this->normalizeOfficeIds($officeId);
        $allowedIds = $this->allowedReportOfficeIds();

        if ($allowedIds !== null) {
            $officeIds = $this->restrictOfficeIds($officeIds, $allowedIds);

            // Nothing left means every office asked for belongs outside access.
            if (empty($officeIds)) {
                return $query->whereRaw('1 = 0');
            }

            // For a school-scoped user the allowed list is already the whole scope.
            return $query->whereIn('enduser_property.office_id', $officeIds);
        }

        if (empty($officeIds)) {
            return $query;
        }

        return $query->where(function ($q) use ($officeIds) {
            foreach ($officeIds as $officeId) {
                if ((string) $officeId === '1') {
                    $q->orWhereIn('enduser_property.office_id', Office::where('school_id', 1)->pluck('id'));
                } else {
                    $q->orWhere('enduser_property.office_id', $officeId);
                }
            }
        });
    }

    /**
     * Resolve a representative Office model for the report header / filename.
     * Returns the single selected office, or the first of a multi-selection.
     */
    private function resolveSelectedOffice($officeId)
    {
        // Restricted the same way as the data itself, so the header and filename
        // can never name an office the report does not actually cover.
        $officeIds = $this->restrictOfficeIds(
            $this->normalizeOfficeIds($officeId),
            $this->allowedReportOfficeIds()
        );

        if (empty($officeIds)) {
            return null;
        }

        if (count($officeIds) === 1) {
            return Office::find($officeIds[0]);
        }

        return Office::whereIn('id', $officeIds)->first();
    }

    private function getMainSchoolDefaultAccountableName()
    {
        return Office::where('id', 1)->value('office_officer') ?? '';
    }

    private function applyDefaultAccountableName($items, $request)
    {
        $officeId = $request->office_id;
        $isMainSchoolOnly = is_array($officeId)
            ? (count($officeId) === 1 && (string) reset($officeId) === '1')
            : ((string) $officeId === '1');

        if (!$isMainSchoolOnly || ($request->filled('person_accnt') && $request->person_accnt !== 'All')) {
            return $items;
        }

        $accountableName = $this->getMainSchoolDefaultAccountableName();

        if ($accountableName === '') {
            return $items;
        }

        return $items->map(function ($item) use ($accountableName) {
            $item->person_accnt_name = $accountableName;
            $item->accountable_person_name = $accountableName;
            $item->accountable_person = $accountableName;

            return $item;
        });
    }

    public function reportForm() {
        $setting = Setting::firstOrNew(['id' => 1]);
        $user = auth()->user();

        if (in_array($user->role, ['Administrator', 'Supply Officer'])) {
            $office = Office::all();
        }else{
            $office = Office::where('school_id', $user->school_id)->get();
        }

        $uid = auth()->user()->school_id;
        $uoffice = Office::where('school_id', $uid)->first();
        $property = Property::all();
        $category = Category::all();
        if($uoffice){
            $accntables = Accountable::where('off_id', $uoffice->id)->get();
        }else{
            $accntables = Accountable::all();
        }
        return view('reports.report_form', compact('setting', 'uoffice', 'office', 'property', 'accntables', 'category'));
    }

    public function reportFormGenerate() {

    }   
    
    public function selectType() {

    }   

    //SELECT AUTO DISPLAY
    public function genPropType($id)
    {
        $reportType = $id;

        $html = '';

        if (!$reportType) {
            return $html;
        }

        // Start with a fresh query builder (NOT Property::all())
        $query = Property::query();

        if (in_array($reportType, [1, 4])) {
            $query->where('id', 3);
        } elseif (in_array($reportType, [2, 3])) {
            $query->whereIn('id', [1, 2]);
        }else{
            $query->whereIn('id', [1, 2, 3]);
        }

        $properties = $query->get();

        foreach ($properties as $property) {
            $html .= '<option selected value="' . $property->id . '">' 
                    . htmlspecialchars($property->abbreviation) 
                    . ' - ' 
                    . htmlspecialchars($property->property_name) 
                    . '</option>';
        }

        if ($properties->isEmpty()) {
            $html = '<option value="" disabled selected>--- No Property Types Available ---</option>';
        }

        return $html;
    }

    // ==================== generateMix ====================
    public function generateMix($id)
    {
        // Supports single ("5") or multiple ("5,7,9") office selection
        $ids = array_values(array_filter(explode(',', (string) $id), function ($v) {
            return trim($v) !== '';
        }));

        // School-scoped users must not be able to list another school's
        // locations, accountable persons or end users either.
        $allowedIds = $this->allowedReportOfficeIds();

        if ($allowedIds !== null) {
            $ids = array_values(array_intersect(array_map('intval', $ids), $allowedIds));
        }

        $offices = empty($ids) ? collect() : Office::whereIn('id', $ids)->get();

        if ($offices->isEmpty()) {
            return response()->json([
                'selectlocation'    => '<option disabled selected value=""> --- select --- </option>',
                'selectedaccntable' => '<option disabled selected value=""> --- select --- </option>',
                'selectedenduser'   => '<option disabled selected value=""> --- select --- </option>',
                'selectitems'       => '<option value=""> --- select items --- </option><option value="All">All Items</option>'
            ]);
        }

        $label = '';
        $location  = collect();
        $accntable = collect();
        $enduser   = collect();

        foreach ($offices as $office) {
            $officeScopeIds = $this->reportOfficeScopeIds($office);

            $enduserlocation = EnduserProperty::whereIn('office_id', $officeScopeIds)
                ->pluck('location');

            $location = $location->merge(
                Office::where('school_id', $office->school_id ?? 1)
                    ->where('office_code', '0000')
                    ->whereIn('id', $enduserlocation)
                    ->get()
            );

            $accntable = $accntable->merge(
                Accountable::whereIn('off_id', $officeScopeIds)
                    ->where('accnt_role', 1)
                    ->whereNotNull('person_accnt')
                    ->where('person_accnt', '!=', '')
                    ->orderBy('person_accnt')
                    ->get()
            );

            $enduser = $enduser->merge(
                Accountable::whereIn('off_id', $officeScopeIds)
                    ->where('accnt_role', 0)
                    ->whereNotNull('person_accnt')
                    ->where('person_accnt', '!=', '')
                    ->orderBy('person_accnt')
                    ->get()
            );
        }

        // De-duplicate across the selected offices
        $location  = $location->unique('id')->values();
        $accntable = $accntable->unique('id')->values();
        $enduser   = $enduser->unique('id')->values();

        // Build Location
        $selectlocation = '<option disabled value=""> --- select --- </option>';
        $first = true;

        foreach ($location as $loc) {
            $selectlocation .= '<option value="' . $loc->id . '" ' . ($first ? 'selected' : '') . '>'
                . htmlspecialchars($loc->office_name ?? '', ENT_QUOTES, 'UTF-8') . '</option>';

            $first = false;
        }

        // Build Accountable
        $selectedaccntable = '<option disabled value=""> --- select --- </option><option value="All" selected>All</option>';
        $first = false;
        foreach ($accntable as $acc) {
            $selectedaccntable .= '<option value="' . $acc->id . '" ' . ($first ? 'selected' : '') . '>' 
                . htmlspecialchars($acc->person_accnt ?? '', ENT_QUOTES, 'UTF-8')
                . $label . '</option>';
            $first = false;
        }

        // Build End User
        $selectedenduser = '<option disabled selected value=""> --- select --- </option>';
        foreach ($enduser as $user) {
            $selectedenduser .= '<option value="' . $user->id . '">' 
                . htmlspecialchars($user->person_accnt ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
        }

        $selectitems = '<option value=""> --- select items --- </option><option value="All">All Items</option>';

        return response()->json([
            'selectlocation'    => $selectlocation,
            'selectedaccntable' => $selectedaccntable,
            'selectedenduser'   => $selectedenduser,
            'selectitems'       => $selectitems
        ]);
    }

    // ==================== generateItems ====================
    public function generateItems(Request $request)
    {
        try {
            $query = EnduserProperty::query()
                ->where('enduser_property.deleted', 0)
                ->join('items', 'items.id', '=', 'enduser_property.item_id')
                ->leftJoin('accountable as accountable_person', 'accountable_person.id', '=', 'enduser_property.person_accnt')
                ->leftJoin('accountable as end_user', 'end_user.id', '=', 'enduser_property.person_accnt1')
                ->select(
                    'enduser_property.*',
                    'accountable_person.person_accnt as accountable_person',
                    'end_user.person_accnt as end_user',
                    'items.*',
                    'enduser_property.id as pid'
                );

            $query = $this->applyOfficeFilter($query, $request->office_id);

            if ($request->filled('location') && $request->location !== 'All') {
                $query->where('enduser_property.location', $request->location);
            }

            if ($request->filled('categories_id') && $request->categories_id !== 'All') {
                $query->where('enduser_property.categories_id', $request->categories_id);
            }

            if ($request->filled('property_id') && $request->property_id !== 'All') {
                $query->where('enduser_property.property_id', $request->property_id);
            }

            // Safe accountable filter
            if ($request->filled('person_accnt1') && $request->person_accnt1 !== 'All') {
                $query->where('enduser_property.person_accnt1', $request->person_accnt1);
            } elseif ($request->filled('person_accnt') && $request->person_accnt !== 'All') {
                $query->where('enduser_property.person_accnt', $request->person_accnt);
            }

            if ($request->filled('report_type')) {
                if ((string) $request->report_type === '5') {
                    $query->where('enduser_property.remarks', 'Unserviceable');
                } else {
                    $query->where(function ($q) {
                        $q->whereNull('enduser_property.remarks')
                        ->orWhere('enduser_property.remarks', '!=', 'Unserviceable');
                    });
                }

                switch ((string) $request->report_type) {
                    case '1': // RPCPPE
                    case '4': // PAR
                        $query->where('enduser_property.item_cost', '>=', 50000);
                        break;

                    case '2': // RPCSEP
                    case '3': // ICS
                        $query->where('enduser_property.item_cost', '<', 50000);
                        break;

                    case '5': // UNSERVICEABLE
                        break;
                }
            }

            if ($request->filled('date_range') && $request->date_range !== 'All' && trim($request->date_range) !== '') {
                $dateString = trim($request->date_range);
                $cleaned = preg_replace('/\s+/', ' ', $dateString);
                $dates = explode(' - ', $cleaned);

                if (count($dates) === 2) {
                    $start = trim($dates[0]);
                    $end   = trim($dates[1]);

                    try {
                        $startDate = Carbon::createFromFormat('Y-m-d', $start)->format('Y-m-d');
                        $endDate   = Carbon::createFromFormat('Y-m-d', $end)->format('Y-m-d');

                        $query->whereBetween('enduser_property.date_acquired', [$startDate, $endDate]);
                    } catch (\Exception $e) {
                        \Log::warning('Invalid date_range format: ' . $dateString);
                    }
                }
            }

            $query->orderBy('enduser_property.date_acquired', 'desc');

            $items = $this->applyDefaultAccountableName($query->get(), $request);

            $selectitems = '<option value=""> --- select items --- </option>';
            $selectitems .= '<option value="All">All Items</option>';

            foreach ($items as $item) {
                $display = trim(($item->item_name ?? '') . ' ' . ($item->item_descrip ?? ''));

                $accountable = $item->accountable_person ?? 'N/A';
                $enduser     = $item->end_user ?? 'N/A';

                $acquiredDate = $item->date_acquired
                    ? date('Y-m-d', strtotime($item->date_acquired))
                    : 'N/A';

                $selectitems .= '<option value="' . $item->pid . '">'
                    . htmlspecialchars($display)
                    . ' | AP: ' . htmlspecialchars($accountable)
                    . ' | EU: ' . htmlspecialchars($enduser)
                    . ' (Acquired: ' . $acquiredDate . ')'
                    . '</option>';
            }

            return response()->json(['selectitems' => $selectitems]);

        } catch (\Exception $e) {
            \Log::error('generateItems Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            return response()->json([
                'selectitems' => '<option value=""> --- select items --- </option><option value="All">All Items</option>'
            ]);
        }
    }

    /**
     * Format date range for display - Converts YYYY-MM-DD to readable format like "APR 1, 2000 - MAY 31, 2026"
     */
    private function formatDateRangeForDisplay($dateRange)
    {
        if (empty($dateRange) || trim($dateRange) === '') {
            return strtoupper(Carbon::now()->format('M j, Y'));
        }
        
        try {
            // Clean the date string
            $dateRange = trim($dateRange);
            
            // Check if it's a range with space-dash-space format (2000-04-01 - 2026-05-31)
            if (preg_match('/(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})/', $dateRange, $matches)) {
                $start = Carbon::parse($matches[1]);
                $end = Carbon::parse($matches[2]);
                
                // Check if it's actually a single date (same day)
                if ($start->isSameDay($end)) {
                    return strtoupper($start->format('M j, Y'));
                }
                
                // Same month and year: "APR 21 - 28, 2026"
                if ($start->isSameMonth($end) && $start->isSameYear($end)) {
                    return strtoupper($start->format('M j') . ' - ' . $end->format('j, Y'));
                }
                
                // Same year, different months: "APR 21 - MAY 28, 2026"
                if ($start->isSameYear($end)) {
                    return strtoupper($start->format('M j') . ' - ' . $end->format('M j, Y'));
                }
                
                // Different years: "APR 1, 2000 - MAY 31, 2026"
                return strtoupper($start->format('M j, Y') . ' - ' . $end->format('M j, Y'));
            }
            
            // Check for 'to' separator
            if (str_contains($dateRange, ' to ')) {
                $parts = explode(' to ', $dateRange);
                if (count($parts) === 2) {
                    $start = Carbon::parse(trim($parts[0]));
                    $end = Carbon::parse(trim($parts[1]));
                    
                    if ($start->isSameDay($end)) {
                        return strtoupper($start->format('M j, Y'));
                    }
                    if ($start->isSameMonth($end) && $start->isSameYear($end)) {
                        return strtoupper($start->format('M j') . ' - ' . $end->format('j, Y'));
                    }
                    if ($start->isSameYear($end)) {
                        return strtoupper($start->format('M j') . ' - ' . $end->format('M j, Y'));
                    }
                    return strtoupper($start->format('M j, Y') . ' - ' . $end->format('M j, Y'));
                }
            }
            
            // Single date: "2026-05-31"
            return strtoupper(Carbon::parse($dateRange)->format('M j, Y'));
            
        } catch (\Exception $e) {
            \Log::warning('Date format error: ' . $e->getMessage() . ' for input: ' . $dateRange);
            return strtoupper($dateRange);
        }
    }

    /**
     * Calculate Balance Brought Forward (items acquired before the selected date range)
     */
    private function calculateBroughtForward($request, $baseQuery = null)
    {
        // If no date range is selected, no brought forward balance
        if (!$request->filled('date_range') || $request->date_range === 'All' || trim($request->date_range) === '') {
            return 0;
        }
        
        // Parse the date range to get the start date
        $dateString = trim($request->date_range);
        $cleaned = preg_replace('/\s+/', ' ', $dateString);
        $dates = explode(' - ', $cleaned);
        
        if (count($dates) !== 2) {
            return 0;
        }
        
        try {
            $startDate = Carbon::createFromFormat('Y-m-d', trim($dates[0]))->startOfDay();
            
            // Build a fresh query for brought forward items
            $broughtForwardQuery = EnduserProperty::query()
                ->where('enduser_property.deleted', 0);
            
            // Apply all the same filters EXCEPT date range
            $broughtForwardQuery = $this->applyFiltersWithoutDateRange($broughtForwardQuery, $request);
            
            // Add condition for items acquired BEFORE the start date
            $broughtForwardQuery->where('enduser_property.date_acquired', '<', $startDate);
            
            // Get the sum of item costs for brought forward items. item_cost is a
            // varchar, so the separators are stripped in SQL before summing.
            $bforward = $broughtForwardQuery->sum(
                \DB::raw("REPLACE(REPLACE(enduser_property.item_cost, ',', ''), ' ', '') + 0")
            );
            
            return $bforward ?: 0;
            
        } catch (\Exception $e) {
            \Log::error('Brought Forward Calculation Error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Apply all filters except date range (for brought forward calculation)
     */
    private function applyFiltersWithoutDateRange($query, $request)
    {
        $query = $this->applyOfficeFilter($query, $request->office_id);
        
        if ($request->filled('location') && $request->location !== 'All') {
            $query->where('enduser_property.location', $request->location);
        }
        
        if ($request->filled('categories_id') && $request->categories_id !== 'All') {
            $query->where('enduser_property.categories_id', $request->categories_id);
        }
        
        if ($request->filled('property_id') && $request->property_id !== 'All') {
            $query->where('enduser_property.property_id', $request->property_id);
        }
        
        // Accountable filter
        if ($request->filled('person_accnt1') && $request->person_accnt1 !== 'All') {
            $query->where('enduser_property.person_accnt1', $request->person_accnt1);
        } elseif ($request->filled('person_accnt') && $request->person_accnt !== 'All') {
            $query->where('enduser_property.person_accnt', $request->person_accnt);
        }
        
        // Report type filters
        if ($request->filled('report_type')) {
            if ((string) $request->report_type === '5') {
                $query->where('enduser_property.remarks', 'Unserviceable');
            } else {
                $query->where(function ($q) {
                    $q->whereNull('enduser_property.remarks')
                        ->orWhere('enduser_property.remarks', '!=', 'Unserviceable');
                });
            }
            
            switch ((string) $request->report_type) {
                case '1': // RPCPPE
                case '4': // PAR
                    $query->where('enduser_property.item_cost', '>=', 50000);
                    break;
                case '2': // RPCSEP
                case '3': // ICS
                    $query->where('enduser_property.item_cost', '<', 50000);
                    break;
                case '5': // UNSERVICEABLE
                    break;
            }
        }
        
        // Item filter
        $itemIds = $request->input('item_id', []);
        $itemIds = is_array($itemIds) ? $itemIds : [$itemIds];
        $itemIds = array_filter($itemIds, function ($id) {
            return $id !== null && $id !== '';
        });
        
        if (!empty($itemIds) && !in_array('All', $itemIds, true)) {
            $query->whereIn('enduser_property.id', $itemIds);
        }
        
        return $query;
    }

    /**
     * enduser_property stores money and quantities as varchar, and some rows were
     * saved with thousands separators ("5,000"). Multiplying or summing those raw
     * raises "A non-numeric value encountered" and, worse, reads 5,000 as 5, so
     * every amount coming out of that table is parsed through here.
     */
    public static function toAmount($value): float
    {
        return (float) str_replace([',', ' '], '', (string) $value);
    }

    /**
     * Calculate total cost for current items
     */
    private function calculateTotalCost($items)
    {
        return $items->sum(function ($item) {
            return self::toAmount($item->item_cost ?? 0);
        });
    }

    /**
     * Get category breakdown for detailed reporting
     */
    private function getCategoryBreakdown($items)
    {
        return $items->groupBy('cat_name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_cost' => $group->sum(function ($item) {
                    return self::toAmount($item->item_cost ?? 0);
                }),
                'total_quantity' => $group->sum(function ($item) {
                    return self::toAmount($item->quantity ?? 0);
                })
            ];
        });
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type'   => 'nullable|in:1,2,3,4,5',
            'format'        => 'nullable|in:pdf,excel',
            'properties_id' => 'nullable|array',
            'office_id'     => 'nullable', // scalar (single) or array (multiple offices)
            'office_id.*'   => 'nullable',
            'location'      => 'nullable',
            'categories_id' => 'nullable',
            'property_id'   => 'nullable',
            'date_range'    => 'nullable',
            'person_accnt'  => 'nullable',
            'person_accnt1' => 'nullable',
            'item_id'       => 'nullable|array',
            'columns'       => 'nullable|array',
            'balance_bforward' => 'nullable|boolean',
            'eachpage_subtotal' => 'nullable|boolean',
            'grand_total' => 'nullable|boolean',
            'eachpage_header' => 'nullable|boolean',
            'eachpage_footer' => 'nullable|boolean',
        ]);

        $reportType  = $request->report_type ?: 1;
        $reportTitle = $this->getReportTitle($reportType);

        $query = EnduserProperty::query()
            ->where('enduser_property.deleted', 0)
            ->leftJoin('items', 'items.id', '=', 'enduser_property.item_id')
            ->leftJoin('property', 'property.id', '=', 'enduser_property.property_id')
            ->leftJoin('categories', 'categories.id', '=', 'enduser_property.categories_id')
            ->leftJoin('accountable as accountable_person', 'accountable_person.id', '=', 'enduser_property.person_accnt')
            ->leftJoin('accountable as end_user', 'end_user.id', '=', 'enduser_property.person_accnt1')
            ->leftJoin('offices', 'offices.id', '=', 'enduser_property.office_id')
            ->leftJoin('offices as locations', 'locations.id', '=', 'enduser_property.location')
            ->leftJoin('units', 'units.id', '=', 'enduser_property.unit_id')
            ->select(
                'enduser_property.*',
                'units.unit_name',
                'accountable_person.person_accnt as accountable_person_name',
                'accountable_person.accnt_role as accountable_role',
                'end_user.person_accnt as end_user_name',
                'items.*',
                'property.property_name',
                'property.abbreviation',
                'categories.cat_name',
                'categories.cat_code',
                'offices.office_name',
                'offices.office_abbr',
                'offices.school_id',
                'locations.office_name as itemlocated',
                'enduser_property.id as pid'
            );

        $query = $this->applyReportFilters($query, $request);
        $items = $this->applyDefaultAccountableName($query->get(), $request);

        // Calculate Balance Brought Forward
        $bforward = $this->calculateBroughtForward($request);
        
        // Calculate Total Cost of current items
        $totalCost = $this->calculateTotalCost($items);
        
        // Calculate Grand Total
        $grandTotal = $bforward + $totalCost;
        
        // Get category breakdown
        $categoryBreakdown = $this->getCategoryBreakdown($items);

        $office = $this->resolveSelectedOffice($request->office_id);
        $selectedColumns = $request->input('columns', []);

        // Format the date range for display
        $hasDateFilter = $request->filled('date_range') && $request->date_range !== 'All' && trim($request->date_range) !== '';
        $formattedDateRange = '';
        
        if ($hasDateFilter) {
            $formattedDateRange = $this->formatDateRangeForDisplay($request->date_range);
        }

        // Get checkbox values from form (using boolean method)
        $showBalanceForward = $request->boolean('balance_bforward');
        $showGrandTotal = $request->boolean('grand_total');
        $showEachpageSubtotal = $request->boolean('eachpage_subtotal');
        $showEachpageHeader = $request->boolean('eachpage_header');
        $showEachpageFooter = $request->boolean('eachpage_footer');

        $data = [
            'title'            => $reportTitle,
            'report_type'      => $reportType,
            'items'            => $items,
            'office'           => $office,
            'filters'          => $this->getFilterSummary($request),
            'selected_columns' => $selectedColumns,
            'generated_date'   => Carbon::now()->format('F d, Y h:i A'),
            'report_number'    => $this->generateReportNumber($reportType),
            
            // Financial calculations
            'bforward'         => $bforward,
            'total_cost'       => $totalCost,
            'grand_total'      => $grandTotal,
            'has_date_filter'  => $hasDateFilter,
            'date_range_display' => $formattedDateRange,
            'raw_date_range'   => $request->date_range,
            
            // Checkbox options from form
            'show_balance_forward' => $showBalanceForward,
            'show_grand_total' => $showGrandTotal,
            'show_eachpage_subtotal' => $showEachpageSubtotal,
            'show_eachpage_header' => $showEachpageHeader,
            'show_eachpage_footer' => $showEachpageFooter,
            
            // Category breakdown
            'category_breakdown' => $categoryBreakdown,
            
            // Additional stats
            'total_items_count' => $items->count(),
            'total_quantity'    => $items->sum('quantity'),
        ];

        $filename = $this->getReportFilename($reportType, $office);

        if ($request->input('format') === 'excel') {
            return $this->streamExcelReport((int) $reportType, $data, $filename);
        }

        $view = $this->getReportView($reportType);

        // Fix orientation logic (explicit and readable)
        $orientation = in_array($reportType, [3, 4, 5]) ? 'Portrait' : 'Landscape';

        $pdf = Snappy::loadView($view, $data)
            ->setOption('page-size', 'Tabloid') // better than setPaper for wkhtmltopdf
            ->setOption('orientation', $orientation)
            ->setOption('disable-smart-shrinking', true)
            ->setOption('no-stop-slow-scripts', true)
            ->setOption('quiet', true)
            ->setOption('margin-top', '5mm')
            ->setOption('margin-bottom', '5mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    // Build and stream the .xlsx workbook that mirrors the PDF report's header/footer content
    private function streamExcelReport(int $reportType, array $data, string $filename)
    {
        $spreadsheet = (new \App\Services\Reports\ReportExcelBuilder())->build($reportType, $data);
        $writer = new Xlsx($spreadsheet);
        $filename = preg_replace('/\.pdf$/i', '', $filename) . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    
    // Helper method to apply all filters
    private function applyReportFilters($query, $request)
    {
        $query = $this->applyOfficeFilter($query, $request->office_id);

        if ($request->filled('location') && $request->location !== 'All') {
            $query->where('enduser_property.location', $request->location);
        }

        if ($request->filled('categories_id') && $request->categories_id !== 'All') {
            $query->where('enduser_property.categories_id', $request->categories_id);
        }

        if ($request->filled('property_id') && $request->property_id !== 'All') {
            $query->where('enduser_property.property_id', $request->property_id);
        }

        // Same accountable behavior as generateItems
        if ($request->filled('person_accnt1') && $request->person_accnt1 !== 'All') {
            $query->where('enduser_property.person_accnt1', $request->person_accnt1);
        } elseif ($request->filled('person_accnt') && $request->person_accnt !== 'All') {
            $query->where('enduser_property.person_accnt', $request->person_accnt);
        }

        // Same report type logic as generateItems
        if ($request->filled('report_type')) {
            if ((string) $request->report_type === '5') {
                $query->where('enduser_property.remarks', 'Unserviceable');
            } else {
                $query->where(function ($q) {
                    $q->whereNull('enduser_property.remarks')
                    ->orWhere('enduser_property.remarks', '!=', 'Unserviceable');
                });
            }

            switch ((string) $request->report_type) {
                case '1': // RPCPPE
                case '4': // PAR
                    $query->where('enduser_property.item_cost', '>=', 50000);
                    break;

                case '2': // RPCSEP
                case '3': // ICS
                    $query->where('enduser_property.item_cost', '<', 50000);
                    break;

                case '5': // UNSERVICEABLE
                    break;
            }
        }

        if ($request->filled('date_range') && $request->date_range !== 'All' && trim($request->date_range) !== '') {
            $dateString = trim($request->date_range);
            $cleaned = preg_replace('/\s+/', ' ', $dateString);
            $dates = explode(' - ', $cleaned);

            if (count($dates) === 2) {
                $start = trim($dates[0]);
                $end   = trim($dates[1]);

                try {
                    $startDate = Carbon::createFromFormat('Y-m-d', $start)->format('Y-m-d');
                    $endDate   = Carbon::createFromFormat('Y-m-d', $end)->format('Y-m-d');

                    $query->whereBetween('enduser_property.date_acquired', [$startDate, $endDate]);
                } catch (\Exception $e) {
                    \Log::warning('Invalid date_range format: ' . $dateString);
                }
            }
        }

        // Safe item filter, supports item_id[] = All
        $itemIds = $request->input('item_id', []);
        $itemIds = is_array($itemIds) ? $itemIds : [$itemIds];
        $itemIds = array_filter($itemIds, function ($id) {
            return $id !== null && $id !== '';
        });

        if (!empty($itemIds) && !in_array('All', $itemIds, true)) {
            $query->whereIn('enduser_property.id', $itemIds);
        }

        $query->orderBy('enduser_property.date_acquired', 'desc');

        return $query;
    }

    // Get report title
    private function getReportTitle($reportType)
    {
        $titles = [
            1 => 'REPORT ON PROPERTY, PLANT AND EQUIPMENT (RPCPPE)',
            2 => 'REPORT ON SEMI-EXPENDABLE PROPERTY (RPCSEP)',
            3 => 'INVENTORY CUSTODIAN SLIP (ICS)',
            4 => 'PROPERTY ACKNOWLEDGMENT RECEIPT (PAR)',
            5 => 'REPORT ON UNSERVICEABLE PROPERTY'
        ];
        
        return $titles[$reportType] ?? 'PROPERTY REPORT';
    }

    // Get report view based on type
    private function getReportView($reportType)
    {
        $views = [
            1 => 'reports.rpcppe_report',
            2 => 'reports.rpcsep_report',
            3 => 'reports.ics_report',
            4 => 'reports.par_report',
            5 => 'reports.unserviceable_report'
        ];
        
        return $views[$reportType] ?? 'reports.default_report';
    }

    // Generate report filename
    private function getReportFilename($reportType, $office)
    {
        $typeNames = [
            1 => 'RPCPPE',
            2 => 'RPCSEP',
            3 => 'ICS',
            4 => 'PAR',
            5 => 'UNSERVICEABLE'
        ];
        
        $typeName = $typeNames[$reportType] ?? 'REPORT';
        $officeName = $office ? str_replace(' ', '_', $office->office_abbr ?? $office->office_name ?? 'OFFICE') : 'ALL';
        $date = Carbon::now()->format('Ymd_His');
        
        return "{$typeName}_{$officeName}_{$date}.pdf";
    }

    // Generate report number
    private function generateReportNumber($reportType)
    {
        $prefixes = [
            1 => 'RPCPPE',
            2 => 'RPCSEP',
            3 => 'ICS',
            4 => 'PAR',
            5 => 'UNSERV'
        ];
        
        $prefix = $prefixes[$reportType] ?? 'RPT';
        $year = Carbon::now()->format('Y');
        $sequence = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$sequence}";
    }

    // Get filter summary for display
    private function getFilterSummary($request)
    {
        $summary = [];
        
        if ($request->filled('date_range') && $request->date_range !== 'All') {
            // Use formatted date range
            $summary['Date Range'] = $this->formatDateRangeForDisplay($request->date_range);
        }
        
        if ($request->filled('categories_id') && $request->categories_id !== 'All') {
            $category = Category::find($request->categories_id);
            $summary['Category'] = $category ? $category->cat_name : $request->categories_id;
        }
        
        if ($request->filled('property_id') && $request->property_id !== 'All') {
            // The account picker submits properties.code (for example "030"),
            // not the primary key of the separate property-type table. Codes can
            // repeat across categories/property types, so use the other selected
            // filters to resolve the exact account title.
            $accountQuery = Properties::where('code', $request->property_id);

            if ($request->filled('categories_id') && $request->categories_id !== 'All') {
                $accountQuery->where('category_id', $request->categories_id);
            }

            $propertyTypeIds = array_values(array_filter(
                (array) $request->input('properties_id', []),
                function ($id) {
                    return $id !== null && $id !== '' && $id !== 'All';
                }
            ));

            if (!empty($propertyTypeIds)) {
                $accountQuery->whereIn('property_id', $propertyTypeIds);
            }

            // A shared code carries a different title per property type, and the
            // account picker labels it with the first selected type's title, so
            // resolve it the same way here.
            $typeOrder = array_flip($propertyTypeIds);

            $accountTitle = $accountQuery->get(['property_id', 'account_title'])
                ->sortBy(function ($account) use ($typeOrder) {
                    return $typeOrder[$account->property_id] ?? count($typeOrder);
                })
                ->value('account_title');

            $summary['Account Title'] = $accountTitle ?: $request->property_id;
        }

        // School or Office supports a single id or a multi-selection.
        if ($request->filled('office_id')) {
            $officeIds = is_array($request->office_id) ? $request->office_id : [$request->office_id];
            $officeIds = array_values(array_filter($officeIds, function ($id) {
                return $id !== null && $id !== '' && $id !== 'All';
            }));

            if (!empty($officeIds)) {
                $officeNames = Office::whereIn('id', $officeIds)->pluck('office_name')->toArray();
                if (!empty($officeNames)) {
                    $summary['School or Office'] = implode(', ', $officeNames);
                }
            }
        }

        if ($request->filled('location') && $request->location !== 'All') {
            $location = Office::find($request->location);
            $summary['Location'] = $location ? $location->office_name : $request->location;
        }
        
        if ($request->filled('person_accnt') && $request->person_accnt !== 'All') {
            $accountable = Accountable::find($request->person_accnt);
            $summary['Accountable Person'] = $accountable ? $accountable->person_accnt : $request->person_accnt;
        }
        
        if ($request->filled('person_accnt1') && $request->person_accnt1 !== 'All') {
            $endUser = Accountable::find($request->person_accnt1);
            $summary['End User'] = $endUser ? $endUser->person_accnt : $request->person_accnt1;
        }
        
        return $summary;
    }

}


