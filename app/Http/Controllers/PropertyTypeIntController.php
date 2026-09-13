<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesAccountTitles;
use Illuminate\Http\Request;

class PropertyTypeIntController extends Controller
{
    use ManagesAccountTitles;

    protected function accountTitleType(): array
    {
        return [
            'property_id' => 4,
            'view' => 'manage.property.listINT',
            'edit_var' => 'intProperties',
            'list_route' => 'intRead',
            'edit_route' => 'intEdit',
        ];
    }

    public function intRead()
    {
        return $this->accountTitleIndex();
    }

    public function intCreate(Request $request)
    {
        return $this->accountTitleStore($request);
    }

    public function intEdit($id)
    {
        return $this->accountTitleIndex((int) $id);
    }

    public function intUpdate(Request $request)
    {
        return $this->accountTitleUpdate($request);
    }

    public function intDelete($id)
    {
        return $this->accountTitleDestroy($id);
    }
}
