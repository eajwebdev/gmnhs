<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesAccountTitles;
use Illuminate\Http\Request;

class PropertyTypeHighController extends Controller
{
    use ManagesAccountTitles;

    protected function accountTitleType(): array
    {
        return [
            'property_id' => 1,
            'view' => 'manage.property.listHV',
            'edit_var' => 'hvProperties',
            'list_route' => 'hvRead',
            'edit_route' => 'hvEdit',
        ];
    }

    public function hvRead()
    {
        return $this->accountTitleIndex();
    }

    public function hvCreate(Request $request)
    {
        return $this->accountTitleStore($request);
    }

    public function hvEdit($id)
    {
        return $this->accountTitleIndex((int) $id);
    }

    public function hvUpdate(Request $request)
    {
        return $this->accountTitleUpdate($request);
    }

    public function hvDelete($id)
    {
        return $this->accountTitleDestroy($id);
    }
}
