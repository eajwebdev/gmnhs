<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesAccountTitles;
use Illuminate\Http\Request;

class PropertyTypeLowController extends Controller
{
    use ManagesAccountTitles;

    protected function accountTitleType(): array
    {
        return [
            'property_id' => 2,
            'view' => 'manage.property.listLV',
            'edit_var' => 'lvProperties',
            'list_route' => 'lvRead',
            'edit_route' => 'lvEdit',
        ];
    }

    public function lvRead()
    {
        return $this->accountTitleIndex();
    }

    public function lvCreate(Request $request)
    {
        return $this->accountTitleStore($request);
    }

    public function lvEdit($id)
    {
        return $this->accountTitleIndex((int) $id);
    }

    public function lvUpdate(Request $request)
    {
        return $this->accountTitleUpdate($request);
    }

    public function lvDelete($id)
    {
        return $this->accountTitleDestroy($id);
    }
}
