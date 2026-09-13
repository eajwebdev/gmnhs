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
    public function reportOption($repcat) {
        $setting = Setting::firstOrNew(['id' => 1]);
        
        $office = Office::leftJoin('schools', 'offices.school_id', '=', 'schools.id')
        ->select('offices.*', 'schools.school_abbr') 
        ->get();
        
        $uid = auth()->user()->school_id;
        $uoffice = Office::where('school_id', $uid)->first();
        $property = Property::all();
        $category = Category::all();

        return view('reports.report_option', compact('setting', 'uoffice', 'office', 'property', 'category', 'repcat'));
    }

    public function reportOptionView(Request $request) {
        ini_set('memory_limit', '2G');  
        set_time_limit(600);            

        $schoolId = auth()->user()->school_id;
        $exists = Office::whereNotNull('school_id')
            ->where('school_id', $schoolId)
            ->exists();
        $repcat = $request->repcat;
        // dd($repcat);
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $item = Item::all();
        $unit = Unit::all();
        $category = Category::all();

        $serial = $request->serial;
        $acquired = $request->acquired;
        $locationcolumn = $request->locationcolumn;
        // dd($serial);

        $officeId = $request->office_id;
        $officeId1 = $request->office_id;
        $propertiesId = $request->properties_id;
        $categoriesId = $request->categories_id;
        $propId = $request->property_id;
        $startDate = $request->start_date_acquired;
        $endDate = $request->end_date_acquired;
        $selectId = $request->selected_account_id;

        $formattedStartDate = $startDate ? date('M. d, Y', strtotime($startDate)) : '';
        $formattedEndDate = $endDate ? date('M. d, Y', strtotime($endDate)) : '';

        $allOffice = ($officeId == 'All') ? '!=' : '=';

        $location = $request->input('location');
        // dd($selectId);
        $purchase = EnduserProperty::join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->leftJoin('offices as locations', 'enduser_property.location', '=', 'locations.id')
            ->join('properties', 'enduser_property.selected_account_id', '=', 'properties.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            ->select('enduser_property.*', 'offices.*', 'properties.*', 'units.*', 'items.*', 'locations.office_abbr as itemlocated')
            ->where('enduser_property.deleted', 0)
            ->where(function ($query) use ($officeId, $allOffice) {
                if ($officeId == 1) {
                    $query->whereNotIn('enduser_property.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('enduser_property.office_id', $allOffice, $officeId);
                }
            })
            ->when(!empty($propertiesId), function ($query) use ($propertiesId) {
                if (is_array($propertiesId)) {
                    $query->whereIn('enduser_property.properties_id', $propertiesId);
                } else {
                    $query->where('enduser_property.properties_id', $propertiesId);
                }
            })
            ->where(function ($query) use ($categoriesId) {
                if ($categoriesId !== 'All') {
                    $query->where('enduser_property.categories_id', $categoriesId);
                }
            })
            ->where(function ($query) use ($propId) {
                if ($propId !== 'All') {
                    $query->where('enduser_property.property_id', $propId);
                }
            })
            // ->where(function ($query) use ($selectId) {
            //     if ($selectId !== 'All') {
            //         $query->where('enduser_property.selected_account_id', $selectId);
            //     }
            // })
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('enduser_property.date_acquired', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('enduser_property.date_acquired', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('enduser_property.date_acquired', '<=', $endDate);
                }
            })
            ->where(function ($query) use ($location) {
                if ($location === 'All') {
                    // No filter applied — get everything including nulls
                } elseif ($location === 'null') {
                    $query->whereNull('enduser_property.location');
                } elseif (!empty($location)) {
                    $query->where('enduser_property.location', $location);
                }
            });

        if ($exists) {
            $purchase->where('offices.school_id', $schoolId);
        }

        $purchase = $purchase->get();

        $cond = ($categoriesId == 'All') ? '!=' : '=';

        $baseQuery = EnduserProperty::join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->join('properties', 'enduser_property.selected_account_id', '=', 'properties.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            ->where('enduser_property.deleted', 0)
            ->when(!empty($propertiesId), function ($query) use ($propertiesId) {
                if (is_array($propertiesId)) {
                    $query->whereIn('enduser_property.properties_id', $propertiesId);
                } else {
                    $query->where('enduser_property.properties_id', $propertiesId);
                }
            })
            ->where(function ($query) use ($categoriesId) {
                if ($categoriesId !== 'All') {
                    $query->where('enduser_property.categories_id', $categoriesId);
                }
            })
            ->where(function ($query) use ($propId) {
                if ($propId !== 'All') {
                    $query->where('enduser_property.property_id', $propId);
                }
            })
            // ->where('enduser_property.selected_account_id', $cond, $selectId)
            ->when($exists, function ($query) use ($schoolId) {
                $query->where('offices.school_id', $schoolId);
            });

        // PURCHASE 1
        $purchase1Query = clone $baseQuery;
        $purchase1Query->where(function ($query) use ($officeId, $cond) {
            if ($officeId == 1) {
                $query->whereNotIn('enduser_property.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
            } else {
                $query->where('enduser_property.office_id', $cond, $officeId);
            }
        });
        if ($startDate) {
            $purchase1Query->where('enduser_property.date_acquired', '<', $startDate);
        }
        $purchase1 = $purchase1Query->selectRaw("
            SUM(total_cost) as total_cost_sum,
            SUM(qty) as qty_sum
        ")->first();

        // PURCHASE 2
        $purchase2Query = clone $baseQuery;
        $purchase2Query->where(function ($query) use ($officeId, $allOffice, $cond) {
            if ($officeId == 1) {
                $query->whereNotIn('enduser_property.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
            } else {
                $query->where('enduser_property.office_id', $cond, $allOffice);
            }
        });
        if ($endDate) {
            $purchase2Query->where('enduser_property.date_acquired', '>', $endDate);
        }
        $purchase2 = $purchase2Query->selectRaw("
            SUM(total_cost) as total_cost_sum,
            SUM(qty) as qty_sum
        ")->first();

        // Assign sums
        $bforward  = $purchase1->total_cost_sum ?? 0;
        $bforward1 = $purchase2->total_cost_sum ?? 0;
        $countBforward  = $purchase1->qty_sum ?? 0;
        $countBforward1 = $purchase2->qty_sum ?? 0;

        $data = [
            'purchase' => $purchase,
            'startDate' => $formattedStartDate,
            'endDate' => $formattedEndDate,
            'selectedPropertyId' => $selectId,
            'category_id' => $purchase,
            'bforward' => $bforward, 
            'bforward1' => $bforward1, 
            'countBforward' => $countBforward,
            'countBforward1' => $countBforward1,
            'locationcolumn' => $locationcolumn,
            'serial' => $serial,
            'acquired' => $acquired,
        ];

        $page = ($repcat == 1)
            ? 'reports.report_generated'
            : (($repcat == 2)
                ? 'reports.rpcsep_option_reportsGen'
                : '');

        if($request->file_type == "PDF"){
                $pdf = Snappy::loadView($page, $data)
                    ->setPaper('Legal')
                    ->setOrientation('landscape')
                    ->setOption('disable-smart-shrinking', true)
                    ->setOption('no-stop-slow-scripts', true)
                    ->setOption('quiet', true);

                return $pdf->inline();
        }else {
            $filePath = public_path('Forms/RPCPPE Reports.xlsx');
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

            $sheet = $spreadsheet->getActiveSheet();
            
            $row = 10;
            $overallTotal = 0;

            $sheetC3 = '';

            $sheetC3 = (($request->categories_id) != "All")  ? isset($purchase->first()->account_title_abbr) ? $purchase->first()->account_title_abbr : '' : 'All';
        
    
            
            $sheetC5 = 'As at ' . $formattedStartDate . ' to ' . $formattedEndDate;
            
            $sheet->mergeCells('C3:H3');
            $sheet->mergeCells('C5:H5');
            $sheet->setCellValue('C3', $sheetC3);
            $sheet->setCellValue('C4', '(Type of Property, Plant and Equipment)');
            $sheet->setCellValue('C5', $sheetC5);
            $sheet->setCellValue('F9', $countBforward);
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
                if ($serial == 1) {
                    $sheet->setCellValue('M' . $row, $pur->serial_number ?? 'N/A');
                }
                if ($acquired == 1) {
                    $sheet->setCellValue('N' . $row, $pur->date_acquired ?? 'N/A');
                }
                $style = $sheet->getStyle('A' . $row . ':L' . $row);
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            
                $row++;

            }
            
            $footerRow = $row;

            $rows = [
                ['label' => 'Total' . $countBforward1, 'value' => $overallTotal],
                ['label' => 'Grand Total' . $countBforward + $countBforward1, 'value' => $overallTotal + $bforward + $bforward1],
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
                ['BENJAMIN R. DELA TORRE', 'ALEXANDRA M. VILLAROSA', 'DANIEL P. MONTECLARO'],
                ['Administrative Officer / Supply Officer', 'School Principal', 'State Auditor IV'],
            ];
            
            $rows = [
                ['Certified Correct by:', 'Approved by:', 'Verify by:'],
                ['', '', ''],
                ['BENJAMIN R. DELA TORRE', 'ALEXANDRA M. VILLAROSA', 'DANIEL P. MONTECLARO'],
                ['Administrative Officer / Supply Officer', 'School Principal', 'State Auditor IV'],
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

    public function rpcppeOption() {
        $setting = Setting::firstOrNew(['id' => 1]);
        
        $office = Office::leftJoin('schools', 'offices.school_id', '=', 'schools.id')
        ->select('offices.*', 'schools.school_abbr') 
        ->get();

        $uid = auth()->user()->school_id;
        $uoffice = Office::where('school_id', $uid)->first();
        $property = Property::whereIn('id', [3])->get();
        $category = Category::all();

        return view('reports.rpcppe_option', compact('setting', 'uoffice', 'office', 'property', 'category'));
    }
    
    public function rpcppeOptionReportGen(Request $request) {
        $schoolId = auth()->user()->school_id;
        $exists = Office::whereNotNull('school_id')
            ->where('school_id', $schoolId)
            ->exists();

        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $item = Item::all();
        $unit = Unit::all();
        $category = Category::all();

        $serial = $request->serial;
        $acquired = $request->acquired;
        $locationcolumn = $request->locationcolumn;
        
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

        $location = $request->input('location');

        $purchase = EnduserProperty::join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->leftJoin('offices as locations', 'enduser_property.location', '=', 'locations.id')
            ->join('properties', 'enduser_property.selected_account_id', '=', 'properties.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            ->select('enduser_property.*', 'offices.*', 'properties.*', 'units.*', 'items.*', 'locations.office_abbr as itemlocated')
            ->where(function ($query) use ($officeId, $allOffice, $cond) {
                if ($officeId == 1) {
                    $query->whereNotIn('enduser_property.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('enduser_property.office_id', $allOffice, $officeId);
                }
            })
            ->where('enduser_property.deleted', 0)
            ->where('enduser_property.properties_id', $propertiesId)
            ->where('enduser_property.categories_id', $cond, $categoriesId)
            ->where('enduser_property.property_id', $cond, $propId)
            ->where('enduser_property.selected_account_id', $cond, $selectId)
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('enduser_property.date_acquired', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('enduser_property.date_acquired', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('enduser_property.date_acquired', '<=', $endDate);
                }
            })
            ->where(function ($query) use ($location) {
                if ($location === 'All') {
                    // No filter applied — get everything including nulls
                } elseif ($location === 'null') {
                    $query->whereNull('enduser_property.location'); // N/A option
                } elseif (!empty($location)) {
                    $query->where('enduser_property.location', $location); // Specific location
                }
            });

        if ($exists){
            $purchase->where('offices.school_id', $schoolId);
        } 

        $purchase = $purchase->get();

        $purchase1 = EnduserProperty::join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->join('properties', 'enduser_property.selected_account_id', '=', 'properties.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            ->where(function ($query) use ($officeId, $allOffice, $cond) {
                if ($officeId == 1) {
                    $query->whereNotIn('enduser_property.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('enduser_property.office_id', $allOffice, $officeId);
                }
            })
            ->where('enduser_property.deleted', 0)
            ->where('enduser_property.properties_id', $propertiesId)
            ->where('enduser_property.categories_id', $cond, $categoriesId)
            ->where('enduser_property.property_id', $cond, $propId)
            ->where('enduser_property.selected_account_id', $cond, $selectId)
            ->where(function ($query) use ($startDate) {
                if ($startDate) {
                    $query->where('enduser_property.date_acquired', '<', $startDate);
                }
            });

        if ($exists){
            $purchase1->where('offices.school_id', $schoolId);
        } 
        
        $purchase1 = $purchase1->get();


        $purchase2 = EnduserProperty::join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->join('properties', 'enduser_property.selected_account_id', '=', 'properties.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            // ->where('enduser_property.office_id', $allOffice, $officeId)
            ->where(function ($query) use ($officeId, $allOffice, $cond) {
                if ($officeId == 1) {
                    $query->whereNotIn('enduser_property.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('enduser_property.office_id', $cond, $allOffice);
                }
            })
            ->where('enduser_property.deleted', 0)
            ->where('enduser_property.properties_id', $propertiesId)
            ->where('enduser_property.categories_id', $cond, $categoriesId)
            ->where('enduser_property.property_id', $cond, $propId)
            ->where('enduser_property.selected_account_id', $cond, $selectId)
            ->where(function ($query) use ($endDate) {
                if ($endDate) {
                    $query->where('enduser_property.date_acquired', '>', $endDate);
                }
            });

        if ($exists){
            $purchase2->where('offices.school_id', $schoolId);
        } 
        
        $purchase2 = $purchase2->get();

        $bforward = $purchase1->sum(function ($purchase1) {
            return str_replace(',', '', $purchase1->total_cost);
        });

        // Ensure $purchase2 is not null or empty before attempting to use it
        $bforward1 = $purchase2->isEmpty() ? 0 : $purchase2->sum(function ($purchase) {
            return str_replace(',', '', $purchase->total_cost);
        });

        $countBforward  = 0;
        $countBforward1 = 0;

        foreach($purchase1 as $purchaseData){
            if (is_numeric(str_replace(',', '', $purchaseData->qty))){
                $countBforward += (float) str_replace(',', '', $purchaseData->qty); 
            }
        }

        foreach($purchase as $purchaseData1){
            if (is_numeric(str_replace(',', '', $purchaseData1->qty))){
                $countBforward1 += (float) str_replace(',', '', $purchaseData1->qty);
            }
        }

        $data = [
            'purchase' => $purchase,
            'startDate' => $formattedStartDate,
            'endDate' => $formattedEndDate,
            'selectedPropertyId' => $selectId,
            'category_id' => $purchase,
            'bforward' => $bforward, 
            'bforward1' => $bforward1, 
            'countBforward' => $countBforward,
            'locationcolumn' => $locationcolumn,
            'serial' => $serial,
            'acquired' => $acquired,
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

            $sheetC3 = ($request->query('categories_id') != "All")  ? isset($purchase->first()->account_title_abbr) ? $purchase->first()->account_title_abbr : '' : 'All';
        
    
            
            $sheetC5 = 'As at ' . $formattedStartDate . ' to ' . $formattedEndDate;
            
            $sheet->mergeCells('C3:H3');
            $sheet->mergeCells('C5:H5');
            $sheet->setCellValue('C3', $sheetC3);
            $sheet->setCellValue('C4', '(Type of Property, Plant and Equipment)');
            $sheet->setCellValue('C5', $sheetC5);
            $sheet->setCellValue('F9', $countBforward);
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
                ['label' => 'Total' . $countBforward1, 'value' => $overallTotal],
                ['label' => 'Grand Total' . $countBforward + $countBforward1, 'value' => $overallTotal + $bforward + $bforward1],
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
                ['BENJAMIN R. DELA TORRE', 'ALEXANDRA M. VILLAROSA', 'DANIEL P. MONTECLARO'],
                ['Administrative Officer / Supply Officer', 'School Principal', 'State Auditor IV'],
            ];
            
            $rows = [
                ['Certified Correct by:', 'Approved by:', 'Verify by:'],
                ['', '', ''],
                ['BENJAMIN R. DELA TORRE', 'ALEXANDRA M. VILLAROSA', 'DANIEL P. MONTECLARO'],
                ['Administrative Officer / Supply Officer', 'School Principal', 'State Auditor IV'],
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

        $office = Office::leftJoin('schools', 'offices.school_id', '=', 'schools.id')
        ->select('offices.*', 'schools.school_abbr') 
        ->get();

        $uid = auth()->user()->school_id;
        $uoffice = Office::where('school_id', $uid)->first();
        $property = Property::whereIn('id', [1, 2])->get();
        $category = Category::all();

        return view('reports.rpcsep_option', compact('setting', 'uoffice', 'office', 'property', 'category'));
    }
    
    public function rpcsepOptionReportGen(Request $request) {
        $schoolId = auth()->user()->school_id;
        $exists = Office::whereNotNull('school_id')
            ->where('school_id', $schoolId)
            ->exists();

        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $item = Item::all();
        $unit = Unit::all();
        $category = Category::all();

        $serial = $request->serial;

        $officeId = $request->office_id;
        $propertiesId = $request->properties_id;
        $propId = $request->property_id;
        $categoriesId = $request->categories_id;
        $selectId = $request->selected_account_id;
        $startDate = $request->start_date_acquired;
        $endDate = $request->end_date_acquired;

        $condProperties = ($propertiesId == 'All') ? '!=' : '=';
        $condpropId = ($propId == 'All') ? '!=' : '=';
        $condCategories = ($categoriesId == 'All') ? '!=' : '=';
        $condselectId = ($selectId == 'All') ? '!=' : '=';
        
        $propertiesId = ($condProperties == 'All') ? '0' : $propertiesId;
        $categoriesId = ($condCategories == 'All') ? '0' : $categoriesId;
        $selectId = ($condselectId == 'All') ? '0' : $selectId;
        $propId = ($condpropId == 'All') ? '0' : $propId;

        // dd($propertiesId);
        
        $allOffice = ($officeId == 'All') ? '!=' : '=';
        $officeId = ($officeId == 'All') ? '0' : $officeId;
        
        $formattedStartDate = $startDate ? date('M. d, Y', strtotime($startDate)) : '';
        $formattedEndDate = $endDate ? date('M. d, Y', strtotime($endDate)) : '';
        
        $purchase = EnduserProperty::join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->join('properties', 'enduser_property.selected_account_id', '=', 'properties.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            ->where('enduser_property.deleted', 0)
            ->where(function ($query) use ($officeId, $allOffice) {
                if ($officeId == 1) {
                    $query->whereNotIn('enduser_property.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('enduser_property.office_id', $allOffice, $officeId);
                }
            })
            ->where(function ($query) use ($condProperties, $propertiesId) {
                if (!empty($propertiesId)) {
                    $query->where('enduser_property.properties_id', $condProperties, $propertiesId);
                }
            })
            ->where(function ($query) use ($condpropId, $propId) {
                if (!empty($propId)) {
                    $query->where('enduser_property.property_id', $condpropId, $propId);
                }
            })
            ->where('enduser_property.categories_id', $condCategories, $categoriesId)
            ->where(function ($query) use ($selectId) {
                if (!empty($selectId)) {
                    $query->where('enduser_property.selected_account_id', $selectId);
                }
            })
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('enduser_property.date_acquired', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('enduser_property.date_acquired', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('enduser_property.date_acquired', '<=', $endDate);
                }
            });
            
        if ($exists){
            $purchase->where('offices.school_id', $schoolId);
        } 

        $purchase = $purchase->get();
            
        $purchase1 = EnduserProperty::join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->join('properties', 'enduser_property.selected_account_id', '=', 'properties.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            ->where('enduser_property.deleted', 0)
            ->where(function ($query) use ($officeId, $allOffice) {
                if ($officeId == 1) {
                    $query->whereNotIn('enduser_property.office_id', [2, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17]);
                } else {
                    $query->where('enduser_property.office_id', $allOffice, $officeId);
                }
            })
            ->where(function ($query) use ($condProperties, $propertiesId) {
                if (!empty($propertiesId)) {
                    $query->where('enduser_property.properties_id', $condProperties, $propertiesId);
                }
            })
            ->where(function ($query) use ($condpropId, $propId) {
                if (!empty($propId)) {
                    $query->where('enduser_property.property_id', $condpropId, $propId);
                }
            })
            ->where('enduser_property.categories_id', $condCategories, $categoriesId)
            ->where(function ($query) use ($selectId) {
                if (!empty($selectId)) {
                    $query->where('enduser_property.selected_account_id', $selectId);
                }
            })
            ->where(function ($query) use ($startDate) {
                if ($startDate) {
                    $query->where('enduser_property.date_acquired', '<', $startDate);
                }
            })
            ->get();


        $purchase2 = EnduserProperty::join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->join('properties', 'enduser_property.selected_account_id', '=', 'properties.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            ->where('enduser_property.office_id', $allOffice, $officeId)
            ->where('enduser_property.deleted', 0)
            ->where(function ($query) use ($condProperties, $propertiesId) {
                if (!empty($propertiesId)) {
                    $query->where('enduser_property.properties_id', $condProperties, $propertiesId);
                }
            })
            ->where(function ($query) use ($condpropId, $propId) {
                if (!empty($propId)) {
                    $query->where('enduser_property.property_id', $condpropId, $propId);
                }
            })
            ->where('enduser_property.categories_id', $condCategories, $categoriesId)
            ->where(function ($query) use ($selectId) {
                if (!empty($selectId)) {
                    $query->where('enduser_property.selected_account_id', $selectId);
                }
            })
            ->where(function ($query) use ($endDate) {
                if ($endDate) {
                    $query->where('enduser_property.date_acquired', '>', $endDate);
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
            'serial' => $serial,
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

            if ($request->categories_id === 'All' || empty($categoriesId)) {
                if ($request->categories_id === 'All')
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

    public function unserviceForm(){
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $property = Property::all();
        $category = Category::all();

        return view('reports.unserviceable_form', compact('setting', 'office', 'property', 'category'));
    }

    public function unserviceReport(Request $request){
        $setting = Setting::firstOrNew(['id' => 1]);
        $officeId = $request->office_id;
        $personaccountable = $request->person_accnt;
        $itemId = $request->item_id;
        $pAccountable = $request->pAccountable;
        $categoriesId = $request->categories_id;
        $propId = $request->property_id;
        $startDate = $request->start_date_acquired;
        $endDate = $request->end_date_acquired;
        $selectId = $request->selected_account_id;

        $serial = $request->serial;
        
        $formattedStartDate = $startDate ? date('M. d, Y', strtotime($startDate)) : '';
        $formattedEndDate = $endDate ? date('M. d, Y', strtotime($endDate)) : '';
        $datereport = $formattedStartDate.'-'.$formattedEndDate;
        $condcategories = ($categoriesId == 'All') ? '!=' : '=';
        $condpropid = ($propId == 'All') ? '!=' : '=';
        $conaccountid = ($selectId == 'All' || $selectId == '') ? '!=' : '=';
        $officecond = ($officeId == 'All') ? '!=' : '=';
    
        $categoriesId = ($categoriesId == 'All') ? '0' : $categoriesId;
        $propId = ($propId == 'All') ? '0' : $propId;
        $selectId = ($selectId == 'All') ? '0' : $selectId;
        $officeId = ($officeId == 'All') ? '0' : $officeId;
    
        $selectedItem = Item::whereIn('id', $itemId)->get();
        $condAccnt = ($pAccountable == 'accountable') ? 'enduser_property.person_accnt' : 'enduser_property.office_id';
    
        $unservicequery = EnduserProperty::join('items', 'enduser_property.item_id', '=', 'items.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->select('enduser_property.*', 'items.*', 'offices.*',  'offices.id as oid', 'items.id as itemid', 'units.*', 'offices.office_abbr', 'offices.office_officer');
    
        if ($itemId[0] != 'All' && !in_array('All', $itemId)) {
            $unservicequery->whereIn('enduser_property.id', $itemId);
        }
    
        if ($pAccountable == 'accountable') {
            $unservicequery->where($condAccnt, $personaccountable);
        } else {
            $unservicequery->where('enduser_property.office_id', $officecond, $officeId);
        }
    
        $unservitems = $unservicequery
            ->where('enduser_property.categories_id', $condcategories, $categoriesId)
            ->where('enduser_property.property_id', $condpropid, $propId)
            ->where('enduser_property.selected_account_id', $conaccountid, $selectId)
            ->where('enduser_property.deleted', 0)
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('enduser_property.date_acquired', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                } elseif ($startDate) {
                    $query->where('enduser_property.date_acquired', '>=', $startDate . ' 00:00:00');
                } elseif ($endDate) {
                    $query->where('enduser_property.date_acquired', '<=', $endDate . ' 23:59:59');
                }
            })
            ->where('enduser_property.remarks', 'Unserviceable')
            ->get();

        if($unservitems->isNotEmpty()){
            $pdf = PDF::loadView('reports.unserviceable_report', compact('selectedItem', 'unservitems', 'serial', 'itemId', 'pAccountable', 'datereport'))->setPaper('Legal', 'landscape');
            return $pdf->stream();
        }else{
            return redirect()->back()->with('error', 'No Item Found Belong to this End User!');
        }
    }

    public function icsOption() {
        $setting = Setting::firstOrNew(['id' => 1]);

        $office = Office::leftJoin('schools', 'offices.school_id', '=', 'schools.id')
            ->select('offices.*', 'schools.school_abbr') 
            ->get();

        $uid = auth()->user()->school_id;
        $uoffice = Office::where('school_id', $uid)->first();
        $property = Property::all();
        $category = Category::all();
        $purchase = EnduserProperty::join('accountable', 'enduser_property.person_accnt', '=', 'accountable.id')
                    ->select('enduser_property.*', 'accountable.person_accnt')
                    ->get();
        if($uoffice){
            $accntables = Accountable::where('off_id', $uoffice->id)->get();
        }else{
            $accntables = Accountable::all();
        }
        return view('reports.ics_option', compact('setting', 'uoffice', 'office', 'property', 'category', 'purchase', 'accntables'));
    }

    public function icsOptionReportGen(Request $request) {
        $schoolId = auth()->user()->school_id;
        $exists = Office::whereNotNull('school_id')
            ->where('school_id', $schoolId)
            ->exists();

        $setting = Setting::firstOrNew(['id' => 1]);
        $officeId = $request->office_id;
        $personaccountable = $request->person_accnt;
        $itemId = $request->item_id;
        $pAccountable = $request->pAccountable;
        $categoriesId = $request->categories_id;
        $propId = $request->property_id;
        $startDate = $request->start_date_acquired;
        $endDate = $request->end_date_acquired;
        $selectId = $request->selected_account_id;

        $formattedStartDate = $startDate ? date('M. d, Y', strtotime($startDate)) : '';
        $formattedEndDate = $endDate ? date('M. d, Y', strtotime($endDate)) : '';
        $datereport = $formattedStartDate.'-'.$formattedEndDate;
        $condcategories = ($categoriesId == 'All') ? '!=' : '=';
        $condpropid = ($propId == 'All') ? '!=' : '=';
        $condsel = ($selectId == 'All' || $selectId == '') ? '!=' : '=';
        $officecond = ($officeId == 'All') ? '!=' : '=';
        $accntcond = ($personaccountable == 'All') ? '!=' : '=';

        $categoriesId = ($categoriesId == 'All') ? '0' : $categoriesId;
        $propId = ($propId == 'All') ? '0' : $propId;
        $selectId = ($selectId == 'All') ? '0' : $selectId;
        $officeId = ($officeId == 'All') ? '0' : $officeId;
        $paccount = ($personaccountable == 'All') ? '0' : $personaccountable;

        $selectedItem = Item::whereIn('id', $itemId)->get();

        $locationcolumn = $request->locationcolumn;

        $icsitemquery = EnduserProperty::join('items', 'enduser_property.item_id', '=', 'items.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->leftJoin('offices as locations', 'enduser_property.location', '=', 'locations.id')
            ->where('enduser_property.deleted', 0)
            ->select(
                'enduser_property.*',
                'items.*',
                'offices.*',
                'offices.id as oid',
                'enduser_property.person_accnt as person_accnt_id',
                'items.id as itemid',
                'units.*',
                'offices.office_abbr',
                'offices.office_officer',
                'locations.office_abbr as itemlocated'
            );
        
        if ($itemId[0] != 'All' && !in_array('All', $itemId)) {
            $icsitemquery->whereIn('enduser_property.id', $itemId);
        }
        
        if ($pAccountable == 'accountable') {
            $icsitemquery->where('enduser_property.person_accnt', $accntcond, $paccount);
        } else {
            $icsitemquery->where('enduser_property.office_id', $officecond, $officeId);
        }

        $icsitems = $icsitemquery
            ->whereIn('enduser_property.properties_id', [1, 2])
            ->where('enduser_property.categories_id', $condcategories, $categoriesId)
            ->where('enduser_property.property_id', $condpropid, $propId)
            ->where('enduser_property.selected_account_id', $condsel, $selectId)
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('enduser_property.date_acquired', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                } elseif ($startDate) {
                    $query->where('enduser_property.date_acquired', '>=', $startDate . ' 00:00:00');
                } elseif ($endDate) {
                    $query->where('enduser_property.date_acquired', '<=', $endDate . ' 23:59:59');
                }
            })
            
            ->where('enduser_property.item_cost', '<', 50000);

        if ($exists){
            $icsitems->where('offices.school_id', $schoolId);
        } 

        $icsitems = $icsitems->get();

        // dd($request->item_id);

        $pAccountable2 = $request->person_accnt1;

        if($icsitems->isNotEmpty()){
            $pdf = PDF::loadView('reports.ics_option_reportsGen', compact('selectedItem', 'icsitems', 'itemId', 'pAccountable', 'pAccountable2', 'datereport', 'locationcolumn'))->setPaper('Legal', 'portrait');
            return $pdf->stream();
        }else{
            return redirect()->back()->with('error', 'No Item Found Belong to this End User!');
        }
    }

    public function parOption() {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        $uid = auth()->user()->school_id;
        $uoffice = Office::where('school_id', $uid)->first();
        $property = Property::all();
        $category = Category::all();
        if($uoffice){
            $accntables = Accountable::where('off_id', $uoffice->id)->get();
        }else{
            $accntables = Accountable::all();
        }
        return view('reports.par_option', compact('setting', 'uoffice', 'office', 'property', 'accntables', 'category'));
    }

    public function parOptionReportGen(Request $request) {
        $schoolId = auth()->user()->school_id;
        $exists = Office::whereNotNull('school_id')
            ->where('school_id', $schoolId)
            ->exists();
        $setting = Setting::firstOrNew(['id' => 1]);
    
        $officeId = $request->office_id;
        $personaccountable = $request->person_accnt;
        $itemId = $request->item_id;
        $pAccountable = $request->pAccountable;
        $categoriesId = $request->categories_id;
        $propId = $request->property_id;
        $startDate = $request->start_date_acquired;
        $endDate = $request->end_date_acquired;
        $selectId = $request->selected_account_id;

        $formattedStartDate = $startDate ? date('M. d, Y', strtotime($startDate)) : '';
        $formattedEndDate = $endDate ? date('M. d, Y', strtotime($endDate)) : '';
        $datereport = $formattedStartDate.'-'.$formattedEndDate;
        $condcategories = ($categoriesId == 'All') ? '!=' : '=';
        $condpropid = ($propId == 'All') ? '!=' : '=';
        $conaccountid = ($selectId == 'All' || $selectId == '') ? '!=' : '=';
        $officecond = ($officeId == 'All') ? '!=' : '=';
    
        $categoriesId = ($categoriesId == 'All') ? '0' : $categoriesId;
        $propId = ($propId == 'All') ? '0' : $propId;
        $selectId = ($selectId == 'All') ? '0' : $selectId;
        $officeId = ($officeId == 'All') ? '0' : $officeId;
    
        $selectedItem = Item::whereIn('id', $itemId)->get();
        $condAccnt = ($pAccountable == 'accountable') ? 'enduser_property.person_accnt' : 'enduser_property.office_id';

        $locationcolumn = $request->locationcolumn;
    
        $paritemquery = EnduserProperty::join('items', 'enduser_property.item_id', '=', 'items.id')
            ->join('units', 'enduser_property.unit_id', '=', 'units.id')
            ->join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->leftJoin('offices as locations', 'enduser_property.location', '=', 'locations.id')
            ->select('enduser_property.*', 'items.*', 'offices.*', 'offices.id as oid', 'items.id as itemid', 'units.*', 'offices.office_abbr', 'offices.office_officer', 'locations.office_abbr as itemlocated');
    
        $itemId = $request->input('item_id');
        $itemId = is_array($itemId) ? $itemId : (array) $itemId;
        $itemId = array_filter($itemId);

        if (!empty($itemId) && !in_array('All', $itemId, true)) {
            $paritemquery->whereIn('enduser_property.id', $itemId);
        }
    
        if ($pAccountable == 'accountable') {
            $paritemquery->where($condAccnt, $personaccountable);
        } else {
            $paritemquery->where('enduser_property.office_id', $officecond, $officeId);
        }

        $paritems = $paritemquery
            ->where('enduser_property.item_cost', '>=', 50000)
            ->where('enduser_property.categories_id', $condcategories, $categoriesId)
            ->where('enduser_property.property_id', $condpropid, $propId)
            ->where('enduser_property.selected_account_id', $conaccountid, $selectId)
            ->where(function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('enduser_property.date_acquired', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                } elseif ($startDate) {
                    $query->where('enduser_property.date_acquired', '>=', $startDate . ' 00:00:00');
                } elseif ($endDate) {
                    $query->where('enduser_property.date_acquired', '<=', $endDate . ' 23:59:59');
                }
            });

        if ($exists){
            $paritems->where('offices.school_id', $schoolId);
        } 
        
        $paritems = $paritems->get();

        $pAccountable2 = $request->person_accnt1;

        if($paritems->isNotEmpty()){
            $pdf = PDF::loadView('reports.par_option_reportsGen', compact('selectedItem', 'paritems', 'itemId', 'pAccountable', 'pAccountable2', 'datereport', 'locationcolumn'))->setPaper('Legal', 'portrait');
            return $pdf->stream();
        }else{
            return redirect()->back()->with('error', 'No Item Found Belong to this End User!');
        }
        
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
                $itempar = EnduserProperty::join('items', 'items.id', '=', 'enduser_property.item_id')
                ->select('enduser_property.*', 'items.*', 'enduser_property.id as pid')
                ->where('office_id', $id)
                ->get();
            }else{
                 $itempar = EnduserProperty::where('person_accnt', $id)
                ->join('items', 'items.id', '=', 'enduser_property.item_id')
                ->select('enduser_property.*', 'items.*', 'enduser_property.id as pid')
                ->get();
            }

            $options = "";
            $options .= "<option>All</option>";
            foreach ($itempar as $parItem) {
                $options .= "<option value='".$parItem->pid."'>".$parItem->item_name.' '.$parItem->item_descrip."</option>";
            }
        }

        return response()->json([
            "options" => $options,
        ]);
    }
    
    public function allgenOption(Request $request) {
        $id = $request->id;
        $type = $request->type;
        $properties_id = $request->properties_id;
        $startDate = $request->start_date_acquired;
        $endDate = $request->end_date_acquired;

        if ($properties_id == 'ics') {
            $propertiesid = [1, 2];
        } elseif ($properties_id == 'par') {
            $propertiesid = [3];
        } else {
            $propertiesid = [1, 2, 3];
        }

        $categoriesId = $request->category;
        $propId = $request->accnt_title;
        $selaccnt = $request->selected_account_id;

        $condcategories = ($categoriesId == 'All') ? '!=' : '=';
        $categoriesId = ($categoriesId == 'All') ? '0' : $categoriesId;

        $condpropid = ($propId == 'All') ? '!=' : '=';
        $propId = ($propId == 'All') ? '0' : $propId;

        $condselaccnt = ($selaccnt == 'All') ? '!=' : '=';
        $selaccnt = ($selaccnt == 'All') ? '0' : $selaccnt;

        if ($type == 'SCHOOL') {
            $office = Office::find($id);
            
            $endusers = "<option value=''>None</option>";

            if ($office->school_id == '') { 
               $userAccountable = Accountable::where('off_id', $id)
                    ->select('person_accnt', 'id')
                    ->get();

                $options = "<option value=''>Select Accountable</option>";
            
                foreach ($userAccountable as $accnt) {
                    $roleLabel = '';
                    if ($accnt->accnt_role == 1) {
                        $roleLabel = ' - HEAD';
                    }

                    $options .= "<option value='" . $accnt->id . "' data-person-cat='accountable' data-account-id='" . $accnt->id . "'>"
                        . $accnt->person_accnt . $roleLabel . "</option>";
                }
            }else{
                $userAccountable = Accountable::where('off_id', $id)
                    ->where('accnt_role', 2)
                    ->select('person_accnt', 'id')
                    ->get();

                $options = "<option value=''>Select Accountable</option>";
            
                foreach ($userAccountable as $accnt) {
                    $roleLabel = ' - OFFICE HEAD';

                    $options .= "<option value='" . $accnt->id . "' data-person-cat='accountable' data-account-id='" . $accnt->id . "'>"
                        . $accnt->person_accnt . $roleLabel . "</option>";
                }

                $endUsersAccountable = Accountable::where('off_id', $id)
                    ->where('accnt_role', '!=', 2)
                    ->select('person_accnt', 'id')
                    ->get();

                $endusers = "<option value='All'>All</option>";

                foreach ($endUsersAccountable as $accnt) {
                    $roleLabel = '';
                    if ($accnt->accnt_role == 1) {
                        $roleLabel = ' - HEAD';
                    }

                    $endusers .= "<option value='" . $accnt->id . "' data-person-cat='accountable' data-account-id='" . $accnt->id . "'>"
                        . $accnt->person_accnt . $roleLabel . "</option>";
                }

            }

            // Get the SCHOOL location options
            $officeloc = Office::find($id);
            $locations = Office::where('office_code', '0000')
                ->when($officeloc->school_id, function($query) use ($officeloc) {
                    return $query->where('school_id', $officeloc->school_id);
                }, function($query) {
                    return $query->where('school_id', 1)
                        ->where(function($q) {
                            $q->whereNull('school_id')
                            ->orWhere('school_id', '');
                        });
                })
                ->get();

            $locoptions = "<option value='All' selected>All Locations</option>";
            foreach ($locations as $location) {
                $locoptions .= "<option value='" . $location->id . "'>" . htmlspecialchars($location->office_name) . "</option>";
            }
        } else {
            $itempar = EnduserProperty::query()
                ->where(function ($q) use ($id) {
                    $q->where('person_accnt', $id)
                    ->orWhere('person_accnt1', $id);
                })
                ->join('items', 'items.id', '=', 'enduser_property.item_id')
                ->select('enduser_property.*', 'items.*', 'enduser_property.id as pid')
                ->when($properties_id == 'ics', function ($query) {
                    return $query->whereIn('enduser_property.properties_id', [1, 2]);
                })
                ->when($properties_id == 'par', function ($query) {
                    return $query->where('enduser_property.properties_id', 3)
                                ->where('enduser_property.item_cost', '>=', 50000);
                })
                ->where('enduser_property.categories_id', $condcategories, $categoriesId)
                ->where('enduser_property.property_id', $condpropid, $propId)
                ->when($startDate || $endDate, function ($query) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        $query->whereBetween('enduser_property.date_acquired', [
                            $startDate . ' 00:00:00', 
                            $endDate   . ' 23:59:59'
                        ]);
                    } elseif ($startDate) {
                        $query->where('enduser_property.date_acquired', '>=', $startDate . ' 00:00:00');
                    } elseif ($endDate) {
                        $query->where('enduser_property.date_acquired', '<=', $endDate . ' 23:59:59');
                    }
                })
                ->get();

            $options = "<option value=''>Select Items</option>";
            foreach ($itempar as $icsItem) {
                $options .= "<option value='" . $icsItem->pid . "'>"
                    . $icsItem->item_name . ' ' 
                    . $icsItem->item_descrip 
                    . ' (Acquired: ' . date('Y-m-d', strtotime($icsItem->date_acquired)) . ")"
                    .$icsItem->item_cost
                    . "</option>";
            }

            // For non-SCHOOL type, return empty location options
            $locoptions = "<option value='All' selected>All Locations</option>";
            $endusers = "<option value=''>None</option>";
        }

        return response()->json([
            "options" => $options,
            "endusers" => $endusers,
            "location" => $locoptions,
        ]);
    }
        
    // public function allgenOption(Request $request) {
    //     $id = $request->id;
    //     $type = $request->type;
    //     $pAccountable = $request->pAccountable;
    //     $properties_id = $request->properties_id;

    //     if($properties_id == 'ics'){
    //         $propertiesid = [1, 2];
    //     }elseif($properties_id == 'par'){
    //         $propertiesid = [3];
    //     }else{
    //         $propertiesid = [1,2,3];
    //     }

    //     $categoriesId = $request->category;
    //     $propId = $request->accnt_title;
    //     $selaccnt = $request->selected_account_id;

    //     $condcategories = ($categoriesId == 'All') ? '!=' : '=';
    //     $categoriesId = ($categoriesId == 'All') ? '0' : $categoriesId;

    //     $condpropid = ($propId == 'All') ? '!=' : '=';
    //     $propId = ($propId == 'All') ? '0' : $propId;

    //     $condselaccnt = ($selaccnt == 'All') ? '!=' : '=';
    //     $selaccnt = ($selaccnt == 'All') ? '0' : $selaccnt;

    //     if ($type == 'SCHOOL') {
    //         $userAccountable = Accountable::where('off_id', $id)
    //             ->select('person_accnt', 'id')
    //             ->get();

    //         $officeAccountable = Office::where('id', $id)
    //             ->select('office_officer', 'id')
    //             ->get();

    //         $options = "";
    //         foreach ($userAccountable as $accnt) {
    //             $options .= "<option value='".$accnt->id."' data-person-cat='accountable' data-account-id='".$accnt->id."'>".$accnt->person_accnt."</option>";
    //         }
    //         foreach ($officeAccountable as $officeAccount) {
    //             $options .= "<option value='".$officeAccount->id."' data-person-cat='officeAccountable' data-account-id='".$officeAccount->id."'>".$officeAccount->office_officer."- Office Head</option>";
    //         }

    //     } else {
    //         if($pAccountable == 'officeAccountable'){
    //             $itempar = EnduserProperty::join('items', 'items.id', '=', 'enduser_property.item_id')
    //             ->select('enduser_property.*', 'items.*', 'enduser_property.id as pid')
    //             ->where('office_id', $id)
    //             ->whereIn('enduser_property.properties_id', $propertiesid)
    //             ->when($properties_id == 'ics', function ($query) {
    //                 return $query->whereIn('enduser_property.properties_id', [1, 2]);
    //             })
    //             ->when($properties_id == 'par', function ($query) {
    //                 return $query->whereIn('enduser_property.properties_id', [3]);
    //             })
    //             ->when($properties_id == 'unserv', function ($query) {
    //                 return $query->whereIn('enduser_property.properties_id', [1, 2, 3]);
    //             })
    //             ->where('enduser_property.property_id', $condpropid, $propId)
    //             ->get();
    //         }else{
    //             $itempar = EnduserProperty::where('person_accnt', $id)
    //             ->join('items', 'items.id', '=', 'enduser_property.item_id')
    //             ->select('enduser_property.*', 'items.*', 'enduser_property.id as pid')
    //             ->when($properties_id == 'ics', function ($query) {
    //                 return $query->whereIn('enduser_property.properties_id', [1, 2]);
    //             })
    //             ->when($properties_id == 'par', function ($query) {
    //                 return $query->whereIn('enduser_property.properties_id', [3]);
    //             })
    //             ->when($properties_id == 'unserv', function ($query) {
    //                 return $query->whereIn('enduser_property.properties_id', [1, 2, 3]);
    //             })
    //             ->where('enduser_property.categories_id', $condcategories, $categoriesId)
    //             ->where('enduser_property.property_id', $condpropid, $propId)
    //             // ->where('enduser_property.selected_account_id ', $selaccnt)
    //             ->get();            
    //         }

    //         $options = "";
    //         $options .= "<option>All</option>";
    //         foreach ($itempar as $icsItem) {
    //             $options .= "<option value='".$icsItem->pid."'>".$icsItem->item_name.' '.$icsItem->item_descrip."</option>";
    //         }
    //     }

    //     return response()->json([
    //         "options" => $options,
    //     ]);
    // }


}


