<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds an .xlsx workbook that mirrors the content of the PDF report views
 * (reports/rpcppe_report, rpcsep_report, ics_report, par_report,
 * unserviceable_report) so the Excel output carries the same header and
 * footer information as the generated PDF.
 */
class ReportExcelBuilder
{
    /**
     * School logo used by the RPCPPE / RPCSEP reports. Appendix labels and
     * report titles are written as text rows below it.
     */
    private const RPC_LETTERHEAD = 'logo.png';

    public function build(int $reportType, array $data): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');

        // Hide the default Excel view gridlines so only our explicit table
        // borders show - otherwise every empty cell around the header/footer
        // renders as a distracting grid box.
        $sheet->setShowGridlines(false);

        // Center the report on the printed page, matching the PDF's centered layout
        $sheet->getPageSetup()->setHorizontalCentered(true)->setFitToWidth(1)->setFitToHeight(0);

        switch ($reportType) {
            case 2:
                $this->buildRpcppeRpcsep($sheet, $data, self::RPC_LETTERHEAD, 'REPORT ON THE PHYSICAL COUNT OF SEMI-EXPENDABLE PROPERTY');
                break;
            case 3:
                $this->buildIcs($sheet, $data);
                break;
            case 4:
                $this->buildPar($sheet, $data);
                break;
            case 5:
                $this->buildUnserviceable($sheet, $data);
                break;
            case 1:
            default:
                $this->buildRpcppeRpcsep($sheet, $data, self::RPC_LETTERHEAD, 'REPORT ON THE PHYSICAL COUNT OF PROPERTY, PLANT AND EQUIPMENT');
                break;
        }

        $sheet->setSelectedCell('A1');

        return $spreadsheet;
    }

    /**
     * enduser_property stores money and quantities as varchar, and some rows were
     * saved with thousands separators ("5,000"), which a plain cast reads as 5.
     */
    private function toAmount($value): float
    {
        return (float) str_replace([',', ' '], '', (string) $value);
    }

    // =====================================================================
    // RPCPPE / RPCSEP (report_type 1 & 2) - identical layout, different logo
    // =====================================================================
    private function buildRpcppeRpcsep(Worksheet $sheet, array $data, string $logo, string $reportTitle): void
    {
        $items = $data['items'] ?? collect();
        $filters = $data['filters'] ?? [];
        $selectedColumns = $data['selected_columns'] ?? [];
        $locationOn = in_array('location', $selectedColumns) || in_array('itemlocated', $selectedColumns);
        $serialOn = in_array('serial_number', $selectedColumns) || in_array('serial', $selectedColumns);
        $acquiredOn = in_array('date_acquired', $selectedColumns);

        $columns = [
            ['ARTICLE', 22],
            ['DESCRIPTION', 38],
            ['PROPERTY NO.', 18],
            ['UNIT OF MEASURE', 14],
            ['UNIT VALUE', 13],
            ['QTY PER PROPERTY CARD', 11],
            ['TOTAL COST', 13],
            ['QTY PER PHYSICAL COUNT', 11],
            ['SHORTAGE/OVERAGE QTY', 11],
            ['SHORTAGE/OVERAGE VALUE', 13],
            ['REMARKS', 16],
            ['WHEREABOUT', 16],
        ];
        if ($locationOn) $columns[] = ['LOCATION', 16];
        if ($serialOn) $columns[] = ['SERIAL', 20];
        if ($acquiredOn) $columns[] = ['DATE ACQUIRED', 14];

        [$headers, $widths] = $this->splitColumns($columns);
        $lastCol = count($headers);
        $this->setColumnWidths($sheet, $widths);

        $row = $this->writeLetterheadImage($sheet, $logo, $lastCol);

        $categoryLabel = $filters['Category'] ?? 'All';
        $categoryDisplay = ($categoryLabel === 'All' || empty($categoryLabel)) ? 'ALL CATEGORIES' : $categoryLabel;
        $officeName = $items->first()->office_name ?? '';
        $displayDate = $data['date_range_display'] ?? strtoupper(Carbon::now()->format('M j, Y'));

        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'Appendix 73', ['italic' => true, 'size' => 10], Alignment::HORIZONTAL_RIGHT);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, $reportTitle, ['bold' => true, 'size' => 13]);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, $officeName, ['bold' => true, 'size' => 11]);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, $categoryDisplay, ['underline' => true]);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, '(Type of Property, Plant and Equipment)', ['italic' => true, 'size' => 9]);
        // Each line only appears when that filter was narrowed - the controller omits it for "All".
        // School/Office and Location print bare; only the account title carries a label.
        $filterLines = [
            ['Account Title: ', $filters['Account Title'] ?? ''],
            ['', $filters['School or Office'] ?? ''],
            ['', $filters['Location'] ?? ''],
        ];
        foreach ($filterLines as [$label, $value]) {
            if ($value !== '') {
                $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, $label . $value, ['underline' => true]);
            }
        }
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'As at ' . $displayDate . '.', []);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'Fund Cluster : ________________________________', [], Alignment::HORIZONTAL_LEFT);
        $row = $this->mergeAndWrite(
            $sheet,
            $row,
            1,
            $lastCol,
            'For which ALEXANDRA M. VILLAROSA, Gil Montilla National High School, Sipalay City of GIL MONTILLA NATIONAL HIGH SCHOOL, is accountable, having assumed such accountability on August 16, 2018.',
            [],
            Alignment::HORIZONTAL_LEFT,
            true
        );
        $row++;

        $row = $this->writeTableHeaderRow($sheet, $row, $headers);

        $showBalanceForward = $data['show_balance_forward'] ?? false;
        $hasDateFilter = $data['has_date_filter'] ?? false;
        if ($showBalanceForward && $hasDateFilter) {
            $row = $this->writeTotalRow($sheet, $row, $lastCol, 6, 'Balance Brought Forward:', $data['bforward'] ?? 0);
        }

        $dataStartRow = $row;
        foreach ($items as $item) {
            // item_cost/qty are varchar and some rows carry thousands separators,
            // which a plain (float) cast would read as 5 instead of 5,000.
            $qty = $this->toAmount($item->quantity ?? $item->qty ?? 1);
            $unitCost = $this->toAmount($item->item_cost ?? 0);
            $totalCost = $qty * $unitCost;

            $col = 1;
            $sheet->setCellValue([$col++, $row], strtoupper($item->item_name ?? ''));
            $sheet->setCellValue([$col++, $row], strtoupper($item->item_descrip ?? ''));
            $sheet->setCellValue([$col++, $row], strtoupper($item->property_no_generated ?? $item->property_no ?? ''));
            $sheet->setCellValue([$col++, $row], strtoupper($item->unit_of_measure ?? $item->unit_name ?? ''));
            $this->setMoney($sheet, $col++, $row, $unitCost);
            $sheet->setCellValue([$col++, $row], $qty);
            $this->setMoney($sheet, $col++, $row, $totalCost);
            $sheet->setCellValue([$col++, $row], $qty);
            $sheet->setCellValue([$col++, $row], $qty);
            $this->setMoney($sheet, $col++, $row, $totalCost);
            $sheet->setCellValue([$col++, $row], strtoupper($item->remarks ?? ''));
            $sheet->setCellValue([$col++, $row], $item->office_name ?? $item->whereabout ?? '');
            if ($locationOn) $sheet->setCellValue([$col++, $row], $item->itemlocated ?? '');
            if ($serialOn) $sheet->setCellValue([$col++, $row], $item->serial_number ?? '');
            if ($acquiredOn) {
                $dateAcquired = $item->date_acquired ?? '';
                $sheet->setCellValue([$col++, $row], $dateAcquired ? strtoupper(Carbon::parse($dateAcquired)->format('M j, Y')) : '');
            }

            $this->wrapRow($sheet, $row, $lastCol);
            $this->borderRow($sheet, $row, $lastCol);
            $row++;
        }

        if ($items->isEmpty()) {
            $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'No data available for the selected filters.', []);
            $row++;
        }

        $row = $this->writeTotalRow($sheet, $row, $lastCol, 6, 'TOTAL:', $data['total_cost'] ?? 0);

        if ($data['show_grand_total'] ?? false) {
            $row = $this->writeTotalRow($sheet, $row, $lastCol, 6, 'GRAND TOTAL:', $data['grand_total'] ?? 0);
        }

        $row++;
        $this->writeSignatureFooter($sheet, $row, $lastCol, [
            ['label' => 'Certified Correct by:', 'name' => 'BENJAMIN R. DELA TORRE', 'title' => 'Administrative Officer / Supply Officer'],
            ['label' => 'Approved by:', 'name' => 'ALEXANDRA M. VILLAROSA', 'title' => 'School Principal'],
            ['label' => 'Verified by', 'name' => 'CAMILLE A. NAVARRETE', 'title' => 'Internal Audit Representative'],
        ]);

        $sheet->freezePane('A' . $dataStartRow);
    }

    // =====================================================================
    // ICS (report_type 3)
    // =====================================================================
    private function buildIcs(Worksheet $sheet, array $data): void
    {
        $items = $data['items'] ?? collect();
        $selectedColumns = $data['selected_columns'] ?? [];
        $locationOn = in_array('location', $selectedColumns) || in_array('itemlocated', $selectedColumns);

        $columns = [
            ['NO', 5],
            ['QTY', 7],
            ['UNIT', 10],
            ['DESCRIPTION', 42],
            ['UNIT COST', 12],
            ['TOTAL COST', 12],
            ['DATE ACQUIRED', 14],
            ['INVENTORY ITEM NO.', 18],
        ];
        if ($locationOn) $columns[] = ['LOCATION', 16];
        $columns[] = ['ESTIMATED USEFUL LIFE', 16];

        [$headers, $widths] = $this->splitColumns($columns);
        $lastCol = count($headers);
        $this->setColumnWidths($sheet, $widths);

        $row = $this->writeLetterheadImage($sheet, 'logo.png', $lastCol);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'INVENTORY CUSTODIAN SLIP', ['bold' => true, 'size' => 13]);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'ICS No. _________________', [], Alignment::HORIZONTAL_RIGHT);
        $row++;

        $row = $this->writeTableHeaderRow($sheet, $row, $headers);

        $dataStartRow = $row;
        $no = 1;
        $totalCost = 0;
        foreach ($items as $item) {
            $qty = $item->qty ?? 0;
            $unitCost = (float) str_replace(',', '', $item->item_cost ?? 0);
            $lineTotal = (float) str_replace(',', '', $item->total_cost ?? ($qty * $unitCost));
            $totalCost += $lineTotal;

            $col = 1;
            $sheet->setCellValue([$col++, $row], $no++);
            $sheet->setCellValue([$col++, $row], $qty);
            $sheet->setCellValue([$col++, $row], $item->unit_name ?? '');
            $this->setDescriptionRichText($sheet, $col++, $row, $item->item_name ?? '', $item->item_descrip ?? '', $item->item_model ?? '', $item->serial_number ?? '');
            $this->setMoney($sheet, $col++, $row, $unitCost);
            $this->setMoney($sheet, $col++, $row, $lineTotal);
            $dateAcquired = $item->date_acquired ?? '';
            $sheet->setCellValue([$col++, $row], $dateAcquired ? Carbon::parse($dateAcquired)->format('M j, Y') : '');
            $sheet->setCellValue([$col++, $row], $item->property_no_generated ?? '');
            if ($locationOn) $sheet->setCellValue([$col++, $row], $item->itemlocated ?? '');
            $sheet->setCellValue([$col++, $row], '');

            $this->wrapRow($sheet, $row, $lastCol);
            $this->borderRow($sheet, $row, $lastCol);
            $sheet->getRowDimension($row)->setRowHeight(48);
            $row++;
        }

        if ($items->isEmpty()) {
            $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'No data available for the selected filters.', []);
            $row++;
        }

        $labelSpan = $locationOn ? 5 : 4;
        $row = $this->writeTotalRow($sheet, $row, $lastCol, $labelSpan, 'TOTAL:', $data['total_cost'] ?? $totalCost);

        if ($data['show_grand_total'] ?? false) {
            $row = $this->writeTotalRow($sheet, $row, $lastCol, $labelSpan, 'GRAND TOTAL:', $data['grand_total'] ?? $totalCost);
        }

        $first = $items->first();
        $accountableName = $first->person_accnt_name ?? $first->person_accnt ?? '';
        $endUserName = $first->end_user_name ?? '';
        $officeName = $first->office_name ?? '';

        $row++;
        $this->writeReceivedIssuedFooter($sheet, $row, $lastCol, $accountableName, $endUserName, $officeName);

        $sheet->freezePane('A' . $dataStartRow);
    }

    // =====================================================================
    // PAR (report_type 4)
    // =====================================================================
    private function buildPar(Worksheet $sheet, array $data): void
    {
        $items = $data['items'] ?? collect();
        $selectedColumns = $data['selected_columns'] ?? [];
        $locationOn = in_array('location', $selectedColumns) || in_array('itemlocated', $selectedColumns);

        $columns = [
            ['NO', 5],
            ['QTY', 7],
            ['UNIT', 10],
            ['DESCRIPTION', 42],
            ['PROPERTY NO.', 18],
        ];
        if ($locationOn) $columns[] = ['LOCATION', 16];
        $columns[] = ['DATE ACQUIRED', 14];
        $columns[] = ['AMOUNT', 14];

        [$headers, $widths] = $this->splitColumns($columns);
        $lastCol = count($headers);
        $this->setColumnWidths($sheet, $widths);

        $row = $this->writeLetterheadImage($sheet, 'logo.png', $lastCol);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'PROPERTY ACKNOWLEDGEMENT RECEIPT', ['bold' => true, 'size' => 13]);

        $officeName = $items->first()->office_name ?? '';
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'Entity Name: ' . $officeName, ['bold' => true, 'size' => 10], Alignment::HORIZONTAL_LEFT);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'Fund Cluster: __________________________________________________  PAR No.: _______________________', ['bold' => true, 'size' => 10], Alignment::HORIZONTAL_LEFT);
        $row++;

        $row = $this->writeTableHeaderRow($sheet, $row, $headers);

        $dataStartRow = $row;
        $no = 1;
        $grandTotal = 0;
        foreach ($items as $item) {
            $qty = $item->qty ?? 0;
            $cost = (float) str_replace(',', '', $item->item_cost ?? 0);
            $lineTotal = $qty * $cost;
            $grandTotal += $lineTotal;

            $col = 1;
            $sheet->setCellValue([$col++, $row], $no++);
            $sheet->setCellValue([$col++, $row], $qty);
            $sheet->setCellValue([$col++, $row], $item->unit_name ?? '');
            $this->setDescriptionRichText($sheet, $col++, $row, $item->item_name ?? '', $item->item_descrip ?? '', $item->item_model ?? '', $item->serial_number ?? '');
            $sheet->setCellValue([$col++, $row], $item->property_no_generated ?? '');
            if ($locationOn) $sheet->setCellValue([$col++, $row], $item->itemlocated ?? '');
            $sheet->setCellValue([$col++, $row], $item->date_acquired ?? '');
            $this->setMoney($sheet, $col++, $row, $cost);

            $this->wrapRow($sheet, $row, $lastCol);
            $this->borderRow($sheet, $row, $lastCol);
            $sheet->getRowDimension($row)->setRowHeight(48);
            $row++;
        }

        if ($items->isEmpty()) {
            $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'No items found', []);
            $row++;
        }

        $labelSpan = $lastCol - 1;
        $row = $this->writeTotalRow($sheet, $row, $lastCol, $labelSpan, 'Grand Total:', $data['grand_total'] ?? $grandTotal);
        $row = $this->mergeAndWrite($sheet, $row, 1, $labelSpan, 'Supplier:', ['bold' => true], Alignment::HORIZONTAL_RIGHT);
        $row++;

        $first = $items->first();
        $accountableName = $first->person_accnt_name ?? '';
        $endUserName = $first->end_user_name ?? '';
        $officeName = $first->office_name ?? '';

        $this->writeReceivedIssuedFooter($sheet, $row, $lastCol, $accountableName, $endUserName, $officeName);

        $sheet->freezePane('A' . $dataStartRow);
    }

    // =====================================================================
    // Unserviceable Property (report_type 5)
    // =====================================================================
    private function buildUnserviceable(Worksheet $sheet, array $data): void
    {
        $items = $data['items'] ?? collect();
        $selectedColumns = $data['selected_columns'] ?? [];
        $serialOn = in_array('serial_number', $selectedColumns) || in_array('serial', $selectedColumns);

        $columns = [
            ['DATE ACQUIRED', 14],
            ['PARTICULARS / ARTICLE', 38],
            ['PROPERTY NO.', 18],
            ['QTY', 7],
            ['UNIT COST', 12],
            ['TOTAL COST', 12],
            ['REMARKS', 18],
        ];
        if ($serialOn) {
            $columns[] = ['SERIAL NUMBER', 18];
            $columns[] = ['ACCOUNTABLE PERSON', 22];
        }

        [$headers, $widths] = $this->splitColumns($columns);
        $lastCol = count($headers);
        $this->setColumnWidths($sheet, $widths);

        $row = $this->writeLetterheadImage($sheet, 'logo.png', $lastCol);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'REPORT ON UNSERVICEABLE PROPERTY', ['bold' => true, 'size' => 13]);
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'As of: ' . Carbon::now()->format('F d, Y'), ['bold' => true]);

        $officeName = $items->first()->office_name ?? '';
        $row = $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'Entity Name: ' . $officeName . '     Fund Cluster: ____________________', ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $row++;

        $row = $this->writeTableHeaderRow($sheet, $row, $headers);

        $dataStartRow = $row;
        $totalCost = 0;
        foreach ($items as $item) {
            $qty = (float) str_replace(',', '', $item->qty ?? 0);
            $unitCost = (float) str_replace(',', '', $item->item_cost ?? 0);
            $lineTotal = $qty * $unitCost;
            $totalCost += $lineTotal;

            $col = 1;
            $sheet->setCellValue([$col++, $row], $item->date_acquired ?? '');
            $this->setDescriptionRichText($sheet, $col++, $row, $item->item_name ?? '', $item->item_descrip ?? '', $item->item_model ?? '', '');
            $sheet->setCellValue([$col++, $row], $item->property_no_generated ?? '');
            $sheet->setCellValue([$col++, $row], $qty);
            $this->setMoney($sheet, $col++, $row, $unitCost);
            $this->setMoney($sheet, $col++, $row, $lineTotal);
            $sheet->setCellValue([$col++, $row], $item->remarks ?? '');
            if ($serialOn) {
                $sheet->setCellValue([$col++, $row], $item->serial_number ?? '');
                $sheet->setCellValue([$col++, $row], ($item->person_accnt1 ?? '') !== '' ? ($item->person_accnt_name ?? '') : '');
            }

            $this->wrapRow($sheet, $row, $lastCol);
            $this->borderRow($sheet, $row, $lastCol);
            $sheet->getRowDimension($row)->setRowHeight(40);
            $row++;
        }

        if ($items->isEmpty()) {
            $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'No data available for the selected filters.', []);
            $row++;
        }

        $row = $this->writeTotalRow($sheet, $row, $lastCol, max(1, $lastCol - 2), 'TOTAL:', $data['total_cost'] ?? $totalCost);
        $row++;

        $half = (int) ceil($lastCol / 2);
        $this->mergeAndWrite($sheet, $row, 1, $half, 'Requested by:', ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, 'Approved by:', ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $row++;
        $this->mergeAndWrite($sheet, $row, 1, $half, 'ELEANOR V. SANTIAGO', ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, 'ALEXANDRA M. VILLAROSA', ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $row++;
        $this->mergeAndWrite($sheet, $row, 1, $half, 'School Principal', ['italic' => true], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, 'School Principal', ['italic' => true], Alignment::HORIZONTAL_LEFT);
        $row += 2;
        $this->mergeAndWrite($sheet, $row, 1, $lastCol, 'Signature over Printed Name of Inspection Officer: DANIEL P. MONTECLARO', ['bold' => true], Alignment::HORIZONTAL_LEFT);

        $sheet->freezePane('A' . $dataStartRow);
    }

    // =====================================================================
    // Shared helpers
    // =====================================================================

    /**
     * Splits [[label, width], ...] pairs into [labels[], widths[]].
     */
    private function splitColumns(array $columns): array
    {
        return [array_column($columns, 0), array_column($columns, 1)];
    }

    /**
     * Applies fixed (non-autosize) widths to columns A.. in Excel character units.
     */
    private function setColumnWidths(Worksheet $sheet, array $widths): void
    {
        foreach ($widths as $i => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($width);
        }
    }

    /**
     * Sum of the fixed column widths (in pixels) for columns A..$lastCol,
     * used to horizontally center the letterhead image over the table.
     */
    private function tableWidthPixels(Worksheet $sheet, int $lastCol): int
    {
        $defaultFont = $sheet->getParent()->getDefaultStyle()->getFont();
        $total = 0;

        for ($i = 1; $i <= $lastCol; $i++) {
            $width = $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->getWidth();
            if ($width <= 0) {
                $width = 8.43; // Excel's default column width
            }
            $total += SharedDrawing::cellDimensionToPixels($width, $defaultFont);
        }

        return $total;
    }

    private function writeLetterheadImage(Worksheet $sheet, string $filename, int $lastCol): int
    {
        // logo.png lives at public/; legacy bare filenames live in template/img.
        $path = public_path(
            $filename === 'logo.png'
                ? $filename
                : (str_contains($filename, '/') ? $filename : 'template/img/' . $filename)
        );
        $row = 1;

        if (is_file($path)) {
            $imageSize = @getimagesize($path);
            $aspectRatio = ($imageSize && $imageSize[1] > 0) ? $imageSize[0] / $imageSize[1] : 3;

            $height = 90;
            $width = (int) round($height * $aspectRatio);

            $tableWidthPx = $this->tableWidthPixels($sheet, $lastCol);

            // Never let the letterhead overflow past the table's width
            if ($width > $tableWidthPx && $tableWidthPx > 0) {
                $width = $tableWidthPx;
                $height = (int) round($width / $aspectRatio);
            }

            $offsetX = $tableWidthPx > $width ? (int) round(($tableWidthPx - $width) / 2) : 0;

            // Merge the two header rows into one blank, borderless block so the
            // floating image sits over a clean area instead of visible cell edges.
            $headerRows = 2;
            $letter = Coordinate::stringFromColumnIndex($lastCol);
            $sheet->mergeCells("A1:{$letter}{$headerRows}");

            $row1Height = 55;
            $row2Height = 25;
            $sheet->getRowDimension(1)->setRowHeight($row1Height);
            $sheet->getRowDimension(2)->setRowHeight($row2Height);

            // Vertically center the image within the merged header block
            $blockHeightPx = (int) round(($row1Height + $row2Height) * (96 / 72));
            $offsetY = max(0, (int) round(($blockHeightPx - $height) / 2));

            $drawing = new Drawing();
            $drawing->setName('Letterhead');
            $drawing->setPath($path);
            $drawing->setHeight($height);
            $drawing->setWidth($width);
            $drawing->setResizeProportional(false);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX($offsetX);
            $drawing->setOffsetY($offsetY);
            $drawing->setWorksheet($sheet);

            $row = $headerRows + 1;
        }

        return $row;
    }

    private function mergeAndWrite(Worksheet $sheet, int $row, int $startCol, int $endCol, string $text, array $font = [], string $align = Alignment::HORIZONTAL_CENTER, bool $wrap = false): int
    {
        $startLetter = Coordinate::stringFromColumnIndex($startCol);
        $endLetter = Coordinate::stringFromColumnIndex($endCol);

        if ($startCol !== $endCol) {
            $sheet->mergeCells("{$startLetter}{$row}:{$endLetter}{$row}");
        }

        $cell = $sheet->getCell("{$startLetter}{$row}");
        $cell->setValue($text);
        $cell->getStyle()->getAlignment()->setHorizontal($align)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText($wrap);

        if (!empty($font)) {
            $cell->getStyle()->getFont()
                ->setBold($font['bold'] ?? false)
                ->setItalic($font['italic'] ?? false)
                ->setUnderline($font['underline'] ?? false)
                ->setSize($font['size'] ?? 10);
        }

        if ($wrap) {
            $sheet->getRowDimension($row)->setRowHeight(30);
        }

        return $row + 1;
    }

    private function writeTableHeaderRow(Worksheet $sheet, int $row, array $headers): int
    {
        $col = 1;
        foreach ($headers as $header) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $cell = $sheet->getCell("{$letter}{$row}");
            $cell->setValue($header);
            $cell->getStyle()->getFont()->setBold(true)->setSize(10);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $cell->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
            $cell->getStyle()->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $col++;
        }
        $sheet->getRowDimension($row)->setRowHeight(28);

        return $row + 1;
    }

    private function writeTotalRow(Worksheet $sheet, int $row, int $lastCol, int $labelSpan, string $label, $amount): int
    {
        $labelSpan = max(1, min($labelSpan, $lastCol - 1));
        $valueCol = $labelSpan + 1;

        $labelStart = Coordinate::stringFromColumnIndex(1);
        $labelEnd = Coordinate::stringFromColumnIndex($labelSpan);
        $sheet->mergeCells("{$labelStart}{$row}:{$labelEnd}{$row}");
        $labelCell = $sheet->getCell("{$labelStart}{$row}");
        $labelCell->setValue($label);
        $labelCell->getStyle()->getFont()->setBold(true);
        $labelCell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $valueLetter = Coordinate::stringFromColumnIndex($valueCol);
        $valueCell = $sheet->getCell("{$valueLetter}{$row}");
        $valueCell->setValue((float) $amount);
        $valueCell->getStyle()->getFont()->setBold(true);
        $valueCell->getStyle()->getNumberFormat()->setFormatCode('#,##0.00');
        $valueCell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        if ($valueCol < $lastCol) {
            $endLetter = Coordinate::stringFromColumnIndex($lastCol);
            $sheet->mergeCells("{$valueLetter}{$row}:{$endLetter}{$row}");
        }

        $this->borderRow($sheet, $row, $lastCol, Border::BORDER_MEDIUM);

        return $row + 1;
    }

    private function writeSignatureFooter(Worksheet $sheet, int $row, int $lastCol, array $blocks): void
    {
        $count = count($blocks);
        $span = (int) floor($lastCol / $count);
        $col = 1;

        foreach ($blocks as $i => $block) {
            $startCol = $col;
            $endCol = ($i === $count - 1) ? $lastCol : ($col + $span - 1);

            $this->mergeAndWrite($sheet, $row, $startCol, $endCol, $block['label'], [], Alignment::HORIZONTAL_LEFT);
            $this->mergeAndWrite($sheet, $row + 1, $startCol, $endCol, $block['name'], ['bold' => true], Alignment::HORIZONTAL_LEFT);
            $this->mergeAndWrite($sheet, $row + 2, $startCol, $endCol, $block['title'], ['italic' => true, 'size' => 8], Alignment::HORIZONTAL_LEFT);

            $col = $endCol + 1;
        }
    }

    private function writeReceivedIssuedFooter(Worksheet $sheet, int $row, int $lastCol, string $accountableName, string $endUserName, string $officeName): void
    {
        $half = (int) floor($lastCol / 2);

        $this->mergeAndWrite($sheet, $row, 1, $half, 'Received by:', [], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, 'Issued by:', [], Alignment::HORIZONTAL_LEFT);
        $row++;

        $this->mergeAndWrite($sheet, $row, 1, $half, strtoupper($accountableName), ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, 'BENJAMIN R. DELA TORRE', ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $row++;
        $this->mergeAndWrite($sheet, $row, 1, $half, 'Signature Over Printed Name', ['italic' => true, 'size' => 8], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, 'Signature Over Printed Name', ['italic' => true, 'size' => 8], Alignment::HORIZONTAL_LEFT);
        $row++;

        if ($endUserName !== '') {
            $this->mergeAndWrite($sheet, $row, 1, $half, strtoupper($endUserName), ['bold' => true], Alignment::HORIZONTAL_LEFT);
            $row++;
            $this->mergeAndWrite($sheet, $row, 1, $half, 'End User', ['italic' => true, 'size' => 8], Alignment::HORIZONTAL_LEFT);
            $row++;
        }

        $this->mergeAndWrite($sheet, $row, 1, $half, strtoupper($officeName), ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, 'Supply Officer / SUPPLY OFFICE', ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $row++;
        $this->mergeAndWrite($sheet, $row, 1, $half, 'Position / Office', ['italic' => true, 'size' => 8], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, 'Position / Office', ['italic' => true, 'size' => 8], Alignment::HORIZONTAL_LEFT);
        $row++;

        $this->mergeAndWrite($sheet, $row, 1, $half, '____________________', [], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, Carbon::now()->format('M. j, Y'), ['bold' => true], Alignment::HORIZONTAL_LEFT);
        $row++;
        $this->mergeAndWrite($sheet, $row, 1, $half, 'Date', ['italic' => true, 'size' => 8], Alignment::HORIZONTAL_LEFT);
        $this->mergeAndWrite($sheet, $row, $half + 1, $lastCol, 'Date', ['italic' => true, 'size' => 8], Alignment::HORIZONTAL_LEFT);
    }

    private function setMoney(Worksheet $sheet, int $col, int $row, $value): void
    {
        $letter = Coordinate::stringFromColumnIndex($col);
        $cell = $sheet->getCell("{$letter}{$row}");
        $cell->setValue((float) $value);
        $cell->getStyle()->getNumberFormat()->setFormatCode('#,##0.00');
        $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function setDescriptionRichText(Worksheet $sheet, int $col, int $row, string $name, string $descrip, string $model, string $serial): void
    {
        $model = $model ? str_replace('Model:', '', $model) : '';
        $serial = $serial ? str_replace(';', "\n", $serial) : '';

        $richText = new RichText();
        $richText->createTextRun($name)->getFont()->setBold(true);

        if ($descrip !== '') {
            $richText->createText("\n");
            $richText->createTextRun($descrip)->getFont()->setItalic(true);
        }

        if ($model !== '') {
            $richText->createText("\n");
            $richText->createTextRun('MODEL: ')->getFont()->setBold(true);
            $richText->createText($model);
        }

        if ($serial !== '') {
            $richText->createText("\n");
            $richText->createTextRun('SN: ')->getFont()->setBold(true);
            $richText->createText($serial);
        }

        $letter = Coordinate::stringFromColumnIndex($col);
        $cell = $sheet->getCell("{$letter}{$row}");
        $cell->setValue($richText);
        $cell->getStyle()->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    }

    /**
     * Enables wrap text on a data row so fixed column widths don't clip content.
     */
    private function wrapRow(Worksheet $sheet, int $row, int $lastCol): void
    {
        $startLetter = Coordinate::stringFromColumnIndex(1);
        $endLetter = Coordinate::stringFromColumnIndex($lastCol);
        $sheet->getStyle("{$startLetter}{$row}:{$endLetter}{$row}")->getAlignment()->setWrapText(true);
    }

    private function borderRow(Worksheet $sheet, int $row, int $lastCol, string $style = Border::BORDER_THIN): void
    {
        $startLetter = Coordinate::stringFromColumnIndex(1);
        $endLetter = Coordinate::stringFromColumnIndex($lastCol);
        $sheet->getStyle("{$startLetter}{$row}:{$endLetter}{$row}")->getBorders()->getAllBorders()->setBorderStyle($style);
        $sheet->getStyle("{$startLetter}{$row}:{$endLetter}{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }
}
