<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('name_key')->index();
            $table->string('trade_name')->nullable();
            $table->string('constitution', 40);
            $table->string('status', 20)->index();
            $table->string('pan', 10)->nullable()->unique();
            $table->string('gstin', 15)->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->default('Maharashtra');
            $table->string('pincode', 6)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('industry_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('name_key');
            $table->string('status', 20)->index();
            $table->string('industry_category', 40);
            $table->text('activity')->nullable();
            $table->string('site_address_line');
            $table->string('site_landmark')->nullable();
            $table->string('site_village')->nullable();
            $table->string('site_taluka')->nullable();
            $table->string('site_district');
            $table->string('site_state')->default('Maharashtra');
            $table->string('site_pincode', 6);
            $table->string('plot_number')->nullable();
            $table->string('site_contact_name')->nullable();
            $table->string('site_contact_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['client_id', 'name_key']);
            $table->index('industry_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_units');
        Schema::dropIfExists('clients');
    }
};
