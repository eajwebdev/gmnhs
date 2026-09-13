@php
    $route = request()->route();
    $current = $route ? $route->getName() : null;
    $user = auth()->user();
    $role = $user->role;
    $code = (int) optional($route)->parameter('code');
    $category = (int) optional($route)->parameter('category');
    $on = fn (...$names) => in_array($current, $names, true);

    $isTechnician = $role === 'Technician';
    $isSchoolAdmin = $role === 'School Admin';
    $isSupply = in_array($role, ['Administrator', 'Supply Officer'], true);
    $hasIntangible = \App\Models\property::whereKey(4)->exists();

    // Each group: header label + entries. An entry either links directly or
    // opens a tree of child links. Entries the role cannot use are left out.
    $groups = [
        'Overview' => [
            user_has_access('dashboard', $user) ? [
                'label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'href' => route('dashboard'), 'active' => $on('dashboard'),
            ] : null,
        ],
        'Registry' => [
            user_has_access('view', $user) && !$isTechnician ? [
                'label' => 'Master Data', 'icon' => 'fa-book-bookmark',
                'active' => request()->is('view*'),
                'children' => array_filter([
                    !$isSchoolAdmin ? ['heading' => 'Account titles'] : null,
                    !$isSchoolAdmin ? ['label' => 'PPE', 'href' => route('ppeRead'), 'active' => $on('ppeRead', 'ppeEdit')] : null,
                    !$isSchoolAdmin ? ['label' => 'High Value', 'href' => route('hvRead'), 'active' => $on('hvRead', 'hvEdit')] : null,
                    !$isSchoolAdmin ? ['label' => 'Low Value', 'href' => route('lvRead'), 'active' => $on('lvRead', 'lvEdit')] : null,
                    !$isSchoolAdmin && $hasIntangible ? ['label' => 'Intangible', 'href' => route('intRead'), 'active' => $on('intRead', 'intEdit')] : null,
                    ['heading' => 'Directory'],
                    !$isSchoolAdmin ? ['label' => 'Offices', 'href' => route('officeRead', 1), 'active' => $on('officeRead', 'officeEdit') && $code === 1] : null,
                    ['label' => 'Locations', 'href' => route('officeRead', 2), 'active' => $on('officeRead', 'officeEdit') && $code === 2],
                    ['label' => $isSchoolAdmin ? 'End Users' : 'Accountable Persons', 'href' => route('accountableRead'), 'active' => $on('accountableRead', 'accountableEdit')],
                    !$isSchoolAdmin ? ['heading' => 'Catalogue'] : null,
                    !$isSchoolAdmin ? ['label' => 'Items', 'href' => route('itemRead'), 'active' => $on('itemRead', 'itemEdit')] : null,
                    !$isSchoolAdmin ? ['label' => 'Units', 'href' => route('unitRead'), 'active' => $on('unitRead', 'unitEdit')] : null,
                ]),
            ] : null,
            user_has_access('properties', $user) && !$isTechnician ? [
                'label' => 'Properties', 'icon' => 'fa-boxes-stacked',
                'active' => request()->is('properties*'),
                'children' => array_filter([
                    ['label' => 'All Properties', 'href' => route('propertiesRead', 4), 'active' => $on('propertiesRead') && $category === 4],
                    ['label' => 'PPE', 'href' => route('propertiesRead', 3), 'active' => $on('propertiesRead') && $category === 3],
                    ['label' => 'High Value', 'href' => route('propertiesRead', 1), 'active' => $on('propertiesRead') && $category === 1],
                    ['label' => 'Low Value', 'href' => route('propertiesRead', 2), 'active' => $on('propertiesRead') && $category === 2],
                    !$isSchoolAdmin ? ['heading' => 'Tagging'] : null,
                    !$isSchoolAdmin ? ['label' => 'Property Stickers', 'href' => route('stickerRead'), 'active' => $on('stickerRead', 'stickerReadPost')] : null,
                    !$isSchoolAdmin ? ['label' => 'Blank Stickers', 'href' => route('propertiesStickerTemplate'), 'active' => $on('propertiesStickerTemplate')] : null,
                ]),
            ] : null,
            user_has_access('purchases', $user) && !$isTechnician && !$isSchoolAdmin ? [
                'label' => 'Purchases', 'icon' => 'fa-cart-flatbed', 'href' => route('purchaseREAD'), 'active' => request()->is('purchases*'),
            ] : null,
            user_has_access('inventory', $user) && !$isTechnician ? [
                'label' => 'Inventory', 'icon' => 'fa-clipboard-check', 'href' => route('inventoryRead'), 'active' => request()->is('inventory*'),
            ] : null,
        ],
        'Operations' => [
            user_has_access('reports', $user) && !$isTechnician ? [
                'label' => 'Reports', 'icon' => 'fa-file-lines', 'href' => route('reportForm'), 'active' => request()->is('report', 'report/*', 'reports*'),
            ] : null,
            user_has_access('return_slips', $user) ? [
                'label' => 'Return Slips', 'icon' => 'fa-arrow-rotate-left',
                'active' => request()->is('return-slips*'),
                'children' => array_filter([
                    ['label' => 'Returned Items', 'href' => route('returnSlips.index'), 'active' => $on('returnSlips.index', 'returnSlips.show')],
                    ['label' => 'Return Slip Report', 'href' => route('returnSlips.slipReport.form'), 'active' => $on('returnSlips.slipReport.form')],
                    $isSupply ? ['label' => 'Transfer Report', 'href' => route('returnSlips.transferReport'), 'active' => $on('returnSlips.transferReport')] : null,
                    $isSupply ? ['label' => 'IIRUP Report', 'href' => route('returnSlips.iirupReport'), 'active' => $on('returnSlips.iirupReport')] : null,
                    $isSupply ? ['label' => 'Item Logs', 'href' => route('returnSlips.logs'), 'active' => $on('returnSlips.logs')] : null,
                ]),
            ] : null,
            user_has_access('repair', $user) && in_array($role, ['Technician', 'Administrator'], true) ? [
                'label' => 'Repairs', 'icon' => 'fa-screwdriver-wrench', 'href' => route('repairRead'), 'active' => request()->is('technician*'),
            ] : null,
        ],
        'Administration' => [
            user_has_access('users', $user) ? [
                'label' => 'Users', 'icon' => 'fa-user-shield', 'href' => route('userRead'), 'active' => $on('userRead', 'userEdit'),
            ] : null,
            user_has_access('settings', $user) ? [
                'label' => 'Settings', 'icon' => 'fa-sliders',
                'active' => request()->is('settings*'),
                'children' => array_filter([
                    ['label' => 'My Account', 'href' => route('user_settings'), 'active' => $on('user_settings')],
                    $role === 'Administrator' ? ['label' => 'System', 'href' => route('setting_list'), 'active' => $on('setting_list')] : null,
                ]),
            ] : null,
        ],
    ];
@endphp

<nav class="app-nav" aria-label="Primary">
    <ul class="nav nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        @foreach($groups as $heading => $entries)
            @php $entries = array_filter($entries); @endphp
            @continue(empty($entries))

            <li class="nav-header">{{ $heading }}</li>

            @foreach($entries as $entry)
                @if(!empty($entry['children']))
                    <li class="nav-item {{ $entry['active'] ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $entry['active'] ? 'active' : '' }}">
                            <i class="nav-icon fas {{ $entry['icon'] }}"></i>
                            <p>{{ $entry['label'] }} <i class="right fas fa-angle-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @foreach($entry['children'] as $child)
                                @if(isset($child['heading']))
                                    <li class="nav-item"><span class="nav-sub-label">{{ $child['heading'] }}</span></li>
                                @else
                                    <li class="nav-item">
                                        <a href="{{ $child['href'] }}" class="nav-link {{ $child['active'] ? 'active' : '' }}">
                                            <p>{{ $child['label'] }}</p>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a href="{{ $entry['href'] }}" class="nav-link {{ $entry['active'] ? 'active' : '' }}">
                            <i class="nav-icon fas {{ $entry['icon'] }}"></i>
                            <p>{{ $entry['label'] }}</p>
                        </a>
                    </li>
                @endif
            @endforeach
        @endforeach
    </ul>
</nav>
