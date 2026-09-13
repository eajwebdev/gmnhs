<?php

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Fills the blank Supply Form 10 (Property Return Slip, revised for DRAR) that
 * Supply keeps in public/.
 *
 * As with the Property Transfer Report, the workbook is opened and written into
 * rather than rebuilt, so the printed output keeps the letterhead, column widths,
 * borders and page setup of the official form.
 */
class PropertyReturnSlipExcelBuilder
{
    public const TEMPLATE = 'Supply-Form-10-PROPERTY-RETURN-SLIP-REVISED (for DRAR).xlsx';

    /**
     * Fixed coordinates of the blank form. Rows below the item table shift down as
     * extra item rows are inserted.
     */
    private const ROW_ITEM_FIRST = 10;
    private const ROW_ITEM_LAST = 28;
    private const ROW_END_USER_RULE = 35;
    private const ROW_END_USER_LABEL = 36;
    private const ROW_DATE_RULE = 37;

    private const LAST_COLUMN = 'K';

    /**
     * The template only carries the accounting format down to row 26, so the two
     * value columns are formatted explicitly on every row that is written.
     */
    private const MONEY_FORMAT = '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)';

    /**
     * A -> K, in the order the form's headings run.
     */
    private const COLUMNS = [
        'A' => 'item_no',
        'B' => 'qty',
        'C' => 'unit_name',
        'D' => 'item_name',
        'E' => 'descrip',
        'F' => 'unit_value',
        'G' => 'total_value',
        'H' => 'property_no',
        'I' => 'date_acquired',
        'J' => 'fund_code',
        'K' => 'enduser_name',
    ];

    public function build(array $data): Spreadsheet
    {
        $spreadsheet = $this->openTemplate();
        $sheet = $spreadsheet->getActiveSheet();

        $items = collect($data['items'] ?? []);

        $extraRows = max(0, $items->count() - $this->templateRowCount());
        $shift = $this->growItemTable($sheet, $extraRows);

        $this->writeItems($sheet, $items);
        $this->writeSubmitter($sheet, $data, $shift);

        // Repeat the letterhead and the column headings on every printed page.
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, self::ROW_ITEM_FIRST - 1);
        $sheet->setSelectedCell('A1');

        return $spreadsheet;
    }

    private function openTemplate(): Spreadsheet
    {
        $path = public_path(self::TEMPLATE);

        if (!is_file($path)) {
            throw new \RuntimeException('Property Return Slip template is missing: '.$path);
        }

        return IOFactory::createReader('Xlsx')->load($path);
    }

    private function templateRowCount(): int
    {
        return self::ROW_ITEM_LAST - self::ROW_ITEM_FIRST + 1;
    }

    /**
     * Adds item lines by cloning the last blank row of the template table so the
     * new rows carry the same borders and height. Returns how far every row below
     * the table moved down.
     */
    private function growItemTable(Worksheet $sheet, int $extraRows): int
    {
        if ($extraRows < 1) {
            return 0;
        }

        // Insert above the final template row so the row being copied still exists
        // with its original styling.
        $sheet->insertNewRowBefore(self::ROW_ITEM_LAST, $extraRows);

        $source = self::ROW_ITEM_LAST + $extraRows;
        $height = $sheet->getRowDimension($source)->getRowHeight();

        for ($offset = 0; $offset < $extraRows; $offset++) {
            $row = self::ROW_ITEM_LAST + $offset;

            // Copied column by column: the outer columns carry the table's medium
            // side borders, so cloning A across A:K would leave the inserted rows
            // with a hairline where the right edge of the table should be.
            foreach (array_keys(self::COLUMNS) as $column) {
                $sheet->duplicateStyle($sheet->getStyle($column.$source), $column.$row);
            }

            if ($height > 0) {
                $sheet->getRowDimension($row)->setRowHeight($height);
            }
        }

        return $extraRows;
    }

    private function writeItems(Worksheet $sheet, $items): void
    {
        $row = self::ROW_ITEM_FIRST;
        $number = 1;

        foreach ($items as $item) {
            $values = [
                'item_no' => $number,
                'qty' => $item['qty'] ?? 1,
                'unit_name' => $item['unit_name'] ?? '',
                'item_name' => $item['item_name'] ?? '',
                'descrip' => $this->descriptionLine($item),
                'unit_value' => (float) ($item['unit_value'] ?? 0),
                'total_value' => (float) ($item['total_value'] ?? 0),
                'property_no' => $item['property_no'] ?? '',
                'date_acquired' => $item['date_acquired'] ?? '',
                'fund_code' => $item['fund_code'] ?? '',
                'enduser_name' => $item['enduser_name'] ?? '',
            ];

            foreach (self::COLUMNS as $column => $key) {
                $sheet->setCellValue($column.$row, $values[$key]);
            }

            $sheet->getStyle('F'.$row.':G'.$row)->getNumberFormat()
                ->setFormatCode(self::MONEY_FORMAT);

            $sheet->getStyle('E'.$row)->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $row++;
            $number++;
        }
    }

    /**
     * DESCRIPTION is one narrow column, so the description, model and serial are
     * folded into it, each on its own wrapped line.
     */
    private function descriptionLine(array $item): string
    {
        $parts = [];
        $descrip = trim((string) ($item['descrip'] ?? ''));
        $name = trim((string) ($item['item_name'] ?? ''));

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

        return implode("\n", $parts);
    }

    /**
     * Fills the "Prepared and Submitted by" block: the end user's name over the
     * form's rule, and the date of submission below it.
     */
    private function writeSubmitter(Worksheet $sheet, array $data, int $shift): void
    {
        $nameRow = self::ROW_END_USER_RULE + $shift;
        $dateRow = self::ROW_DATE_RULE + $shift;

        $endUser = trim((string) ($data['end_user'] ?? ''));
        $office = trim((string) ($data['office_name'] ?? ''));

        $sheet->setCellValue('A'.$nameRow, $endUser !== '' ? $endUser : '___________________');
        $sheet->getStyle('A'.$nameRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$nameRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // The label row beneath the rule names the capacity; the office is appended
        // so a printed copy shows where the property came from.
        if ($office !== '') {
            $labelRow = self::ROW_END_USER_LABEL + $shift;
            $sheet->setCellValue('A'.$labelRow, 'End user — '.$office);
        }

        $sheet->setCellValue('A'.$dateRow, $data['submitted_at'] ?? '');
        $sheet->getStyle('A'.$dateRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
