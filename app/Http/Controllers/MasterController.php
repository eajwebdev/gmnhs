<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Setting;
use App\Models\Office;
use App\Models\School;
use App\Models\User;
use App\Models\property;
use App\Models\Inventory;
use App\Models\Purchases;
use App\Models\EnduserProperty;
use App\Models\Repair;

class MasterController extends Controller
{
    //
    public function dashboard(){
        $setting = Setting::firstOrNew(['id' => 1]);

        $schools = School::all();
        $userCount = User::count();
        $offCount = Office::count();
        $schoolCount = School::count();
        $propertyCount = property::count();
        $user = auth()->user();
        $role = $user->role;
        $isAdministrator = $role === 'Administrator';
        $isSupplyOfficer = $role === 'Supply Officer';
        $isSchoolAdmin = $role === 'School Admin';
        $isTechnician = $role === 'Technician';
        $dashboardMode = $isTechnician ? 'technician' : ($isSchoolAdmin ? 'school' : ($isSupplyOfficer ? 'supply' : 'administrator'));
        $scopeToSchool = !$isAdministrator && !$isSupplyOfficer;
        $schoolOfficeIds = collect();

        if ($scopeToSchool) {
            $schoolOfficeIds = Office::query()
                ->where(function ($query) use ($user) {
                    $query->where('school_id', $user->school_id);
                })
                ->pluck('id');
        }

        $applySchoolScope = function ($query) use ($scopeToSchool, $schoolOfficeIds) {
            if (!$scopeToSchool) {
                return $query;
            }

            return $schoolOfficeIds->isNotEmpty()
                ? $query->whereIn('office_id', $schoolOfficeIds)
                : $query->whereRaw('1 = 0');
        };

        $reportBase = $applySchoolScope(EnduserProperty::query()->where('deleted', 0));
        $reportableItemsCount = (clone $reportBase)->count();
        $reportableAssetsValue = (clone $reportBase)->sum('item_cost');
        $reportableQuantity = (clone $reportBase)->sum('qty');
        $latestReportableItem = (clone $reportBase)->max('date_acquired');

        $serviceableReportBase = function () use ($applySchoolScope) {
            return $applySchoolScope(EnduserProperty::query()
                ->where('deleted', 0)
                ->where(function ($query) {
                    $query->whereNull('remarks')
                        ->orWhere('remarks', '!=', 'Unserviceable');
                }));
        };

        $unserviceableReportBase = function () use ($applySchoolScope) {
            return $applySchoolScope(EnduserProperty::query()
                ->where('deleted', 0)
                ->where('remarks', 'Unserviceable'));
        };

        // These buckets match the active ReportsController report_type filters above //OLD.
        $rpcppeCount = $serviceableReportBase()->where('item_cost', '>=', 50000)->count();
        $rpcppeValue = $serviceableReportBase()->where('item_cost', '>=', 50000)->sum('item_cost');
        $rpcsepCount = $serviceableReportBase()->where('item_cost', '<', 50000)->count();
        $rpcsepValue = $serviceableReportBase()->where('item_cost', '<', 50000)->sum('item_cost');
        $unserviceableCount = $unserviceableReportBase()->count();
        $unserviceableValue = $unserviceableReportBase()->sum('item_cost');

        $inventoryPPECount = $rpcppeCount;
        $inventoryHighCount = $rpcsepCount;
        $inventoryLowCount = $unserviceableCount;

        $officeReportRows = Office::query()
            ->where('id', '!=', 1)
            ->where('office_code', '!=', '0000')
            ->when($scopeToSchool, function ($query) use ($schoolOfficeIds) {
                return $schoolOfficeIds->isNotEmpty()
                    ? $query->whereIn('id', $schoolOfficeIds)
                    : $query->whereRaw('1 = 0');
            })
            ->orderBy('office_abbr')
            ->get()
            ->map(function ($office) use ($serviceableReportBase, $unserviceableReportBase) {
                $officeServiceable = function () use ($serviceableReportBase, $office) {
                    return $serviceableReportBase()->where('office_id', $office->id);
                };

                $officeUnserviceable = function () use ($unserviceableReportBase, $office) {
                    return $unserviceableReportBase()->where('office_id', $office->id);
                };

                $rpcppe = $officeServiceable()->where('item_cost', '>=', 50000)->count();
                $rpcsep = $officeServiceable()->where('item_cost', '<', 50000)->count();
                $unserviceable = $officeUnserviceable()->count();

                return [
                    'id' => $office->id,
                    'label' => $office->office_abbr ?: $office->office_name,
                    'is_extension' => false,
                    'rpcppe' => $rpcppe,
                    'rpcsep' => $rpcsep,
                    'unserviceable' => $unserviceable,
                    'total' => $rpcppe + $rpcsep + $unserviceable,
                ];
            });

        $mainReportRows = $officeReportRows->values();
        $extensionReportRows = $officeReportRows->values();
        $visibleExtensionRows = $extensionReportRows->where('total', '>', 0)->take(12)->values();

        if ($visibleExtensionRows->isEmpty()) {
            $visibleExtensionRows = $extensionReportRows->take(12)->values();
        }

        $mainSchoolTotal = $mainReportRows->sum('total');
        $extensionSchoolTotal = $extensionReportRows->sum('total');

        $MainPpeCount = $mainReportRows->sum('rpcppe');
        $MainHighCount = $mainReportRows->sum('rpcsep');
        $MainLowCount = $mainReportRows->sum('unserviceable');

        $schoolReportLabels = $visibleExtensionRows->pluck('label')->values();
        $schoolReportRpcppe = $visibleExtensionRows->pluck('rpcppe')->values();
        $schoolReportRpcsep = $visibleExtensionRows->pluck('rpcsep')->values();
        $schoolReportUnserviceable = $visibleExtensionRows->pluck('unserviceable')->values();
        $mainReportLabels = ['GMNHS'];
        $mainReportRpcppe = [$MainPpeCount];
        $mainReportRpcsep = [$MainHighCount];
        $mainReportUnserviceable = [$MainLowCount];

        $repairScope = Repair::query();
        if ($scopeToSchool && $schoolOfficeIds->isNotEmpty()) {
            $repairScope->whereIn('prop_id', EnduserProperty::query()
                ->whereIn('office_id', $schoolOfficeIds)
                ->select('id'));
        } elseif ($scopeToSchool) {
            $repairScope->whereRaw('1 = 0');
        }

        $repairTotalCount = (clone $repairScope)->count();
        $repairPendingCount = (clone $repairScope)
            ->where(function ($query) {
                $query->whereNull('diagnosis')
                    ->orWhere('diagnosis', '');
            })
            ->count();
        $repairForReleaseCount = (clone $repairScope)
            ->whereNotNull('diagnosis')
            ->whereNull('release_date')
            ->count();
        $repairReleasedCount = (clone $repairScope)->whereNotNull('release_date')->count();


        return view("home.dashboard", compact('schools', 
                                            'dashboardMode',
                                            'role',
                                            'isAdministrator',
                                            'isSupplyOfficer',
                                            'isSchoolAdmin',
                                            'isTechnician',
                                            'userCount', 
                                            'offCount', 
                                            'schoolCount', 
                                            'propertyCount', 
                                            'reportableItemsCount',
                                            'reportableAssetsValue',
                                            'reportableQuantity',
                                            'latestReportableItem',
                                            'rpcppeCount',
                                            'rpcppeValue',
                                            'rpcsepCount',
                                            'rpcsepValue',
                                            'unserviceableCount',
                                            'unserviceableValue',
                                            'inventoryPPECount', 
                                            'inventoryHighCount', 
                                            'inventoryLowCount',
                                            'mainSchoolTotal',
                                            'extensionSchoolTotal',
                                            'schoolReportLabels',
                                            'schoolReportRpcppe',
                                            'schoolReportRpcsep',
                                            'schoolReportUnserviceable',
                                            'mainReportLabels',
                                            'mainReportRpcppe',
                                            'mainReportRpcsep',
                                            'mainReportUnserviceable',
                                            'MainPpeCount',
                                            'MainHighCount',
                                            'MainLowCount',
                                            'repairTotalCount',
                                            'repairPendingCount',
                                            'repairForReleaseCount',
                                            'repairReleasedCount',
                                            'setting'));
    }

    public function logout(){
        auth()->logout();
        return redirect()->route('getLogin')->with('success','You have been Successfully Logged Out');
    }
}


