<?php

namespace Tests\Feature;

use App\Models\Accountable;
use App\Models\Item;
use App\Models\Office;
use App\Models\Properties;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Runs against the configured database inside a transaction that is rolled
 * back after every test, so no records are left behind.
 */
class CrudSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('role', 'Administrator')->firstOrFail();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in to the registry');
    }

    public function test_pages_render(): void
    {
        $this->actingAs($this->admin());

        $pages = [
            '/dashboard', '/view/property/listPPE', '/view/property/listLV', '/view/property/listHV',
            '/view/property/listINT', '/view/unit/list', '/view/item/list', '/view/office/list/1',
            '/view/office/list/2', '/view/accntperson/list', '/users/list', '/settings/account-settings',
            '/settings/system-name', '/purchases/list/all', '/properties/list/4', '/properties/list/3',
            '/properties/list/1', '/properties/list/2', '/properties/sticker', '/properties/blank-sticker',
            '/inventory/list', '/report', '/technician/repair', '/return-slips',
            '/return-slips/logs', '/return-slips/iirup-report', '/return-slips/slip-report',
            '/return-slips/transfer-report',
        ];

        $failures = [];
        foreach ($pages as $page) {
            $response = $this->get($page);
            if (!in_array($response->getStatusCode(), [200, 302], true)) {
                $failures[] = $page.' => '.$response->getStatusCode().' '.substr((string) optional($response->exception)->getMessage(), 0, 220);
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_unit_crud(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('unitCreate'), ['unit_name' => 'Zz Test Unit'])->assertSessionHas('success');
        $unit = Unit::where('unit_name', 'Zz Test Unit')->firstOrFail();

        $this->get(route('unitEdit', $unit->id))->assertOk();
        $this->post(route('unitUpdate'), ['id' => $unit->id, 'unit_name' => 'Zz Test Unit 2'])->assertSessionHas('success');
        $this->assertSame('Zz Test Unit 2', $unit->fresh()->unit_name);

        $this->get(route('unitDelete', $unit->id))->assertOk()->assertJson(['status' => 200]);
        $this->assertNull(Unit::find($unit->id));
        $this->get(route('unitDelete', $unit->id))->assertStatus(404);
    }

    public function test_item_crud(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('itemCreate'), ['item_name' => 'Zz Test Item'])->assertSessionHas('success');
        $item = Item::where('item_name', 'Zz Test Item')->firstOrFail();

        $this->get(route('itemEdit', $item->id))->assertOk();
        $this->post(route('itemUpdate'), ['id' => $item->id, 'item_name' => 'Zz Test Item 2'])->assertSessionHas('success');
        $this->assertSame('Zz Test Item 2', $item->fresh()->item_name);

        $this->get(route('itemDelete', $item->id))->assertOk();
        $this->assertNull(Item::find($item->id));

        $this->post(route('itemCreate'), ['item_name' => ''])->assertSessionHasErrors('item_name');
    }

    public function test_office_and_location_crud(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('officeCreate'), [
            'code' => 1, 'office_code' => '0999', 'office_name' => 'ZZ TEST OFFICE',
            'office_abbr' => 'ZTO', 'office_officer' => 'Test Head',
        ])->assertSessionHas('success');
        $office = Office::where('office_name', 'ZZ TEST OFFICE')->firstOrFail();

        $this->get(route('officeEdit', ['id' => $office->id, 'code' => 1]))->assertOk();
        $this->post(route('officeUpdate'), [
            'id' => $office->id, 'code' => 1, 'office_code' => '0999', 'office_name' => 'ZZ TEST OFFICE 2',
            'office_abbr' => 'ZTO', 'office_officer' => 'Test Head',
        ])->assertSessionHas('success');
        $this->assertSame('ZZ TEST OFFICE 2', $office->fresh()->office_name);

        $this->post(route('officeCreate'), [
            'code' => 2, 'office_code' => '0000', 'office_name' => 'ZZ TEST ROOM', 'school_id' => 1,
        ])->assertSessionHas('success');
        $location = Office::where('office_name', 'ZZ TEST ROOM')->firstOrFail();

        $this->get(route('officeEdit', ['id' => $location->id, 'code' => 2]))->assertOk();
        $this->post(route('officeUpdate'), [
            'id' => $location->id, 'code' => 2, 'office_code' => '0000', 'office_name' => 'ZZ TEST ROOM 2', 'school_id' => 1,
        ])->assertSessionHas('success');
        $this->assertSame('ZZ TEST ROOM 2', $location->fresh()->office_name);

        $this->post(route('officeCreate'), [
            'code' => 1, 'office_code' => '12', 'office_name' => 'ZZ PADDED OFFICE',
            'office_abbr' => 'ZPO', 'office_officer' => 'Someone',
        ])->assertSessionHas('success');
        $this->assertSame('0012', Office::where('office_name', 'ZZ PADDED OFFICE')->value('office_code'));

        $this->get(route('officeDelete', $location->id))->assertOk();
        $this->get(route('officeDelete', $office->id))->assertOk();
    }

    public function test_accountable_crud(): void
    {
        $this->actingAs($this->admin());
        $officeId = Office::where('office_code', '!=', '0000')->value('id');

        $this->post(route('accountableCreate'), [
            'person_accnt' => 'Zz Test Person', 'accnt_role' => 1, 'off_id' => $officeId, 'desig_offid' => [$officeId],
        ])->assertSessionHas('success');
        $person = Accountable::where('person_accnt', 'Zz Test Person')->firstOrFail();

        $this->get(route('accountableEdit', $person->id))->assertOk();
        $this->post(route('accountableUpdate'), [
            'id' => $person->id, 'person_accnt' => 'Zz Test Person 2', 'accnt_role' => 0, 'off_id' => $officeId,
        ])->assertSessionHas('success');
        $this->assertSame('Zz Test Person 2', $person->fresh()->person_accnt);

        $this->get(route('accountableDelete', $person->id))->assertOk();
        $this->assertNull(Accountable::find($person->id));
    }

    public function test_property_type_crud(): void
    {
        $this->actingAs($this->admin());

        $types = [
            3 => ['ppeCreate', 'ppeEdit', 'ppeUpdate', 'ppeDelete'],
            2 => ['lvCreate', 'lvEdit', 'lvUpdate', 'lvDelete'],
            1 => ['hvCreate', 'hvEdit', 'hvUpdate', 'hvDelete'],
            4 => ['intCreate', 'intEdit', 'intUpdate', 'intDelete'],
        ];

        foreach ($types as $propertyId => [$create, $edit, $update, $delete]) {
            $number = '9-99-9'.$propertyId.'-999';
            $this->post(route($create), [
                'property_id' => $propertyId, 'category_id' => '05', 'account_number' => $number,
                'account_title' => 'Zz Test Title', 'account_title_abbr' => 'ZZT', 'code' => '999',
            ])->assertSessionHas('success');

            // Same number again for the same type is rejected
            $this->post(route($create), [
                'property_id' => $propertyId, 'category_id' => '05', 'account_number' => $number,
                'account_title' => 'Dup', 'account_title_abbr' => 'D',
            ])->assertSessionHasErrors('account_number');
            $row = Properties::where('account_number', $number)->firstOrFail();

            $this->get(route($edit, $row->id))->assertOk();
            $this->post(route($update), [
                'id' => $row->id, 'property_id' => $propertyId, 'category_id' => '05', 'account_number' => $number,
                'account_title' => 'Zz Test Title 2', 'account_title_abbr' => 'ZZT', 'code' => '999',
            ])->assertSessionHas('success');
            $this->assertSame('Zz Test Title 2', $row->fresh()->account_title);

            $this->get(route($delete, $row->id))->assertOk();
            $this->assertNull(Properties::find($row->id));
        }
    }

    public function test_user_crud_keeps_password_when_left_blank(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('userCreate'), [
            'lname' => 'TEST', 'fname' => 'ZZ', 'mname' => 'Q', 'gender' => 'Male', 'username' => 'zztestuser',
            'password' => 'secret123', 'school_id' => 1, 'role' => 'Supply Staff', 'access' => ['dashboard', 'view'],
        ])->assertSessionHas('success');
        $user = User::where('username', 'zztestuser')->firstOrFail();
        $hash = $user->password;

        $this->get(route('userEdit', $user->id))->assertOk();
        $this->post(route('userUpdate'), [
            'id' => $user->id, 'lname' => 'TEST', 'fname' => 'ZZ2', 'mname' => 'Q', 'gender' => 'Male',
            'username' => 'zztestuser', 'password' => '', 'school_id' => 1, 'role' => 'Supply Staff', 'access' => ['dashboard'],
        ])->assertSessionHas('success');
        $this->assertSame('ZZ2', $user->fresh()->fname);
        $this->assertSame($hash, $user->fresh()->password);

        $this->get(route('userDelete', $this->admin()->id))->assertStatus(409);
        $this->get(route('userDelete', $user->id))->assertOk();
        $this->assertNull(User::find($user->id));
    }

    public function test_account_settings_update(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post(route('profileUpdate'), [
            'lname' => $admin->lname, 'fname' => $admin->fname, 'mname' => $admin->mname,
            'username' => $admin->username, 'gender' => $admin->gender ?: 'Male',
        ])->assertSessionHas('success');
    }

    public function test_purchase_release_flow(): void
    {
        $this->actingAs($this->admin());

        $purchase = \App\Models\Purchases::whereColumn('qty_release', '<', 'qty')->where('serial_number', 'like', '%-unrel%')->first();
        if (!$purchase) {
            $this->markTestSkipped('No unreleased purchase with serials to release.');
        }

        $office = Office::where('office_code', '!=', '0000')->whereIn('id', \App\Models\Accountable::pluck('off_id'))->firstOrFail();
        $get = $this->get(route('purchaseReleaseGet', $purchase->id))->assertOk()->json();
        $next = $this->get('/purchases/check-next-number/'.preg_replace('/^[^-]+-/', '', $get['pcode']).'/'.$office->office_code)->assertOk()->json();
        $serial = preg_replace('/-unrel$/', '', explode($purchase->unit_id == 2 ? ':' : ';', $purchase->serial_number)[0]);

        $this->post(route('purchaseReleasePost'), [
            'purchase_id' => $purchase->id, 'office_id' => $office->id, 'person_accnt' => $next['accountables'][0]['id'],
            'qty' => 1, 'date_acquired' => now()->toDateString(), 'itemnum' => $next['next_item_number'],
            'property_no_generated' => $get['pcode'].'-'.$next['next_item_number'].'-'.$office->office_code,
            'serial_number' => [$serial],
        ])->assertSessionHas('success');

        $this->assertStringNotContainsString($serial.'-unrel', $purchase->fresh()->serial_number);
        $this->assertSame((int) $purchase->qty_release + 1, (int) $purchase->fresh()->qty_release);
        $this->assertTrue(\App\Models\EnduserProperty::where('purch_id', $purchase->id)->where('serial_number', $serial)->exists());
    }
}
