<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number', 20)->unique();
            $table->foreignId('work_order_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->unsignedBigInteger('accepted_revision_id')->nullable();
            $table->string('status', 20)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['work_order_id', 'status']);
        });

        Schema::create('quotation_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->date('issue_date')->index();
            $table->date('valid_until')->index();
            $table->decimal('government_total', 14, 2);
            $table->decimal('consultancy_total', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount_amount', 14, 2);
            $table->decimal('tax_amount', 14, 2);
            $table->decimal('grand_total', 14, 2);
            $table->text('payment_terms')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['quotation_id', 'revision_number']);
        });

        Schema::create('quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_revision_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sort_order');
            $table->string('line_type', 20);
            $table->string('charge_side', 20)->nullable();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2);
            $table->decimal('government_fee', 14, 2);
            $table->decimal('consultancy_fee', 14, 2);
            $table->decimal('discount_amount', 14, 2);
            $table->foreignId('fee_schedule_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('schedule_government_fee', 14, 2)->nullable();
            $table->decimal('schedule_consultancy_fee', 14, 2)->nullable();
            $table->boolean('amounts_adjusted')->default(false);
            $table->string('tax_code', 40)->nullable();
            $table->decimal('tax_amount', 14, 2);
            $table->decimal('government_total', 14, 2);
            $table->decimal('consultancy_total', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();

            $table->index(['quotation_revision_id', 'sort_order']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->foreign('current_revision_id')->references('id')->on('quotation_revisions')->restrictOnDelete();
            $table->foreign('accepted_revision_id')->references('id')->on('quotation_revisions')->restrictOnDelete();
            $table->unique('current_revision_id');
            $table->unique('accepted_revision_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['current_revision_id']);
            $table->dropForeign(['accepted_revision_id']);
            $table->dropUnique(['current_revision_id']);
            $table->dropUnique(['accepted_revision_id']);
        });

        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('quotation_revisions');
        Schema::dropIfExists('quotations');
    }
};
