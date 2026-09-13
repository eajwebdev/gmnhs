<?php

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Fills Appendix 74 (Inventory and Inspection Report of Unserviceable Property)
 * from public/IIRUP - FORM.xlsx.
 */
class IirupExcelBuilder
{
    public const TEMPLATE = 'IIRUP - FORM.xlsx';
    // Keep the Excel letterhead identical to the PDF letterhead.
    public const HEADER_IMAGE = 'logo.png';

    private const HEADER_WIDTH = 417;
    private const HEADER_HEIGHT = 65;
    private const HEADER_OFFSET_X = 38;
    private const HEADER_OFFSET_Y = 10;

    private const ROW_PERIOD = 8;
    private const ROW_FUND_CLUSTER = 10;
    private const ROW_ACCOUNTABLE_NAME = 11;
    private const ROW_ITEM_FIRST = 19;
    private const ROW_ITEM_LAST = 20;
    private const ROW_GRAND_TOTAL = 21;
    private const LAST_COLUMN = 'R';

    private const MONEY_FORMAT = '#,##0.00';

    public function build(array $data): Spreadsheet
    {
        $spreadsheet = $this->openTemplate();
        $sheet = $spreadsheet->getActiveSheet();

        $this->applyReportHeader($sheet);

        $items = collect($data['items'] ?? []);
        $shift = $this->prepareItemTable($sheet, $items->count());

        $this->writeHeader($sheet, $data);
        $this->writeItems($sheet, $items);
        $this->styleItemRows($sheet, max($items->count(), $this->templateRowCount()));
        $this->writeGrandTotal($sheet, $data, $shift);

        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 18);
        $sheet->getPageSetup()->setPrintArea('A1:'.self::LAST_COLUMN.(33 + $shift));
        $sheet->setSelectedCell('A1');

        return $spreadsheet;
    }

    private function openTemplate(): Spreadsheet
    {
        $path = public_path(self::TEMPLATE);

        if (!is_file($path)) {
            throw new \RuntimeException('IIRUP template is missing: '.$path);
        }

        return IOFactory::createReader('Xlsx')->load($path);
    }

    private function applyReportHeader(Worksheet $sheet): void
    {
        foreach (array_keys($sheet->getDrawingCollection()->getArrayCopy()) as $key) {
            $sheet->getDrawingCollection()->offsetUnset($key);
        }

        $path = public_path(self::HEADER_IMAGE);

        if (!is_file($path)) {
            return;
        }

        // Same letterhead image used by the Property Transfer Report template.
        $drawing = new Drawing();
        $drawing->setName('Report Header');
        $drawing->setPath($path);
        $drawing->setCoordinates('B1');
        $drawing->setOffsetX(self::HEADER_OFFSET_X);
        $drawing->setOffsetY(self::HEADER_OFFSET_Y);
        $drawing->setWidth(self::HEADER_WIDTH);
        $drawing->setHeight(self::HEADER_HEIGHT);
        $drawing->setResizeProportional(false);
        $drawing->setWorksheet($sheet);
    }

    private function templateRowCount(): int
    {
        return self::ROW_ITEM_LAST - self::ROW_ITEM_FIRST + 1;
    }

    private function prepareItemTable(Worksheet $sheet, int $itemCount): int
    {
        $extraRows = max(0, $itemCount - $this->templateRowCount());

        if ($extraRows < 1) {
            return 0;
        }

        $this->copyFooterBlock($sheet, self::ROW_GRAND_TOTAL, self::ROW_GRAND_TOTAL + $extraRows, 13);

        $sourceRow = self::ROW_ITEM_LAST;
        $height = $sheet->getRowDimension($sourceRow)->getRowHeight();
        $firstExtraRow = self::ROW_GRAND_TOTAL;
        $lastItemRow = self::ROW_ITEM_FIRST + $itemCount - 1;

        for ($row = $firstExtraRow; $row <= $lastItemRow; $row++) {
            if ($height > 0) {
                $sheet->getRowDimension($row)->setRowHeight($height);
            }
        }

        return $extraRows;
    }

    private function copyFooterBlock(Worksheet $sheet, int $sourceStartRow, int $targetStartRow, int $rowCount): void
    {
        $sourceEndRow = $sourceStartRow + $rowCount - 1;
        $rowOffset = $targetStartRow - $sourceStartRow;
        $footerMerges = [];

        foreach ($sheet->getMergeCells() as $mergeRange) {
            [$start, $end] = Coordinate::rangeBoundaries($mergeRange);
            $startRow = $start[1];
            $endRow = $end[1];

            if ($startRow >= $sourceStartRow && $endRow <= $sourceEndRow) {
                $footerMerges[] = [$start, $end, $mergeRange];
            }
        }

        foreach ($footerMerges as [, , $mergeRange]) {
            $sheet->unmergeCells($mergeRange);
        }

        $sheet->duplicateStyle(
            $sheet->getStyle('A'.$sourceStartRow.':'.self::LAST_COLUMN.$sourceEndRow),
            'A'.$targetStartRow.':'.self::LAST_COLUMN.($targetStartRow + $rowCount - 1)
        );

        for ($row = $sourceStartRow; $row <= $sourceEndRow; $row++) {
            $targetRow = $row + $rowOffset;
            $height = $sheet->getRowDimension($row)->getRowHeight();

            if ($height > 0) {
                $sheet->getRowDimension($targetRow)->setRowHeight($height);
            }

            for ($col = 1; $col <= 18; $col++) {
                $sourceCell = Coordinate::stringFromColumnIndex($col).$row;
                $targetCell = Coordinate::stringFromColumnIndex($col).$targetRow;
                $sheet->setCellValue($targetCell, $sheet->getCell($sourceCell)->getValue());
            }
        }

        foreach ($footerMerges as [$start, $end]) {
            $startRow = $start[1];
            $endRow = $end[1];
            $startColumn = Coordinate::stringFromColumnIndex($start[0]);
            $endColumn = Coordinate::stringFromColumnIndex($end[0]);
            $sheet->mergeCells($startColumn.($startRow + $rowOffset).':'.$endColumn.($endRow + $rowOffset));
        }
    }

    private function writeHeader(Worksheet $sheet, array $data): void
    {
        $sheet->setCellValue('A'.self::ROW_PERIOD, 'for the period '.$this->blankable($data['period_label'] ?? ''));
        $sheet->setCellValue('O'.self::ROW_FUND_CLUSTER, 'Fund Cluster  '.$this->blankable($data['fund_cluster'] ?? ''));

        $sheet->setCellValue('A'.self::ROW_ACCOUNTABLE_NAME, strtoupper(trim((string) ($data['accountable_name'] ?? ''))));
        $sheet->setCellValue('E'.self::ROW_ACCOUNTABLE_NAME, trim((string) ($data['designation'] ?? '')));
        $sheet->setCellValue('J'.self::ROW_ACCOUNTABLE_NAME, trim((string) ($data['station'] ?? '')));

        foreach (['A'.self::ROW_ACCOUNTABLE_NAME, 'E'.self::ROW_ACCOUNTABLE_NAME, 'J'.self::ROW_ACCOUNTABLE_NAME] as $cell) {
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function writeItems(Worksheet $sheet, $items): void
    {
        $row = self::ROW_ITEM_FIRST;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 1);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $totalCost = (float) ($item['total_cost'] ?? ($qty * $unitCost));

            $sheet->setCellValue('A'.$row, $item['date_acquired'] ?? '');
            $sheet->setCellValue('B'.$row, $this->particularsLine($item));
            $sheet->setCellValue('C'.$row, $this->propertyAndSerialLine($item));
            $sheet->setCellValue('D'.$row, $qty > 0 ? $qty : 1);
            $sheet->setCellValue('E'.$row, $unitCost);
            $sheet->setCellValue('F'.$row, $totalCost);
            $sheet->setCellValue('G'.$row, '');
            $sheet->setCellValue('H'.$row, '');
            $sheet->setCellValue('I'.$row, $totalCost);
            $sheet->setCellValue('J'.$row, $this->remarksLine($item));

            $row++;
        }
    }

    private function styleItemRows(Worksheet $sheet, int $rowCount): void
    {
        if ($rowCount < 1) {
            return;
        }

        $lastRow = self::ROW_ITEM_FIRST + $rowCount - 1;
        $itemRange = 'A'.self::ROW_ITEM_FIRST.':'.self::LAST_COLUMN.$lastRow;

        $sheet->getStyle($itemRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($itemRange)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $sheet->getStyle('A'.self::ROW_ITEM_FIRST.':A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.self::ROW_ITEM_FIRST.':D'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E'.self::ROW_ITEM_FIRST.':I'.$lastRow)->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        $sheet->getStyle('E'.self::ROW_ITEM_FIRST.':I'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function writeGrandTotal(Worksheet $sheet, array $data, int $shift): void
    {
        $row = self::ROW_GRAND_TOTAL + $shift;
        $total = (float) ($data['total_cost'] ?? 0);

        $sheet->setCellValue('F'.$row, $total);
        $sheet->setCellValue('I'.$row, $total);
        $sheet->getStyle('F'.$row.':I'.$row)->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        $sheet->getStyle('F'.$row.':I'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function particularsLine(array $item): string
    {
        $parts = [];
        $name = trim((string) ($item['item_name'] ?? ''));
        $description = trim((string) ($item['description'] ?? ''));
        $model = trim((string) ($item['model'] ?? ''));

        if ($name !== '') {
            $parts[] = $name;
        }

        if ($description !== '' && strcasecmp($description, $name) !== 0) {
            $parts[] = $description;
        }

        if ($model !== '' && strcasecmp($model, 'n/a') !== 0) {
            $parts[] = 'Model: '.$model;
        }

        return implode("\n", $parts);
    }

    private function propertyAndSerialLine(array $item): string
    {
        $parts = [];
        $propertyNo = trim((string) ($item['property_no'] ?? ''));
        $serial = trim((string) ($item['serial_number'] ?? ''));

        if ($propertyNo !== '') {
            $parts[] = $propertyNo;
        }

        if ($serial !== '' && strcasecmp($serial, 'n/a') !== 0) {
            $parts[] = 'SN: '.str_replace(';', ' / ', $serial);
        }

        return implode("\n", $parts);
    }

    /**
     * Remarks carries the school/office instead of the logged remark, which is
     * why particularsLine() no longer repeats it.
     */
    private function remarksLine(array $item): string
    {
        $parts = [];

        if (!empty($item['status'])) {
            $parts[] = $item['status'];
        }

        $office = trim((string) ($item['office_name'] ?? ''));
        if ($office !== '') {
            $parts[] = 'SCHOOL/OFFICE : '.$office;
        }

        return implode(' - ', $parts);
    }

    private function blankable($value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : '______________________';
    }
}
