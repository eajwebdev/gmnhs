<?php

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Fills the blank Supply Form 11 (Property Transfer Report / GAM Appendix 76)
 * that Supply keeps at public/Supply-Form-11-Property-Transfer-Report.xlsx.
 *
 * The workbook is opened and written into rather than rebuilt, so the printed
 * output keeps the letterhead, column widths, borders and page setup of the
 * official form byte for byte.
 */
class PropertyTransferReportExcelBuilder
{
    public const TEMPLATE = 'Supply-Form-11-Property-Transfer-Report.xlsx';

    /**
     * Fixed coordinates of the blank form. Row numbers below the item table
     * shift down as extra item rows are inserted.
     */
    private const ROW_FUND_CLUSTER = 7;
    private const ROW_FROM = 9;
    private const ROW_TO = 10;
    private const ROW_TYPE_FIRST = 14;
    private const ROW_TYPE_SECOND = 15;
    private const ROW_ITEM_FIRST = 19;
    private const ROW_ITEM_LAST = 33;
    private const ROW_REASON_LABEL = 34;
    private const ROW_REASON_TEXT = 35;
    private const ROW_SIGNATORY_NAME = 44;
    private const ROW_SIGNATORY_DESIGNATION = 45;

    private const CHECKED = '[ / ]';
    private const UNCHECKED = '[    ]';

    public function build(array $data): Spreadsheet
    {
        $spreadsheet = $this->openTemplate();
        $sheet = $spreadsheet->getActiveSheet();

        $items = collect($data['items'] ?? []);

        // The blank form provides ROW_ITEM_LAST - ROW_ITEM_FIRST + 1 lines. Longer
        // reports grow the table in place so the footer stays attached below it.
        $extraRows = max(0, $items->count() - $this->templateRowCount());
        $shift = $this->growItemTable($sheet, $extraRows);

        $this->writeHeader($sheet, $data);
        $this->writeTransferType($sheet, $data);
        $this->writeItems($sheet, $items);
        $this->writeReason($sheet, $data, $shift);
        $this->writeSignatories($sheet, $data, $shift);

        // Repeat the letterhead and column headings on every printed page.
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, self::ROW_ITEM_FIRST - 1);
        $sheet->setSelectedCell('A1');

        return $spreadsheet;
    }

    private function openTemplate(): Spreadsheet
    {
        $path = public_path(self::TEMPLATE);

        if (!is_file($path)) {
            throw new \RuntimeException('Property Transfer Report template is missing: '.$path);
        }

        return IOFactory::createReader('Xlsx')->load($path);
    }

    private function templateRowCount(): int
    {
        return self::ROW_ITEM_LAST - self::ROW_ITEM_FIRST + 1;
    }

    /**
     * Adds item lines by cloning the last blank row of the template table, so the
     * new rows carry the same borders, merges and height. Returns how far every
     * row below the table moved down.
     */
    private function growItemTable(Worksheet $sheet, int $extraRows): int
    {
        if ($extraRows < 1) {
            return 0;
        }

        // Insert above the final template row so the row being copied still
        // exists with its original styling.
        $sheet->insertNewRowBefore(self::ROW_ITEM_LAST, $extraRows);

        $height = $sheet->getRowDimension(self::ROW_ITEM_LAST + $extraRows)->getRowHeight();

        for ($offset = 0; $offset < $extraRows; $offset++) {
            $row = self::ROW_ITEM_LAST + $offset;

            $sheet->duplicateStyle(
                $sheet->getStyle('A'.(self::ROW_ITEM_LAST + $extraRows)),
                'A'.$row.':I'.$row
            );

            // insertNewRowBefore() does not carry the per-row merges of the copied
            // row, so the property-number and description cells are re-merged.
            foreach (['B:C', 'D:G'] as $span) {
                [$start, $end] = explode(':', $span);
                $sheet->mergeCells($start.$row.':'.$end.$row);
            }

            if ($height > 0) {
                $sheet->getRowDimension($row)->setRowHeight($height);
            }
        }

        return $extraRows;
    }

    private function writeHeader(Worksheet $sheet, array $data): void
    {
        $fundCluster = $data['fund_cluster'] ?? '';
        $sheet->setCellValue('H'.self::ROW_FUND_CLUSTER, 'Fund Cluster : '.$fundCluster);

        // The blank form carries an underscore rule where the officer's name goes;
        // filling it in replaces the rule with the name.
        $sheet->setCellValue(
            'A'.self::ROW_FROM,
            'From Accountable Officer/Agency/Fund Cluster :  '.$this->officerLine($data['from_officer'] ?? [])
        );
        $sheet->setCellValue('H'.self::ROW_FROM, 'PTR No. : '.($data['ptr_no'] ?? ''));

        $sheet->setCellValue(
            'A'.self::ROW_TO,
            'To Accountable Officer/Agency/Fund Cluster : '.$this->officerLine($data['to_officer'] ?? [])
        );
        $sheet->setCellValue('H'.self::ROW_TO, 'Date : '.($data['ptr_date'] ?? ''));
    }

    private function officerLine(array $officer): string
    {
        $name = trim((string) ($officer['name'] ?? ''));
        $designation = trim((string) ($officer['designation'] ?? ''));

        if ($name === '') {
            return '__________________________';
        }

        return $designation === '' ? $name : $name.' ('.$designation.')';
    }

    /**
     * Ticks one of the four boxes. The narrow columns A and E sit to the left of
     * each label on the printed form, which is where the tick belongs.
     */
    private function writeTransferType(Worksheet $sheet, array $data): void
    {
        $selected = $data['transfer_type'] ?? null;
        $other = trim((string) ($data['transfer_type_other'] ?? ''));

        $boxes = [
            'Donation' => ['A', self::ROW_TYPE_FIRST],
            'Relocate' => ['E', self::ROW_TYPE_FIRST],
            'Reassignment' => ['A', self::ROW_TYPE_SECOND],
            'Others' => ['E', self::ROW_TYPE_SECOND],
        ];

        foreach ($boxes as $type => [$column, $row]) {
            $cell = $sheet->getCell($column.$row);
            $cell->setValue($type === $selected ? self::CHECKED : self::UNCHECKED);
            $cell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        if ($selected === 'Others') {
            $sheet->setCellValue(
                'F'.self::ROW_TYPE_SECOND,
                'Others (Specify) '.($other !== '' ? $other : '_________________')
            );
        }
    }

    private function writeItems(Worksheet $sheet, $items): void
    {
        $row = self::ROW_ITEM_FIRST;

        foreach ($items as $item) {
            $sheet->setCellValue('A'.$row, $item['date_acquired'] ?? '');
            $sheet->setCellValue('B'.$row, $item['property_no'] ?? '');
            $sheet->setCellValue('D'.$row, $this->descriptionLine($item));

            $amount = (float) ($item['amount'] ?? 0);
            $sheet->setCellValue('H'.$row, $amount);
            $sheet->getStyle('H'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('I'.$row, $item['condition'] ?? '');
            $sheet->getStyle('D'.$row)->getAlignment()->setWrapText(true);

            $row++;
        }
    }

    /**
     * The DESCRIPTION column is a single merged cell, so the item name, model and
     * serial number are folded into one line each separated by a bullet.
     */
    private function descriptionLine(array $item): string
    {
        $parts = [];
        $name = trim((string) ($item['item_name'] ?? ''));
        $descrip = trim((string) ($item['descrip'] ?? ''));

        if ($name !== '') {
            $parts[] = $name;
        }

        if ($descrip !== '' && strcasecmp($descrip, $name) !== 0) {
            $parts[] = $descrip;
        }

        $model = trim((string) ($item['model'] ?? ''));
        if ($model !== '' && strcasecmp($model, 'n/a') !== 0) {
            $parts[] = 'Model: '.$model;
        }

        $serial = trim((string) ($item['serial_number'] ?? ''));
        if ($serial !== '' && strcasecmp($serial, 'n/a') !== 0) {
            $parts[] = 'SN: '.str_replace(';', ' / ', $serial);
        }

        return implode(' · ', $parts);
    }

    private function writeReason(Worksheet $sheet, array $data, int $shift): void
    {
        $labelRow = self::ROW_REASON_LABEL + $shift;
        $textRow = self::ROW_REASON_TEXT + $shift;

        // The form's own label is not reprinted - the block shows the reason itself,
        // so clear whatever the template left on that row.
        $sheet->setCellValue('A'.$labelRow, '');

        // The reason sits inside the bordered block the form leaves below the
        // label; merging it keeps long text on one wrapped paragraph.
        $sheet->mergeCells('A'.$textRow.':I'.($textRow + 2));
        $cell = $sheet->getCell('A'.$textRow);
        $cell->setValue($data['reason'] ?? '');
        $cell->getStyle()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);

        $summary = $this->summaryLine($data);

        if ($summary !== '') {
            $summaryRow = $textRow + 3;
            $sheet->mergeCells('A'.$summaryRow.':I'.$summaryRow);
            $summaryCell = $sheet->getCell('A'.$summaryRow);
            $summaryCell->setValue($summary);
            $summaryCell->getStyle()->getFont()->setItalic(true)->setSize(8);
            $summaryCell->getStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
    }

    /**
     * Provenance line: what the report covers and what it totals, so a printed
     * copy can be traced back to the return-slip trail it came from.
     */
    private function summaryLine(array $data): string
    {
        $items = collect($data['items'] ?? []);

        if ($items->isEmpty()) {
            return '';
        }

        return sprintf(
            '%s · %d item%s totalling PHP %s · Period: %s · Generated %s by %s',
            $data['scope_label'] ?? '',
            $items->count(),
            $items->count() === 1 ? '' : 's',
            number_format((float) ($data['total_amount'] ?? 0), 2),
            $data['period_label'] ?? 'All recorded dates',
            $data['generated_at'] ?? '',
            $data['generated_by'] ?? ''
        );
    }

    private function writeSignatories(Worksheet $sheet, array $data, int $shift): void
    {
        $nameRow = self::ROW_SIGNATORY_NAME + $shift;
        $designationRow = self::ROW_SIGNATORY_DESIGNATION + $shift;

        // Column B is APPROVED BY, F is RELEASED/ISSUED BY, H is RECEIVED BY.
        $blocks = [
            'B' => $data['approved_by'] ?? [],
            'F' => $data['released_by'] ?? [],
            'H' => $data['received_by'] ?? [],
        ];

        foreach ($blocks as $column => $block) {
            $sheet->setCellValue($column.$nameRow, strtoupper(trim((string) ($block['name'] ?? ''))));
            $sheet->setCellValue($column.$designationRow, trim((string) ($block['designation'] ?? '')));
            $sheet->getStyle($column.$designationRow)->getAlignment()->setWrapText(true);
        }
    }
}
