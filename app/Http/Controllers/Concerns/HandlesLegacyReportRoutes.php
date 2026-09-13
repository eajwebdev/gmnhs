<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * The separate RPCPPE / RPCSEP / ICS / PAR / Unserviceable report screens were
 * merged into the single Reports page (reportForm + generateReport). Their old
 * URLs are still registered, so they are kept working here: option pages open
 * the Reports page with the matching type selected, and the old generate and
 * item-list endpoints translate their inputs and run the current report engine.
 */
trait HandlesLegacyReportRoutes
{
    private const LEGACY_REPORT_TYPES = [
        'rpcppe' => 1,
        'rpcsep' => 2,
        'ics' => 3,
        'par' => 4,
        'unserviceable' => 5,
    ];

    // Option pages ---------------------------------------------------------

    public function reportOption($id)
    {
        return $this->openReportForm(in_array((int) $id, [1, 2], true) ? (int) $id : null);
    }

    public function rpcppeOption()
    {
        return $this->openReportForm(self::LEGACY_REPORT_TYPES['rpcppe']);
    }

    public function rpcsepOption()
    {
        return $this->openReportForm(self::LEGACY_REPORT_TYPES['rpcsep']);
    }

    public function icsOption()
    {
        return $this->openReportForm(self::LEGACY_REPORT_TYPES['ics']);
    }

    public function parOption()
    {
        return $this->openReportForm(self::LEGACY_REPORT_TYPES['par']);
    }

    public function unserviceForm()
    {
        return $this->openReportForm(self::LEGACY_REPORT_TYPES['unserviceable']);
    }

    // Report generation ----------------------------------------------------

    public function reportOptionView(Request $request)
    {
        $type = in_array((int) $request->input('repcat'), [1, 2], true) ? (int) $request->input('repcat') : 1;

        return $this->generateLegacyReport($request, $type);
    }

    public function rpcppeOptionReportGen(Request $request)
    {
        return $this->generateLegacyReport($request, self::LEGACY_REPORT_TYPES['rpcppe']);
    }

    public function rpcsepOptionReportGen(Request $request)
    {
        return $this->generateLegacyReport($request, self::LEGACY_REPORT_TYPES['rpcsep']);
    }

    public function icsOptionReportGen(Request $request)
    {
        return $this->generateLegacyReport($request, self::LEGACY_REPORT_TYPES['ics']);
    }

    public function parOptionReportGen(Request $request)
    {
        return $this->generateLegacyReport($request, self::LEGACY_REPORT_TYPES['par']);
    }

    public function unserviceReport(Request $request)
    {
        return $this->generateLegacyReport($request, self::LEGACY_REPORT_TYPES['unserviceable']);
    }

    // Item lists (AJAX) ----------------------------------------------------

    public function icsgenOption(Request $request)
    {
        return $this->legacyItemList($request, self::LEGACY_REPORT_TYPES['ics']);
    }

    public function pargenOption(Request $request)
    {
        return $this->legacyItemList($request, self::LEGACY_REPORT_TYPES['par']);
    }

    public function genOptionUnserv(Request $request)
    {
        return $this->legacyItemList($request, self::LEGACY_REPORT_TYPES['unserviceable']);
    }

    public function allgenOption(Request $request)
    {
        $type = (int) $request->input('report_type');

        return $this->legacyItemList($request, in_array($type, self::LEGACY_REPORT_TYPES, true) ? $type : null);
    }

    /**
     * Items issued to one end user, as select options.
     */
    public function displayItem(Request $request, $enduserId)
    {
        $request->merge(['person_accnt1' => $enduserId]);

        return $this->legacyItemList($request, null);
    }

    // Helpers --------------------------------------------------------------

    private function openReportForm(?int $type)
    {
        return redirect()->route('reportForm', $type ? ['type' => $type] : []);
    }

    private function generateLegacyReport(Request $request, int $type)
    {
        $this->translateLegacyReportInput($request, $type);

        return $this->generateReport($request);
    }

    private function legacyItemList(Request $request, ?int $type)
    {
        $this->translateLegacyReportInput($request, $type);

        return $this->generateItems($request);
    }

    /**
     * Map the old form field names onto the ones generateReport/generateItems read.
     */
    private function translateLegacyReportInput(Request $request, ?int $type): void
    {
        $input = [];

        if ($type !== null) {
            $input['report_type'] = $type;
        }

        if (!$request->filled('format')) {
            $input['format'] = strtoupper((string) $request->input('file_type')) === 'EXCEL' ? 'excel' : 'pdf';
        }

        if (!$request->filled('date_range') && $request->filled('start_date_acquired') && $request->filled('end_date_acquired')) {
            $input['date_range'] = $request->input('start_date_acquired').' - '.$request->input('end_date_acquired');
        }

        if ($request->has('properties_id') && !is_array($request->input('properties_id'))) {
            $input['properties_id'] = array_filter([(string) $request->input('properties_id')], 'strlen');
        }

        if ($request->has('item_id') && !is_array($request->input('item_id'))) {
            $input['item_id'] = array_filter([(string) $request->input('item_id')], 'strlen');
        }

        if (!$request->has('columns')) {
            $input['columns'] = array_keys(array_filter([
                'location' => $request->boolean('locationcolumn'),
                'serial_number' => $request->boolean('serial'),
                'date_acquired' => $request->boolean('acquired'),
            ]));
        }

        $request->merge($input);
    }
}
