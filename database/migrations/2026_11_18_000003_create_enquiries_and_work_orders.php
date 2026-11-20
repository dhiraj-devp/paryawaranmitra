<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->date('enquiry_date')->index();
            $table->foreignId('client_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('industry_unit_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('contact_name');
            $table->string('mobile', 20);
            $table->string('email')->nullable();
            $table->string('source', 20)->index();
            $table->foreignId('service_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('requirement');
            $table->text('internal_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('next_follow_up_on')->nullable()->index();
            $table->string('status', 20)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('enquiry_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained()->restrictOnDelete();
            $table->dateTime('followed_up_at')->index();
            $table->string('method', 20);
            $table->text('notes');
            $table->string('outcome', 160);
            $table->date('next_follow_up_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['enquiry_id', 'followed_up_at']);
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('enquiry_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('industry_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('work_description');
            $table->date('start_date')->index();
            $table->date('target_completion_date')->nullable();
            $table->string('status', 20)->index();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('enquiry_follow_ups');
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('number_sequences');
    }
};
