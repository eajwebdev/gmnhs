<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesAccountTitles;
use Illuminate\Http\Request;

class PropertyTypeController extends Controller
{
    use ManagesAccountTitles;

    protected function accountTitleType(): array
    {
        return [
            'property_id' => 3,
            'view' => 'manage.property.listPPE',
            'edit_var' => 'ppeProperties',
            'list_route' => 'ppeRead',
            'edit_route' => 'ppeEdit',
        ];
    }

    public function ppeRead()
    {
        return $this->accountTitleIndex();
    }

    public function ppeCreate(Request $request)
    {
        return $this->accountTitleStore($request);
    }

    public function ppeEdit($id)
    {
        return $this->accountTitleIndex((int) $id);
    }

    public function ppeUpdate(Request $request)
    {
        return $this->accountTitleUpdate($request);
    }

    public function ppeDelete($id)
    {
        return $this->accountTitleDestroy($id);
    }
}
