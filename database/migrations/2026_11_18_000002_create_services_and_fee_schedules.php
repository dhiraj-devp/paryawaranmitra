<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('category', 80)->index();
            $table->string('description', 500);
            $table->text('detailed_description')->nullable();
            $table->unsignedSmallInteger('expected_processing_days')->nullable();
            $table->string('status', 20)->index();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('service_industries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('industry_category', 40);
            $table->unique(['service_id', 'industry_category']);
            $table->index('industry_category');
        });

        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->date('effective_from')->index();
            $table->date('effective_until')->nullable()->index();
            $table->decimal('government_fee', 14, 2);
            $table->decimal('consultancy_fee', 14, 2);
            $table->string('status', 20)->index();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['service_id', 'status', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_schedules');
        Schema::dropIfExists('service_industries');
        Schema::dropIfExists('services');
    }
};
