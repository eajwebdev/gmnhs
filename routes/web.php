<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\ViewController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvSetting;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RpcppeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PropertyTypeController;
use App\Http\Controllers\PropertyTypeLowController;
use App\Http\Controllers\PropertyTypeHighController;
use App\Http\Controllers\PropertyTypeIntController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\EnduserController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TechController;
use App\Http\Controllers\ReturnSlipController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which 
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('login');
});

//Login
Route::get('/login', [LoginController::class, 'getLogin'])->name('getLogin');
Route::post('/login', [LoginController::class, 'postLogin'])->name('postLogin');

//Middleware
Route::group(['middleware' => ['login_auth']], function () {
    Route::get('/dashboard', [MasterController::class, 'dashboard'])->name('dashboard');

    Route::prefix('/return-slips')->group(function () {
        Route::get('/', [ReturnSlipController::class, 'index'])->name('returnSlips.index');
        Route::get('/create', [ReturnSlipController::class, 'create'])->name('returnSlips.create');
        Route::post('/store', [ReturnSlipController::class, 'store'])->name('returnSlips.store');
        Route::get('/logs', [ReturnSlipController::class, 'logs'])->name('returnSlips.logs');
        Route::get('/iirup-report', [ReturnSlipController::class, 'iirupReportForm'])->name('returnSlips.iirupReport');
        Route::get('/iirup-report/generate', [ReturnSlipController::class, 'iirupReportGenerate'])->name('returnSlips.iirupReport.generate');
        Route::get('/properties', [ReturnSlipController::class, 'propertyOptions'])->name('returnSlips.properties');
        Route::get('/slip-report', [ReturnSlipController::class, 'slipReportForm'])->name('returnSlips.slipReport.form');
        Route::get('/slip-report/summary', [ReturnSlipController::class, 'slipReportSummary'])->name('returnSlips.slipReport.summary');
        Route::get('/transfer-report', [ReturnSlipController::class, 'transferReportForm'])->name('returnSlips.transferReport');
        Route::get('/transfer-report/generate', [ReturnSlipController::class, 'transferReportGenerate'])->name('returnSlips.transferReport.generate');
        Route::get('/transfer-report/summary', [ReturnSlipController::class, 'transferReportSummary'])->name('returnSlips.transferReport.summary');
        Route::post('/items/{itemId}/transfer', [ReturnSlipController::class, 'transferItem'])->name('returnSlips.items.transfer');
        Route::post('/items/{itemId}/unserviceable', [ReturnSlipController::class, 'unserviceableItem'])->name('returnSlips.items.unserviceable');
        Route::post('/items/{itemId}/obsolete', [ReturnSlipController::class, 'obsoleteItem'])->name('returnSlips.items.obsolete');
        Route::post('/items/{itemId}/cancel', [ReturnSlipController::class, 'cancelItem'])->name('returnSlips.items.cancel');
        Route::post('/items/{itemId}/delete', [ReturnSlipController::class, 'deleteItem'])->name('returnSlips.items.delete');
        Route::get('/{id}', [ReturnSlipController::class, 'show'])->whereNumber('id')->name('returnSlips.show');
        Route::get('/{id}/slip-report', [ReturnSlipController::class, 'returnSlipReport'])->whereNumber('id')->name('returnSlips.slipReport');
    });

    //View
    Route::prefix('/view')->group(function () {
        // Route::get('/', [ViewController::class, 'index'])->name('manage-index');
        Route::prefix('/property')->group(function () {
            Route::post('list/create', [PropertyTypeController::class, 'ppeCreate'])->name('ppeCreate');
            Route::get('/listPPE', [PropertyTypeController::class, 'ppeRead'])->name('ppeRead');
            Route::get('list/edit/{id}', [PropertyTypeController::class, 'ppeEdit'])->name('ppeEdit');
            Route::post('list/update', [PropertyTypeController::class, 'ppeUpdate'])->name('ppeUpdate');
            Route::get('list/delete/{id}', [PropertyTypeController::class, 'ppeDelete'])->name('ppeDelete');

            Route::post('listLV/create', [PropertyTypeLowController::class, 'lvCreate'])->name('lvCreate');
            Route::get('/listLV', [PropertyTypeLowController::class, 'lvRead'])->name('lvRead');
            Route::get('list/{id}/LVedit', [PropertyTypeLowController::class, 'lvEdit'])->name('lvEdit');
            Route::post('listLV/update', [PropertyTypeLowController::class, 'lvUpdate'])->name('lvUpdate');
            Route::get('listLV/delete/{id}', [PropertyTypeLowController::class, 'lvDelete'])->name('lvDelete');

            Route::post('listHV/create', [PropertyTypeHighController::class, 'hvCreate'])->name('hvCreate');
            Route::get('/listHV', [PropertyTypeHighController::class, 'hvRead'])->name('hvRead');
            Route::get('list/{id}/HVedit', [PropertyTypeHighController::class, 'hvEdit'])->name('hvEdit');
            Route::post('listHV/update', [PropertyTypeHighController::class, 'hvUpdate'])->name('hvUpdate');
            Route::get('listHV/delete/{id}', [PropertyTypeHighController::class, 'hvDelete'])->name('hvDelete');

            Route::post('listINT/create', [PropertyTypeIntController::class, 'intCreate'])->name('intCreate');
            Route::get('/listINT', [PropertyTypeIntController::class, 'intRead'])->name('intRead');
            Route::get('list/{id}/INTedit', [PropertyTypeIntController::class, 'intEdit'])->name('intEdit');
            Route::post('listINT/update', [PropertyTypeIntController::class, 'intUpdate'])->name('intUpdate');
            Route::get('listINT/delete/{id}', [PropertyTypeIntController::class, 'intDelete'])->name('intDelete');
        });


        Route::prefix('/unit')->group(function () {
            Route::get('/list', [UnitController::class, 'unitRead'])->name('unitRead');
            Route::post('/list', [UnitController::class, 'unitCreate'])->name('unitCreate');
            Route::get('list/edit/{id}', [UnitController::class, 'unitEdit'])->name('unitEdit');
            Route::post('list/update', [UnitController::class, 'unitUpdate'])->name('unitUpdate');
            Route::get('list/delete/{id}', [UnitController::class, 'unitDelete'])->name('unitDelete');
        });

        Route::prefix('/item')->group(function () {
            Route::get('/list', [ItemController::class, 'itemRead'])->name('itemRead');
            Route::post('/list', [ItemController::class, 'itemCreate'])->name('itemCreate');
            Route::get('list/edit/{id}', [ItemController::class, 'itemEdit'])->name('itemEdit');
            Route::post('list/update', [ItemController::class, 'itemUpdate'])->name('itemUpdate');
            Route::get('list/delete/{id}', [ItemController::class, 'itemDelete'])->name('itemDelete');
        });

        Route::prefix('/office')->group(function () {
            Route::get('/list/{code}', [OfficeController::class, 'officeRead'])->name('officeRead');
            Route::post('/list', [OfficeController::class, 'officeCreate'])->name('officeCreate');
            Route::get('list/edit/{id}/{code}', [OfficeController::class, 'officeEdit'])->name('officeEdit');
            Route::post('list/update', [OfficeController::class, 'officeUpdate'])->name('officeUpdate');
            Route::get('list/delete/{id}', [OfficeController::class, 'officeDelete'])->name('officeDelete');
        });

        Route::prefix('/accntperson')->group(function () {
            Route::get('/list', [EnduserController::class, 'accountableRead'])->name('accountableRead');
            Route::post('/list', [EnduserController::class, 'accountableCreate'])->name('accountableCreate');
            Route::get('list/edit/{id}', [EnduserController::class, 'accountableEdit'])->name('accountableEdit');
            Route::post('list/update', [EnduserController::class, 'accountableUpdate'])->name('accountableUpdate');
            Route::get('list/delete/{id}', [EnduserController::class, 'accountableDelete'])->name('accountableDelete');
        });
    });

    //purchases
    Route::prefix('/purchases')->group(function () {
        Route::get('/list/all', [PurchaseController::class, 'purchaseREAD'])->name('purchaseREAD');
        Route::post('/list/add', [PurchaseController::class, 'purchaseCreate'])->name('purchaseCreate');
        Route::get('/list/delete/{id}', [PurchaseController::class, 'purchaseRelDel'])->name('purchaseRelDel');

        Route::get('/list/purchase-get/{id}', [PurchaseController::class, 'purchaseReleaseGet'])->name('purchaseReleaseGet');
        Route::post('/list/purchase-post', [PurchaseController::class, 'purchaseReleasePost'])->name('purchaseReleasePost');
        Route::get('/list/ajax', [PurchaseController::class, 'getPurchase'])->name('getPurchase');

        Route::get('/check-next-number/{propertyno}/{officeCode}', [PurchaseController::class, 'checkNextNumber'])->name('checkNextNumber');
    });

    //properties
    Route::prefix('/properties')->group(function () {
        Route::get('/list/{category}', [PropertiesController::class, 'propertiesRead'])->whereNumber('category')->name('propertiesRead');
        Route::get('/list/ajax/{category}', [PropertiesController::class, 'getProperties'])->name('getProperties');
        Route::get('/list/ppe', [PropertiesController::class, 'propertiesppeRead'])->name('propertiesppeRead');
        Route::get('/list/high', [PropertiesController::class, 'propertieshighRead'])->name('propertieshighRead');
        Route::get('/list/low', [PropertiesController::class, 'propertieslowRead'])->name('propertieslowRead');
        Route::get('/list/intangible', [PropertiesController::class, 'propertiesintangibleRead'])->name('propertiesintangibleRead');

        Route::get('/return-slip/{id}', [PropertiesController::class, 'returnSlip'])->name('returnSlip');

        Route::post('/list/add', [PropertiesController::class, 'propertiesCreate'])->name('propertiesCreate');
        Route::get('/list/edit/{id}', [PropertiesController::class, 'propertiesEdit'])->name('propertiesEdit');
        Route::post('/list/update', [PropertiesController::class, 'propertiesUpdate'])->name('propertiesUpdate');
        Route::get('/list/cat/{id}/{mode}', [PropertiesController::class, 'propertiesCat'])->name('propertiesCat');
        Route::get('/list/prnt/{id}', [PropertiesController::class, 'propertiesPrntSticker'])->name('propertiesPrntSticker');
        Route::get('/list/delete/{id}', [PropertiesController::class, 'propertiesDelete'])->name('propertiesDelete');

        Route::post('/end-user/update', [PropertiesController::class, 'enduserUpdate'])->name('enduserUpdate');

        Route::get('/sticker', [PropertiesController::class, 'stickerRead'])->name('stickerRead');
        Route::post('/sticker', [PropertiesController::class, 'stickerReadPost'])->name('stickerReadPost');
        Route::get('/sticker-pdf/{office}', [PropertiesController::class, 'stickerReadPdf'])->name('stickerReadPdf');
        Route::get('/sticker-json/{range}/{office}', [PropertiesController::class, 'stickerReadJson'])->name('stickerReadJson');

        Route::get('/blank-sticker', [PropertiesController::class, 'propertiesStickerTemplate'])->name('propertiesStickerTemplate');
        Route::get('/list/sticker/pdf', [PropertiesController::class, 'propertiesStickerTemplatePDF'])->name('propertiesStickerTemplatePDF');

        Route::get('/sticker/generate/', [PropertiesController::class, 'generateQRCodesAndDownloadPdf'])->name('stickers.pdf');
    });

    //inventory
    Route::prefix('/inventory')->group(function () {
        Route::get('/list', [InventoryController::class, 'inventoryRead'])->name('inventoryRead');
        Route::get('/view/{id}/', [InventoryController::class, 'inventoryView'])->name('inventoryView');
        Route::get('/list/ajax', [InventoryController::class, 'getInventory'])->name('getInventory');
        Route::post('/start', [InventoryController::class, 'startInventory'])->name('startInventory');
        Route::post('/save', [InventoryController::class, 'invSave'])->name('invSave');
    });

    //Reports
    Route::prefix('/reports')->group(function () {
        Route::get('/{id}', [ReportsController::class, 'reportOption'])->whereNumber('id')->name('reportOption');
        Route::post('/report-option', [ReportsController::class, 'reportOptionView'])->name('reportOptionView');
        Route::get('/rpcppe/option', [ReportsController::class, 'rpcppeOption'])->name('rpcppeOption');
        Route::get('/rpcppe/reports/rpcppe', [ReportsController::class, 'rpcppeOptionReportGen'])->name('rpcppeOptionReportGen');

        Route::get('/rpcsep/option', [ReportsController::class, 'rpcsepOption'])->name('rpcsepOption');
        Route::post('/rpcsep/reports/rpcsep', [ReportsController::class, 'rpcsepOptionReportGen'])->name('rpcsepOptionReportGen');

        Route::get('/ics/option', [ReportsController::class, 'icsOption'])->name('icsOption');
        Route::post('/ics/reports/ics', [ReportsController::class, 'icsOptionReportGen'])->name('icsOptionReportGen');
        Route::post('/ics/icsitemList', [ReportsController::class, 'icsgenOption'])->name('icsgenOption');

        Route::get('/unserviceable-form', [ReportsController::class, 'unserviceForm'])->name('unserviceForm');
        Route::post('/unserviceable-report', [ReportsController::class, 'unserviceReport'])->name('unserviceReport');

        Route::get('/par/option', [ReportsController::class, 'parOption'])->name('parOption');
        Route::post('/par/reports/par', [ReportsController::class, 'parOptionReportGen'])->name('parOptionReportGen');
        Route::post('/par/icsitemList', [ReportsController::class, 'pargenOption'])->name('pargenOption');

        Route::post('/par/itemList/unserv', [ReportsController::class, 'genOptionUnserv'])->name('genOptionUnserv');

        Route::get('/list/cat/{id}/{mode}', [PropertiesController::class, 'invCatIcsPar'])->name('invCatIcsPar');

        Route::post('/allgenoption', [ReportsController::class, 'allgenOption'])->name('allgenOption');

        Route::get('/display-item/{enduserId}', [ReportsController::class, 'displayItem'])->name('displayItem');
        
        
    });

    Route::prefix('/report')->group(function () {
        Route::get('', [ReportsController::class, 'reportForm'])->name('reportForm');
        Route::post('/generate', [ReportsController::class, 'generateReport'])->name('generateReport');
        Route::get('/generate-items', [ReportsController::class, 'generateItems'])->name('generateItems');

        Route::get('/gen-prop-type/{id}', [ReportsController::class, 'genPropType'])->name('genPropType'); 
        Route::get('/generate-mix/{id}', [ReportsController::class, 'generateMix'])->name('generateMix');
    });

    //Users
    Route::prefix('/users')->group(function () {
        Route::get('/list', [UserController::class, 'userRead'])->name('userRead');
        Route::post('/list', [UserController::class, 'userCreate'])->name('userCreate');
        Route::get('list/edit/{id}', [UserController::class, 'userEdit'])->name('userEdit');
        Route::post('list/update', [UserController::class, 'userUpdate'])->name('userUpdate');
        Route::get('list/delete/{id}', [UserController::class, 'userDelete'])->name('userDelete');
    });

    //Settings
    Route::prefix('/settings')->group(function () {
        Route::get('/account-settings', [SettingsController::class, 'user_settings'])->name('user_settings');
        Route::post('/account-settings/update', [SettingsController::class, 'profileUpdate'])->name('profileUpdate');
        Route::post('/acccount-settings/updatePass', [SettingsController::class, 'profilePassUpdate'])->name('profilePassUpdate');
        Route::get('/system-name', [SettingsController::class, 'setting_list'])->name('setting_list');
        Route::post('/system-name', [SettingsController::class, 'upload'])->name('upload');
    });

    Route::prefix('/technician')->group(function () {
        Route::get('/repair', [TechController::class, 'repairRead'])->name('repairRead');
        Route::post('/repair-create', [TechController::class, 'repairCreate'])->name('repairCreate');
        Route::post('/repair-update', [TechController::class, 'repairUpdate'])->name('repairUpdate');

        Route::get('/qr-scan', [TechController::class, 'qrScan'])->name('qr-scan');
        Route::get('/qr-scan-check/{propno}', [TechController::class, 'qrScanCheck'])->name('qr-scan-check');

        Route::get('/form/{propno}', [TechController::class, 'repairForm'])->name('repairForm');
        Route::get('/form/diagnose/{propno}', [TechController::class, 'repairDiagnose'])->name('repairDiagnose');
        Route::post('/form/release/{propno}', [TechController::class, 'repairRelease'])->name('repairRelease');

        Route::get('/repair-report/{id}', [TechController::class, 'repairPDF'])->name('repairPDF');
    });

    //Logout
    Route::post('/logout', [MasterController::class, 'logout'])->name('logout');
});

