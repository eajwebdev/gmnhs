<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables for the yearly physical inventory count. InventoryController and the
 * mobile API read and write them, but the standalone schema never created them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('yearly_inventories')) {
            Schema::create('yearly_inventories', function (Blueprint $table) {
                $table->id();
                $table->string('inv_status')->default('Ongoing');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_histories')) {
            Schema::create('inventory_histories', function (Blueprint $table) {
                $table->id();
                $table->string('uid')->nullable();
                $table->unsignedBigInteger('prop_id')->index();
                $table->unsignedBigInteger('inv_id')->nullable()->index();
                $table->string('office_id')->nullable();
                $table->unsignedTinyInteger('accnt_type')->nullable();
                $table->string('person_accnt')->nullable();
                $table->string('remarks')->nullable();
                $table->string('item_status')->nullable();
                $table->unsignedTinyInteger('inv_status')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_histories');
        Schema::dropIfExists('yearly_inventories');
    }
};
