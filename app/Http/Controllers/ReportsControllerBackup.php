<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
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

class ReportsController extends Controller
{
    public function rpcppeOption() {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $property = Property::whereIn('id', [3])->get();
        $category = Category::all();

        return view('reports.rpcppe_option', compact('setting', 'office', 'property', 'category'));
    } 
    
    public function rpcppeOptionReportGen(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $item = Item::all();
        $unit = Unit::all();
        $category = Category::all();

        $officeId = $request->query('office_id');
        $officeId1 = $request->query('office_id');
        $propertiesId = $request->query('properties_id');
        $categoriesId = $request->query('categories_id');
        $propId = $request->query('property_id');
        $startDate = $request->query('start_date_acquired');
        $endDate = $request->query('end_date_acquired');
        $selectId = $request->query('selected_account_id');

        $formattedStartDate = $startDate ? date('M. d, Y', strtotime($startDate)) : '';
        $formattedEndDate = $endDate ? date('M. d, Y', strtotime($endDate)) : '';

        $cond = ($categoriesId == 'All') ? '!=' : '=';
        $allOffice = ($officeId == 'All') ? '!=' : '=';

        $propId = ($categoriesId == 'All') ? $propId = '0' : $propId;
        $selectId = ($categoriesId == 'All') ? $selectId = '0' : $selectId;
        $categoriesId = ($categoriesId == 'All') ? $categoriesId = '0' : $categoriesId;

        $purchase = Purchases::join('offices', 'purchases.office_id', '=', 'offices.id')
            ->join('properties', 'purchases.selected_account_id', '=', 'properties.id')
            ->join('units', 'purchases.unit_id', '=', 'units.id')
            ->join('items', 'purchases.item_id', '=', 'items.id')
            ->where(function ($query) use ($officeId, $allOffice, $cond) {
                if ($officeId == 1) {
                    $query->whereNotIn('purchases.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('purchases.office_id', $allOffice, $officeId);
                }
            })
            ->where('purchases.properties_id', $propertiesId)
            ->where('purchases.categories_id', $cond, $categoriesId)
            ->where('purchases.property_id', $cond, $propId)
            ->where('purchases.selected_account_id', $cond, $selectId)
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('purchases.date_acquired', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('purchases.date_acquired', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('purchases.date_acquired', '<=', $endDate);
                }
            })
        ->get();

        $purchase1 = Purchases::join('offices', 'purchases.office_id', '=', 'offices.id')
            ->join('properties', 'purchases.selected_account_id', '=', 'properties.id')
            ->join('units', 'purchases.unit_id', '=', 'units.id')
            ->join('items', 'purchases.item_id', '=', 'items.id')
            ->where(function ($query) use ($officeId, $allOffice, $cond) {
                if ($officeId == 1) {
                    $query->whereNotIn('purchases.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('purchases.office_id', $allOffice, $officeId);
                }
            })
            ->where('purchases.properties_id', $propertiesId)
            ->where('purchases.categories_id', $cond, $categoriesId)
            ->where('purchases.property_id', $cond, $propId)
            ->where('purchases.selected_account_id', $cond, $selectId)
            ->where(function ($query) use ($startDate) {
                if ($startDate) {
                    $query->where('purchases.date_acquired', '<', $startDate);
                }
            })
            ->get();


        $purchase2 = Purchases::join('offices', 'purchases.office_id', '=', 'offices.id')
            ->join('properties', 'purchases.selected_account_id', '=', 'properties.id')
            ->join('units', 'purchases.unit_id', '=', 'units.id')
            ->join('items', 'purchases.item_id', '=', 'items.id')
            // ->where('purchases.office_id', $allOffice, $officeId)
            ->where(function ($query) use ($officeId, $allOffice, $cond) {
                if ($officeId == 1) {
                    $query->whereNotIn('purchases.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('purchases.office_id', $cond, $allOffice);
                }
            })
            ->where('purchases.properties_id', $propertiesId)
            ->where('purchases.categories_id', $cond, $categoriesId)
            ->where('purchases.property_id', $cond, $propId)
            ->where('purchases.selected_account_id', $cond, $selectId)
            ->where(function ($query) use ($endDate) {
                if ($endDate) {
                    $query->where('purchases.date_acquired', '>', $endDate);
                }
            })
            ->get();

        $bforward = $purchase1->sum(function ($purchase1) {
            return str_replace(',', '', $purchase1->total_cost);
        });

        // Ensure $purchase2 is not null or empty before attempting to use it
        $bforward1 = $purchase2->isEmpty() ? 0 : $purchase2->sum(function ($purchase) {
            return str_replace(',', '', $purchase->total_cost);
        });

        $data = [
            'purchase' => $purchase,
            'startDate' => $formattedStartDate,
            'endDate' => $formattedEndDate,
            'selectedPropertyId' => $selectId,
            'category_id' => $purchase,
            'bforward' => $bforward, 
            'bforward1' => $bforward1, 
        ];

        if($request->file_type == "PDF"){
            $pdf = PDF::loadView('reports.rpcppe_option_reportsGen', $data)->setPaper('Legal', 'landscape');
            return $pdf->stream();
        }else {
            $filePath = public_path('Forms/RPCPPE Reports.xlsx');
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

            $sheet = $spreadsheet->getActiveSheet();
            
            $row = 10;
            $overallTotal = 0;

            $sheetC3 = '';

            if (request('categories_id') === 'All' || empty($categoriesId)) {
                if (request('categories_id') === 'All') {
                    $sheetC3 = 'ALL';
                } else {
                    $sheetC3 = isset($purchase->first()->account_title_abbr) ? $purchase->first()->account_title_abbr : '';
                }
            } elseif ($purchase->isEmpty()) {
                $sheetC3 = '';
            }
            
            $sheetC5 = 'As at ' . $formattedStartDate . ' to ' . $formattedEndDate;
            
            $sheet->mergeCells('C3:H3');
            $sheet->mergeCells('C5:H5');
            $sheet->setCellValue('C3', $sheetC3);
            $sheet->setCellValue('C4', '(Type of Property, Plant and Equipment)');
            $sheet->setCellValue('C5', $sheetC5);

            $sheet->mergeCells('G9:L9');
            $sheet->setCellValue('G9', number_format($bforward, 2));
            
            foreach ($purchase as $pur) {
                
                if (is_numeric(str_replace(',', '', $pur->total_cost))){
                    $overallTotal += str_replace(',', '', $pur->total_cost); 
                }

                $sheet->setCellValue('A' . $row, $pur->item_name);
                $sheet->setCellValue('B' . $row, $pur->item_descrip);
                $sheet->setCellValue('C' . $row, $pur->property_no_generated);
                $sheet->setCellValue('D' . $row, $pur->unit_name);
                $sheet->setCellValue('E' . $row, $pur->item_cost);
                $sheet->setCellValue('F' . $row, $pur->qty);
                $sheet->setCellValue('G' . $row, $pur->total_cost);
                $sheet->setCellValue('H' . $row, '');
                $sheet->setCellValue('I' . $row, $pur->qty);
                $sheet->setCellValue('J' . $row, '');
                $sheet->setCellValue('K' . $row, $pur->remarks);
                $sheet->setCellValue('L' . $row, $pur->office_name);
            
                $style = $sheet->getStyle('A' . $row . ':L' . $row);
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $row++;

            }
            
            $footerRow = $row;

            $rows = [
                ['label' => 'Total', 'value' => $overallTotal],
                ['label' => 'Grand Total', 'value' => $overallTotal + $bforward + $bforward1],
            ];
            
            foreach ($rows as $rowData) {
                $label = $rowData['label'];
                $value = $rowData['value'];
            
                $rangeAtoF = 'A' . $footerRow . ':F' . $footerRow;
                $sheet->mergeCells($rangeAtoF);
                $sheet->setCellValue('A' . $footerRow, $label)
                ->getStyle('A' . $footerRow)
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
                $rangeGtoL = 'G' . $footerRow . ':L' . $footerRow;
                $sheet->mergeCells($rangeGtoL);
                $sheet->setCellValue('G' . $footerRow, number_format($value, 2));
            
                $footerStyleAtoF = $sheet->getStyle($rangeAtoF);
                $footerStyleAtoF->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $footerStyleAtoF->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerStyleGtoL = $sheet->getStyle($rangeGtoL);
                $footerStyleGtoL->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $footerStyleGtoL->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerRow++;
            }

            $footerRow = $row + 2;

            $rows = [
                ['Certified Correct by:', 'Approved by:', 'Verify by:'],
                ['', '', ''],
                ['BENJAMIN R. DELA TORRE', 'ALEXANDRA M. VILLAROSA', ''],
                ['Administrative Officer IV/Supply Officer designate', 'School Principal', 'Signature over Printed Name of COA Representative'],
            ];
            
            $rows = [
                ['Certified Correct by:', 'Approved by:', 'Verify by:'],
                ['', '', ''],
                ['BENJAMIN R. DELA TORRE', 'ALEXANDRA M. VILLAROSA', ''],
                ['Administrative Officer IV/Supply Officer designate', 'School Principal', 'Signature over Printed Name of COA Representative'],
            ];
            
            foreach ($rows as $rowData) {
                $label = $rowData[0];
                $value = $rowData[1];
            
                $rangeAtoB = 'A' . $footerRow . ':B' . $footerRow;
                $sheet->mergeCells($rangeAtoB);
                $sheet->setCellValue('A' . $footerRow, $label);
            
                $rangeCtoG = 'C' . $footerRow . ':G' . $footerRow;
                $sheet->mergeCells($rangeCtoG);
            
                $sheet->setCellValue('C' . $footerRow, is_numeric($value) ? number_format((float)$value, 2) : $value);
            
                $rangeHtoL = 'H' . $footerRow . ':L' . $footerRow;
                $sheet->mergeCells($rangeHtoL);
            
                $sheet->setCellValue('H' . $footerRow, is_numeric($value) ? number_format((float)$value, 2) : $value);
            
                $footerStyleAtoB = $sheet->getStyle($rangeAtoB);
                $footerStyleAtoB->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerStyleCtoG = $sheet->getStyle($rangeCtoG);
                $footerStyleCtoG->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerStyleHtoL = $sheet->getStyle($rangeHtoL);
                $footerStyleHtoL->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerRow++;
            }

            $randomFilename = 'RPCPPE_Reports_' . uniqid() . '.xlsx';

            $copyFilename = 'RPCPPE_Reports_' . date('dmYHis') . '.xlsx';
            $copyPath = public_path("Downloaded Form/" . $copyFilename);
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($copyPath);
            
            return redirect()->back()->with('download', $copyFilename)->with('successcopy', 'File copied successfully!');
            
        }
    }

    public function rpcsepOption() {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $property = Property::whereIn('id', [1, 2])->get();
        $category = Category::all();

        return view('reports.rpcsep_option', compact('setting', 'office', 'property', 'category'));
    }
    
    public function rpcsepOptionReportGen(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $item = Item::all();
        $unit = Unit::all();
        $category = Category::all();

        $officeId = $request->query('office_id');
        $propertiesId = $request->query('properties_id');
        $propId = $request->query('property_id');
        $categoriesId = $request->query('categories_id');
        $selectId = $request->query('selected_account_id');
        $startDate = $request->query('start_date_acquired');
        $endDate = $request->query('end_date_acquired');

        // dd($propId);
        
        $condProperties = ($propertiesId == 'All') ? '!=' : '=';
        $condpropId = ($propId == 'All') ? '!=' : '=';
        $condCategories = ($categoriesId == 'All') ? '!=' : '=';
        $condselectId = ($selectId == 'All') ? '!=' : '=';
        
        // Sanitize and validate input if necessary
        
        $propertiesId = ($condProperties == 'All') ? '0' : $propertiesId;
        $propId = ($condpropId == 'All') ? '0' : $propId;
        $categoriesId = ($condCategories == 'All') ? '0' : $categoriesId;
        $selectId = ($condselectId == 'All') ? '0' : $selectId;
        
        $allOffice = ($officeId == 'All') ? '!=' : '=';
        $officeId = ($officeId == 'All') ? '0' : $officeId;
        
        $formattedStartDate = $startDate ? date('M. d, Y', strtotime($startDate)) : '';
        $formattedEndDate = $endDate ? date('M. d, Y', strtotime($endDate)) : '';

        // dd($propId);
        
        $purchase = Purchases::join('offices', 'purchases.office_id', '=', 'offices.id')
            ->join('properties', 'purchases.selected_account_id', '=', 'properties.id')
            ->join('units', 'purchases.unit_id', '=', 'units.id')
            ->join('items', 'purchases.item_id', '=', 'items.id')
            ->where(function ($query) use ($officeId, $allOffice) {
                if ($officeId == 1) {
                    $query->whereNotIn('purchases.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('purchases.office_id', $allOffice, $officeId);
                }
            })
            ->where('purchases.properties_id', $condProperties, $propertiesId)
            ->where('purchases.property_id', $condpropId, $propId)
            ->where('purchases.categories_id', $condCategories, $categoriesId)
            ->where(function ($query) use ($selectId) {
                if (!empty($selectId)) {
                    $query->where('purchases.selected_account_id', $selectId);
                }
            })
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('purchases.date_acquired', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('purchases.date_acquired', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('purchases.date_acquired', '<=', $endDate);
                }
            })
            ->get();        
            
        $purchase1 = Purchases::join('offices', 'purchases.office_id', '=', 'offices.id')
            ->join('properties', 'purchases.selected_account_id', '=', 'properties.id')
            ->join('units', 'purchases.unit_id', '=', 'units.id')
            ->join('items', 'purchases.item_id', '=', 'items.id')
            ->where(function ($query) use ($officeId, $allOffice) {
                if ($officeId == 1) {
                    $query->whereNotIn('purchases.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('purchases.office_id', $allOffice, $officeId);
                }
            })
            ->where('purchases.properties_id', $condProperties, $propertiesId)
            ->where('purchases.property_id', $condpropId, $propId)
            ->where('purchases.categories_id', $condCategories, $categoriesId)
            ->where(function ($query) use ($selectId) {
                if (!empty($selectId)) {
                    $query->where('purchases.selected_account_id', $selectId);
                }
            })
            ->where(function ($query) use ($startDate) {
                if ($startDate) {
                    $query->where('purchases.date_acquired', '<', $startDate);
                }
            })
            ->get();


        $purchase2 = Purchases::join('offices', 'purchases.office_id', '=', 'offices.id')
            ->join('properties', 'purchases.selected_account_id', '=', 'properties.id')
            ->join('units', 'purchases.unit_id', '=', 'units.id')
            ->join('items', 'purchases.item_id', '=', 'items.id')
            ->where('purchases.office_id', $allOffice, $officeId)
            ->where('purchases.properties_id', $condProperties, $propertiesId)
            ->where('purchases.property_id', $condpropId, $propId)
            ->where('purchases.categories_id', $condCategories, $categoriesId)
            ->where(function ($query) use ($selectId) {
                if (!empty($selectId)) {
                    $query->where('purchases.selected_account_id', $selectId);
                }
            })
            ->where(function ($query) use ($endDate) {
                if ($endDate) {
                    $query->where('purchases.date_acquired', '>', $endDate);
                }
            })
            ->get();

        $bforward = $purchase1->sum(function ($purchase) {
            return str_replace(',', '', $purchase->total_cost);
        });

        $bforward1 = $purchase2->isEmpty() ? 0 : $purchase2->sum(function ($purchase) {
            return str_replace(',', '', $purchase->total_cost);
        });


        $data = [
            'purchase' => $purchase,
            'startDate' => $formattedStartDate,
            'endDate' => $formattedEndDate,
            'selectedPropertyId' => $selectId,
            'category_id' => $purchase,
            'bforward' => $bforward, 
            'bforward1' => $bforward1, 
        ];

        if($request->file_type == "PDF"){
            $pdf = PDF::loadView('reports.rpcsep_option_reportsGen', $data)->setPaper('Legal', 'landscape');
            return $pdf->stream();
        }else {
            $filePath = public_path('Forms/RPCSEP Reports.xlsx');
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            
            $sheet = $spreadsheet->getActiveSheet();
            
            $row = 10;
            $overallTotal = 0;

            $sheetC3 = '';

            if (request('categories_id') === 'All' || empty($categoriesId)) {
                if (request('categories_id') === 'All')
                    $sheetC3 = 'ALL';
                else {
                    $sheetC3 = isset($purchase->first()->account_title_abbr) ? $purchase->first()->account_title_abbr : '';
                }
            } elseif ($purchase->isEmpty()) {
                $sheetC3 = '____________________';
            }
            
            $sheetC5 = 'As at ' . $formattedStartDate . ' to ' . $formattedEndDate;
            
            $sheet->mergeCells('C3:H3');
            $sheet->mergeCells('C5:H5');
            $sheet->setCellValue('C3:H3', $sheetC3);
            $sheet->setCellValue('C4', '(Type of Property, Plant and Equipment)');
            $sheet->setCellValue('C5', $sheetC5);

            $sheet->mergeCells('G9:L9');
            $sheet->setCellValue('G9', number_format($bforward, 2));
            
            foreach ($purchase as $pur) {
                
                if (is_numeric(str_replace(',', '', $pur->total_cost))){
                    $overallTotal += str_replace(',', '', $pur->total_cost); 
                }

                $sheet->setCellValue('A' . $row, $pur->item_name);
                $sheet->setCellValue('B' . $row, $pur->item_descrip);
                $sheet->setCellValue('C' . $row, $pur->property_no_generated);
                $sheet->setCellValue('D' . $row, $pur->unit_name);
                $sheet->setCellValue('E' . $row, $pur->item_cost);
                $sheet->setCellValue('F' . $row, $pur->qty);
                $sheet->setCellValue('G' . $row, $pur->total_cost);
                $sheet->setCellValue('H' . $row, '');
                $sheet->setCellValue('I' . $row, $pur->qty);
                $sheet->setCellValue('J' . $row, '');
                $sheet->setCellValue('K' . $row, $pur->remarks);
                $sheet->setCellValue('L' . $row, $pur->office_name);
            
                $style = $sheet->getStyle('A' . $row . ':L' . $row);
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $row++;

            }
            
            $footerRow = $row;

            $rows = [
                ['label' => 'Total', 'value' => $overallTotal],
                ['label' => 'Grand Total', 'value' => $overallTotal + $bforward + $bforward1],
            ];
            
            foreach ($rows as $rowData) {
                $label = $rowData['label'];
                $value = $rowData['value'];
            
                $rangeAtoF = 'A' . $footerRow . ':F' . $footerRow;
                $sheet->mergeCells($rangeAtoF);
                $sheet->setCellValue('A' . $footerRow, $label)
                ->getStyle('A' . $footerRow)
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
                $rangeGtoL = 'G' . $footerRow . ':L' . $footerRow;
                $sheet->mergeCells($rangeGtoL);
                $sheet->setCellValue('G' . $footerRow, number_format($value, 2));
            
                $footerStyleAtoF = $sheet->getStyle($rangeAtoF);
                $footerStyleAtoF->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $footerStyleAtoF->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerStyleGtoL = $sheet->getStyle($rangeGtoL);
                $footerStyleGtoL->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $footerStyleGtoL->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerRow++;
            }

            $footerRow = $row + 2;

            $rows = [
                ['Certified Correct by:', 'Approved by:', 'Verify by:'],
                ['', '', ''],
                ['BENJAMIN R. DELA TORRE', 'ALEXANDRA M. VILLAROSA', ''],
                ['Administrative Officer / Supply Officer', 'School Principal', 'Signature over Printed Name of COA Representative'],
            ];
            
            $rows = [
                ['Certified Correct by:', 'Approved by:', 'Verify by:'],
                ['', '', ''],
                ['BENJAMIN R. DELA TORRE', 'ALEXANDRA M. VILLAROSA', ''],
                ['Administrative Officer / Supply Officer', 'School Principal', 'Signature over Printed Name of COA Representative'],
            ];
            
            foreach ($rows as $rowData) {
                $label = $rowData[0];
                $value = $rowData[1];
            
                $rangeAtoB = 'A' . $footerRow . ':B' . $footerRow;
                $sheet->mergeCells($rangeAtoB);
                $sheet->setCellValue('A' . $footerRow, $label);
            
                $rangeCtoG = 'C' . $footerRow . ':G' . $footerRow;
                $sheet->mergeCells($rangeCtoG);
            
                $sheet->setCellValue('C' . $footerRow, is_numeric($value) ? number_format((float)$value, 2) : $value);
            
                $rangeHtoL = 'H' . $footerRow . ':L' . $footerRow;
                $sheet->mergeCells($rangeHtoL);
            
                $sheet->setCellValue('H' . $footerRow, is_numeric($value) ? number_format((float)$value, 2) : $value);
            
                $footerStyleAtoB = $sheet->getStyle($rangeAtoB);
                $footerStyleAtoB->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerStyleCtoG = $sheet->getStyle($rangeCtoG);
                $footerStyleCtoG->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerStyleHtoL = $sheet->getStyle($rangeHtoL);
                $footerStyleHtoL->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $footerRow++;
            }

            $randomFilename = 'RPCPPE_Reports_' . uniqid() . '.xlsx';

            $copyFilename = 'RPCPPE_Reports_' . date('dmYHis') . '.xlsx';
            $copyPath = public_path("Downloaded Form/" . $copyFilename);
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($copyPath);
            
            return redirect()->back()->with('download', $copyFilename)->with('successcopy', 'File copied successfully!');
            
        }
    }

    public function icsOption() {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $property = Property::all();
        $category = Category::all();
        $purchase = Purchases::join('accountable', 'purchases.person_accnt', '=', 'accountable.id')
                    ->select('purchases.*', 'accountable.person_accnt')
                    ->get();
                    
        return view('reports.ics_option', compact('setting', 'office', 'property', 'category', 'purchase'));
    }

    public function icsOptionReportGen(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
         
        $id = $request->person_accnt;
        $itemId = $request->item_id;
        $pAccountable = $request->pAccountable;

        $selectedItem = Item::whereIn('id', $itemId)->get();
        $condAccnt = ($pAccountable == 'accountable') ? 'purchases.person_accnt' : 'purchases.office_id';

        if($pAccountable == 'accountable'){
            $relatedItems = Item::join('purchases', 'items.id', '=', 'purchases.item_id')
                ->join('units', 'purchases.unit_id', '=', 'units.id')
                ->join('offices', 'purchases.office_id', '=', 'offices.id')
                ->join('accountable', 'purchases.person_accnt', '=', 'accountable.id')
                ->select('purchases.*', 'items.*', 'offices.*', 'items.id as itemid', 'units.*', 'accountable.person_accnt')
                ->whereIn('purchases.id', $itemId)
                ->where($condAccnt, $id)
                ->get();
        }
        else{
         $relatedItems = Purchases::join('items', 'purchases.item_id', '=', 'items.id')
            ->join('units', 'purchases.unit_id', '=', 'units.id')
            ->join('offices', 'purchases.office_id', '=', 'offices.id')
            ->select('purchases.*', 'items.*', 'offices.*', 'offices.id as oid', 'items.id as itemid', 'units.*', 'offices.office_abbr', 'offices.office_officer')
            ->whereIn('purchases.id', $itemId)
            ->where($condAccnt, $id)
            ->get();
        }

        $data = [
            'selectedItem' => $selectedItem,
            'relatedItems' => $relatedItems,
        ];

        $pdf = PDF::loadView('reports.ics_option_reportsGen', $data)->setPaper('Legal', 'portrait');
        return $pdf->stream();
    }

    public function icsgenOption(Request $request) {
        $categoriesId = $request->categories_id;
        $propId = $request->property_id;
        $id = $request->id;
        $type = $request->type;
        $pAccountable = $request->pAccountable;

        $condcategories = ($categoriesId == 'All') ? '!=' : '=';
        $categoriesId = ($categoriesId == 'All') ? '0' : $categoriesId;

        $condpropid = ($propId == 'All') ? '!=' : '=';
        $propId = ($propId == 'All') ? '0' : $propId;

        // if ($type == 'SCHOOL') {
        //     $userAccountable = Accountable::where('off_id', $id)
        //         ->select('person_accnt', 'id')
        //         ->get();

        //     $officeAccountable = Office::where('id', $id)
        //         ->select('office_officer', 'id')
        //         ->get();

        //     $options = "";
        //     foreach ($userAccountable as $accnt) {
        //         $options .= "<option value='".$accnt->id."' data-person-cat='accountable' data-account-id='".$accnt->id."'>".$accnt->person_accnt."</option>";
        //     }
        //     foreach ($officeAccountable as $officeAccount) {
        //         $options .= "<option value='".$officeAccount->id."' data-person-cat='officeAccountable' data-account-id='".$officeAccount->id."'>".$officeAccount->office_officer."- Office Head</option>";
        //     }


        // } else {
            if($pAccountable == 'officeAccountable'){
                $itempar = Purchases::join('items', 'items.id', '=', 'purchases.item_id')
                ->select('purchases.*', 'items.*', 'purchases.id as pid')
                ->where('inventories.categories_id', $condcategories, $categoriesId)
                ->where('inventories.property_id', $condpropid, $propId)
                ->where('office_id', $id)
                ->whereIn('purchases.properties_id', ['1', '2'])
                ->get();
            }else{
                 $itempar = Purchases::where('person_accnt', $id)
                ->join('items', 'items.id', '=', 'purchases.item_id')
                ->select('purchases.*', 'items.*', 'purchases.id as pid')
                ->where('inventories.categories_id', $condcategories, $categoriesId)
                ->where('inventories.property_id', $condpropid, $propId)
                ->whereIn('purchases.properties_id', ['1', '2'])
                ->get();
            }


        // }

        return response()->json([
            "options" => $options,
        ]);
    }

    public function parOption() {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        
        return view('reports.par_option', compact('setting', 'office'));
    }

    public function parOptionReportGen(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
         
        $id = $request->person_accnt;
        $itemId = $request->item_id;
        $pAccountable = $request->pAccountable;

        $selectedItem = Item::whereIn('id', $itemId)->get();
        $condAccnt = ($pAccountable == 'accountable') ? 'purchases.person_accnt' : 'purchases.office_id';

        if($pAccountable == 'accountable'){
            $relatedItems = Item::join('purchases', 'items.id', '=', 'purchases.item_id')
                ->join('units', 'purchases.unit_id', '=', 'units.id')
                ->join('offices', 'purchases.office_id', '=', 'offices.id')
                ->join('accountable', 'purchases.person_accnt', '=', 'accountable.id')
                ->select('purchases.*', 'items.*', 'offices.*', 'items.id as itemid', 'units.*', 'accountable.person_accnt')
                ->whereIn('purchases.id', $itemId)
                ->where($condAccnt, $id)
                ->get();
        }
        else{
         $relatedItems = Purchases::join('items', 'purchases.item_id', '=', 'items.id')
            ->join('units', 'purchases.unit_id', '=', 'units.id')
            ->join('offices', 'purchases.office_id', '=', 'offices.id')
            ->select('purchases.*', 'items.*', 'offices.*', 'offices.id as oid', 'items.id as itemid', 'units.*', 'offices.office_abbr', 'offices.office_officer')
            ->whereIn('purchases.id', $itemId)
            ->where($condAccnt, $id)
            ->get();
        }

        $data = [
            'selectedItem' => $selectedItem,
            'relatedItems' => $relatedItems,
        ];

        $pdf = PDF::loadView('reports.par_option_reportsGen', $data)->setPaper('Legal', 'portrait');
        return $pdf->stream();
    }

    public function genOption(Request $request) {
        $id = $request->id;
        $type = $request->type;
        $pAccountable = $request->pAccountable;

        if ($type == 'SCHOOL') {
            $userAccountable = Accountable::where('off_id', $id)
                ->select('person_accnt', 'id')
                ->get();

            $officeAccountable = Office::where('id', $id)
                ->select('office_officer', 'id')
                ->get();

            $options = "";
            foreach ($userAccountable as $accnt) {
                $options .= "<option value='".$accnt->id."' data-person-cat='accountable' data-account-id='".$accnt->id."'>".$accnt->person_accnt."</option>";
            }
            foreach ($officeAccountable as $officeAccount) {
                $options .= "<option value='".$officeAccount->id."' data-person-cat='officeAccountable' data-account-id='".$officeAccount->id."'>".$officeAccount->office_officer."- Office Head</option>";
            }


        } else {
            if($pAccountable == 'officeAccountable'){
                $itempar = Purchases::join('items', 'items.id', '=', 'purchases.item_id')
                ->select('purchases.*', 'items.*', 'purchases.id as pid')
                ->where('office_id', $id)
                ->where('purchases.properties_id', '=', '3')
                ->get();
            }else{
                 $itempar = Purchases::where('person_accnt', $id)
                ->join('items', 'items.id', '=', 'purchases.item_id')
                ->select('purchases.*', 'items.*', 'purchases.id as pid')
                ->where('purchases.properties_id', '=', '3')
                ->get();
            }

            $options = "";
            foreach ($itempar as $parItem) {
                $options .= "<option value='".$parItem->pid."'>".$parItem->item_name.' '.$parItem->item_descrip."</option>";
            }
        }

        return response()->json([
            "options" => $options,
        ]);
    }

}


