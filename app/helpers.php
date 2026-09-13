<?php

use Jenssegers\Agent\Agent;

/**
 * Detect if the current request is from a mobile device (phone or tablet).
 *
 * @return bool
 */
if (!function_exists('isMobileDevice')) {
    function isMobileDevice(): bool
    {
        return (new Agent())->isMobile();
    }
}

if (!function_exists('display_role')) {
    function display_role(?string $role): string
    {
        return (string) $role;
    }
}

if (!function_exists('system_access_options')) {
    function system_access_options(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'view' => 'View Records',
            'purchases' => 'Purchases',
            'properties' => 'Properties',
            'inventory' => 'Inventory',
            'reports' => 'Reports',
            'repair' => 'Technician',
            'return_slips' => 'Return Slip',
            'users' => 'Users',
            'settings' => 'Settings',
        ];
    }
}

if (!function_exists('role_default_access')) {
    function role_default_access(?string $role): array
    {
        return match ($role) {
            'Administrator' => array_keys(system_access_options()),
            'Supply Officer' => ['dashboard', 'view', 'purchases', 'properties', 'inventory', 'reports', 'return_slips', 'users'],
            'School Admin' => ['dashboard', 'view', 'properties', 'inventory', 'reports', 'return_slips', 'settings'],
            'Supply Staff' => ['dashboard', 'view', 'properties', 'inventory', 'reports', 'return_slips'],
            'Technician' => ['dashboard', 'repair', 'return_slips'],
            default => ['dashboard'],
        };
    }
}

if (!function_exists('user_access_list')) {
    function user_access_list($user = null): array
    {
        $user = $user ?: auth()->user();

        if (!$user) {
            return [];
        }

        $access = $user->access ?? null;
        if (is_string($access)) {
            $decoded = json_decode($access, true);
            $access = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($access) || $access === []) {
            $access = role_default_access($user->role ?? null);
        }

        return array_values(array_intersect($access, array_keys(system_access_options())));
    }
}

if (!function_exists('user_has_access')) {
    function user_has_access(string $module, $user = null): bool
    {
        return in_array($module, user_access_list($user), true);
    }
}


if (!function_exists('page_meta')) {
    /**
     * Section, title and one-line description for the current screen. Feeds the
     * topbar breadcrumb and the shared page header in the master layout.
     */
    function page_meta(): array
    {
        $route = request()->route();
        $name = $route ? $route->getName() : null;
        $role = auth()->user()->role ?? null;

        $propertyCategories = [
            4 => ['All Properties', 'Every property record across PPE, high value and low value classifications.'],
            3 => ['PPE Properties', 'Property, plant and equipment records valued at 50,000 and above.'],
            1 => ['High Value Properties', 'Semi-expendable property recorded under the high value classification.'],
            2 => ['Low Value Properties', 'Semi-expendable property recorded under the low value classification.'],
        ];

        $accountableLabel = $role === 'School Admin' ? 'End Users' : 'Accountable Persons';
        $isLocation = (int) optional($route)->parameter('code') === 2;
        $category = (int) optional($route)->parameter('category');

        $map = [
            'dashboard' => ['Overview', 'Dashboard', ''],
            'ppeRead' => ['Master Data', 'PPE Account Titles', 'Account numbers used to classify property, plant and equipment.'],
            'ppeEdit' => ['Master Data', 'PPE Account Titles', 'Account numbers used to classify property, plant and equipment.'],
            'lvRead' => ['Master Data', 'Low Value Account Titles', 'Account titles for semi-expendable, low value property.'],
            'lvEdit' => ['Master Data', 'Low Value Account Titles', 'Account titles for semi-expendable, low value property.'],
            'hvRead' => ['Master Data', 'High Value Account Titles', 'Account titles for semi-expendable, high value property.'],
            'hvEdit' => ['Master Data', 'High Value Account Titles', 'Account titles for semi-expendable, high value property.'],
            'intRead' => ['Master Data', 'Intangible Account Titles', 'Account titles for intangible assets.'],
            'intEdit' => ['Master Data', 'Intangible Account Titles', 'Account titles for intangible assets.'],
            'unitRead' => ['Master Data', 'Units of Measure', 'Units used when recording quantities on purchases and properties.'],
            'unitEdit' => ['Master Data', 'Units of Measure', 'Units used when recording quantities on purchases and properties.'],
            'itemRead' => ['Master Data', 'Items', 'Item names and how many active property records use each one.'],
            'itemEdit' => ['Master Data', 'Items', 'Item names and how many active property records use each one.'],
            'officeRead' => $isLocation
                ? ['Master Data', 'Locations', 'Rooms and physical locations where property is kept.']
                : ['Master Data', 'Offices', 'School offices, their codes and the officer heading each one.'],
            'officeEdit' => $isLocation
                ? ['Master Data', 'Locations', 'Rooms and physical locations where property is kept.']
                : ['Master Data', 'Offices', 'School offices, their codes and the officer heading each one.'],
            'accountableRead' => ['Master Data', $accountableLabel, 'People who carry accountability for issued property.'],
            'accountableEdit' => ['Master Data', $accountableLabel, 'People who carry accountability for issued property.'],
            'purchaseREAD' => ['Registry', 'Purchases', 'Purchased items waiting to be released as property.'],
            'purchaseReleaseGet' => ['Registry', 'Release Purchase', 'Assign a purchased item to an office and accountable person.'],
            'propertiesRead' => ['Registry', $propertyCategories[$category][0] ?? 'Properties', $propertyCategories[$category][1] ?? ''],
            'propertiesEdit' => ['Registry', 'Edit Property', 'Update the details recorded for this property.'],
            'stickerRead' => ['Registry', 'Property Stickers', 'Print QR property stickers by office and range.'],
            'stickerReadPost' => ['Registry', 'Property Stickers', 'Print QR property stickers by office and range.'],
            'propertiesStickerTemplate' => ['Registry', 'Blank Stickers', 'Print blank sticker sheets for manual tagging.'],
            'inventoryRead' => ['Registry', 'Inventory', 'Yearly physical count of property.'],
            'inventoryView' => ['Registry', 'Inventory Count', 'Progress of the ongoing physical count per office.'],
            'reportForm' => ['Operations', 'Reports', 'Generate RPCPPE, RPCSEP, ICS, PAR and IIRUP reports.'],
            'repairRead' => ['Operations', 'Repairs', 'Items received for diagnosis, repair and release.'],
            'returnSlips.index' => ['Operations', 'Return Slips', ''],
            'returnSlips.show' => ['Operations', 'Return Slip', ''],
            'returnSlips.logs' => ['Operations', 'Item Logs', ''],
            'returnSlips.iirupReport' => ['Operations', 'IIRUP Report', ''],
            'returnSlips.slipReport.form' => ['Operations', 'Return Slip Report', ''],
            'returnSlips.transferReport' => ['Operations', 'Transfer Report', ''],
            'user_settings' => ['Administration', 'Account Settings', 'Your profile details and sign-in password.'],
            'setting_list' => ['Administration', 'System Settings', 'System name and logo shown across the application.'],
            'userRead' => ['Administration', 'Users', 'Accounts that can sign in, their roles and module access.'],
            'userEdit' => ['Administration', 'Users', 'Accounts that can sign in, their roles and module access.'],
        ];

        [$section, $title, $description] = $map[$name] ?? ['GMNHS', 'Property Registry', ''];

        return [
            'route' => $name,
            'section' => $section,
            'title' => $title,
            'description' => $description,
            // Screens that render their own header block
            'own_header' => $name === 'dashboard' || str_starts_with((string) $name, 'returnSlips.'),
        ];
    }
}
