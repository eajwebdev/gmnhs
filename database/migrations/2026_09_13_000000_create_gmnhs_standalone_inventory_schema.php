<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('schools', function (Blueprint $table) {
            $table->id();
            $table->string('school_name');
            $table->string('school_abbr');
            $table->timestamps();
        });

        $this->createTable('settings', function (Blueprint $table) {
            $table->id();
            $table->string('system_name');
            $table->string('photo_filename')->nullable();
            $table->timestamps();
        });

        $this->createTable('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->default(1);
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('gender')->nullable();
            $table->string('role');
            $table->json('access')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        $this->createTable('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        $this->createTable('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        $this->createTable('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        $this->createTable('inv_settings', function (Blueprint $table) {
            $table->id();
            $table->string('switch')->default('Off');
            $table->timestamps();
        });

        $this->createTable('units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_name');
            $table->timestamps();
        });

        $this->createTable('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('supply_type')->nullable();
            $table->string('ct')->nullable();
            $table->timestamps();
        });

        $this->createTable('offices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('office_code', 50);
            $table->string('office_name');
            $table->string('office_abbr');
            $table->string('office_officer');
            $table->unsignedBigInteger('school_id')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });

        $this->createTable('accountable', function (Blueprint $table) {
            $table->id();
            $table->string('person_accnt');
            $table->unsignedInteger('off_id')->nullable();
            $table->text('desig_offid')->nullable();
            $table->unsignedTinyInteger('accnt_role')->default(0);
            $table->timestamps();
        });

        $this->createTable('property', function (Blueprint $table) {
            $table->increments('id');
            $table->string('default_code')->default('');
            $table->string('property_code')->default('');
            $table->string('property_name');
            $table->string('abbreviation');
            $table->timestamps();
        });

        $this->createTable('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('property_id');
            $table->string('cat_name');
            $table->string('cat_code');
            $table->timestamps();
        });

        $this->createTable('properties', function (Blueprint $table) {
            $table->increments('id');
            $table->string('property_id');
            $table->string('category_id');
            $table->string('account_number');
            $table->string('account_title');
            $table->string('account_title_abbr')->nullable();
            $table->string('code');
            $table->timestamps();
        });

        $this->createTable('purchases', function (Blueprint $table) {
            $table->increments('id');
            $table->string('po_number')->nullable();
            $table->string('office_id');
            $table->string('item_id');
            $table->string('item_descrip');
            $table->string('item_model')->nullable();
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('date_acquired');
            $table->string('unit_id');
            $table->string('qty');
            $table->string('qty_release')->default('0');
            $table->string('item_cost');
            $table->string('total_cost');
            $table->string('properties_id');
            $table->string('categories_id')->nullable();
            $table->string('property_id')->nullable();
            $table->string('item_number')->nullable();
            $table->string('property_no_generated')->nullable();
            $table->string('selected_account_id')->nullable();
            $table->string('status')->default('Good Condition');
            $table->string('remarks')->default('N/A');
            $table->string('date_stat')->nullable();
            $table->string('price_stat')->nullable();
            $table->string('person_accnt')->nullable();
            $table->string('person_accnt_name')->nullable();
            $table->string('print_stat')->default('1');
            $table->string('supply_type')->nullable();
            $table->timestamps();
        });

        $this->createTable('inventories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('purch_id')->nullable();
            $table->string('property_id')->nullable();
            $table->string('categories_id')->nullable();
            $table->string('properties_id')->nullable();
            $table->string('office_id')->nullable();
            $table->string('item_id')->nullable();
            $table->string('item_descrip')->nullable();
            $table->string('item_model')->nullable();
            $table->text('description')->nullable();
            $table->string('item_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('unit_id')->nullable();
            $table->string('item_cost')->nullable();
            $table->string('qty')->nullable();
            $table->string('total_cost')->nullable();
            $table->string('property_no_generated')->nullable();
            $table->string('selected_account_id')->nullable();
            $table->string('status')->default('Good Condition');
            $table->string('remarks')->default('N/A');
            $table->string('date_acquired')->nullable();
            $table->string('date_stat')->nullable();
            $table->string('price_stat')->nullable();
            $table->string('person_accnt')->nullable();
            $table->string('person_accnt1')->nullable();
            $table->string('serial_owned')->nullable();
            $table->string('person_accnt_name')->nullable();
            $table->string('print_stat')->default('1');
            $table->timestamps();
        });

        $this->createTable('enduser_property', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('purch_id')->nullable();
            $table->string('property_id')->nullable();
            $table->string('prop_code')->nullable();
            $table->string('categories_id')->nullable();
            $table->string('properties_id')->nullable();
            $table->string('office_id')->nullable();
            $table->string('location')->nullable();
            $table->string('item_id')->nullable();
            $table->string('item_descrip')->nullable();
            $table->string('item_model')->nullable();
            $table->text('description')->nullable();
            $table->string('item_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('unit_id')->nullable();
            $table->string('item_cost')->nullable();
            $table->string('qty')->nullable();
            $table->string('total_cost')->nullable();
            $table->string('property_no_generated_old')->nullable();
            $table->string('property_no_generated')->nullable();
            $table->string('selected_account_id')->nullable();
            $table->string('status')->default('Good Condition');
            $table->string('remarks')->default('N/A');
            $table->string('date_acquired')->nullable();
            $table->string('date_stat')->nullable();
            $table->string('price_stat')->nullable();
            $table->string('person_accnt')->nullable();
            $table->string('person_accnt1')->nullable();
            $table->string('serial_owned')->nullable();
            $table->string('person_accnt_name')->nullable();
            $table->string('print_stat')->default('1');
            $table->string('supply_type')->nullable();
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });

        $this->createTable('repairs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uid')->nullable();
            $table->unsignedInteger('prop_id')->nullable();
            $table->text('findings')->nullable();
            $table->string('urgency')->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('diagnose_by')->nullable();
            $table->string('repair_status')->nullable();
            $table->timestamp('date_diagnose')->nullable();
            $table->string('release_by')->nullable();
            $table->timestamp('release_date')->nullable();
            $table->timestamps();
        });

        $this->createTable('logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->default(1);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->string('module')->nullable();
            $table->string('action')->nullable();
            $table->timestamps();
        });

        $this->createTable('return_slips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('requested_by')->nullable();
            $table->unsignedBigInteger('school_id')->default(1);
            $table->unsignedBigInteger('returned_by_id')->nullable();
            $table->string('returned_by_name')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('request_type')->nullable();
            $table->unsignedInteger('target_office_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('Pending');
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        $this->createTable('return_slip_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('return_slip_id');
            $table->unsignedInteger('enduser_property_id')->nullable();
            $table->string('property_no_generated')->nullable();
            $table->string('item_name')->nullable();
            $table->string('serial_number')->nullable();
            $table->unsignedInteger('current_office_id')->nullable();
            $table->unsignedInteger('current_location_id')->nullable();
            $table->string('current_status')->nullable();
            $table->string('previous_remarks')->nullable();
            $table->string('status')->default('Returned');
            $table->string('action_type')->nullable();
            $table->unsignedBigInteger('transferred_to_enduser_id')->nullable();
            $table->string('transferred_to_enduser_name')->nullable();
            $table->unsignedInteger('transferred_to_office_id')->nullable();
            $table->unsignedInteger('transferred_to_location_id')->nullable();
            $table->unsignedBigInteger('actioned_by')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('action_remarks')->nullable();
            $table->timestamps();
        });

        $this->createTable('return_slip_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('return_slip_id')->nullable();
            $table->unsignedBigInteger('return_slip_item_id')->nullable();
            $table->unsignedInteger('enduser_property_id')->nullable();
            $table->string('property_no_generated')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('from_value')->nullable();
            $table->string('to_value')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        $this->ensureStandaloneColumns();
        $this->seedStandaloneDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('return_slip_logs');
        Schema::dropIfExists('return_slip_items');
        Schema::dropIfExists('return_slips');
        Schema::dropIfExists('logs');
        Schema::dropIfExists('repairs');
        Schema::dropIfExists('enduser_property');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('property');
        Schema::dropIfExists('accountable');
        Schema::dropIfExists('offices');
        Schema::dropIfExists('items');
        Schema::dropIfExists('units');
        Schema::dropIfExists('inv_settings');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('users');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('schools');
    }

    private function createTable(string $tableName, Closure $callback): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, $callback);
        }
    }

    private function ensureStandaloneColumns(): void
    {
        $this->addColumnIfMissing('users', 'gender', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('password');
        });

        $this->addColumnIfMissing('users', 'school_id', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->default(1)->after('id');
        });

        $this->addColumnIfMissing('users', 'access', function (Blueprint $table) {
            $table->json('access')->nullable()->after('role');
        });

        $this->addColumnIfMissing('items', 'supply_type', function (Blueprint $table) {
            $table->string('supply_type')->nullable()->after('item_name');
        });

        $this->addColumnIfMissing('items', 'ct', function (Blueprint $table) {
            $table->string('ct')->nullable()->after('supply_type');
        });

        $this->addColumnIfMissing('offices', 'school_id', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->default(1)->after('office_officer');
        });

        $this->addColumnIfMissing('properties', 'account_title_abbr', function (Blueprint $table) {
            $table->string('account_title_abbr')->nullable()->after('account_title');
        });

        foreach ([
            'po_number', 'item_model', 'description', 'qty_release', 'selected_account_id',
            'price_stat', 'person_accnt', 'person_accnt_name', 'supply_type',
        ] as $column) {
            $this->addColumnIfMissing('purchases', $column, function (Blueprint $table) use ($column) {
                $column === 'description'
                    ? $table->text($column)->nullable()
                    : $table->string($column)->nullable();
            });
        }
    }

    private function addColumnIfMissing(string $tableName, string $columnName, Closure $callback): void
    {
        if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, $columnName)) {
            Schema::table($tableName, $callback);
        }
    }

    private function seedStandaloneDefaults(): void
    {
        $now = now();

        DB::table('schools')->updateOrInsert(
            ['id' => 1],
            ['school_name' => 'GIL MONTILLA NATIONAL HIGH SCHOOL', 'school_abbr' => 'GMNHS', 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('schools')->where('id', '<>', 1)->delete();

        DB::table('settings')->updateOrInsert(
            ['id' => 1],
            ['system_name' => 'GMNHS PPEI', 'photo_filename' => 'logo.png', 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('inv_settings')->updateOrInsert(
            ['id' => 1],
            ['switch' => 'Off', 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('users')->updateOrInsert(
            ['username' => 'admin'],
            [
                'school_id' => 1,
                'fname' => 'PEARL JOY',
                'mname' => '',
                'lname' => 'POSITAR',
                'password' => Hash::make('password'),
                'role' => 'Administrator',
                'access' => json_encode($this->defaultAccessForRole('Administrator')),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('users')->update(['school_id' => 1, 'updated_at' => $now]);

        DB::table('offices')->update(['school_id' => 1, 'updated_at' => $now]);
        DB::table('accountable')->where('accnt_role', 2)->update(['accnt_role' => 0, 'updated_at' => $now]);
        DB::table('offices')->where('id', 1)->update([
            'office_code' => '0001',
            'office_name' => 'GIL MONTILLA NATIONAL HIGH SCHOOL',
            'office_abbr' => 'GMNHS',
            'office_officer' => 'ALEXANDRA M. VILLAROSA',
            'updated_at' => $now,
        ]);

        foreach ($this->signatoryNames() as $name) {
            DB::table('accountable')->updateOrInsert(
                ['person_accnt' => $name],
                [
                    'off_id' => 1,
                    'desig_offid' => json_encode([1]),
                    'accnt_role' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function signatoryNames(): array
    {
        return [
            'ALEXANDRA M. VILLAROSA',
            'BENJAMIN R. DELA TORRE',
            'CAMILLE A. NAVARRETE',
            'DANIEL P. MONTECLARO',
            'ELEANOR V. SANTIAGO',
            'FRANCIS L. MENDOZA',
            'GABRIEL N. SORIANO',
            'HANNAH C. VALDEZ',
        ];
    }

    private function defaultAccessForRole(string $role): array
    {
        return match ($role) {
            'Administrator' => ['dashboard', 'view', 'purchases', 'properties', 'inventory', 'reports', 'repair', 'return_slips', 'users', 'settings'],
            'Supply Officer' => ['dashboard', 'view', 'purchases', 'properties', 'inventory', 'reports', 'return_slips', 'users'],
            'School Admin' => ['dashboard', 'view', 'properties', 'inventory', 'reports', 'return_slips', 'settings'],
            'Supply Staff' => ['dashboard', 'view', 'properties', 'inventory', 'reports', 'return_slips'],
            'Technician' => ['dashboard', 'repair', 'return_slips'],
            default => ['dashboard'],
        };
    }
};


