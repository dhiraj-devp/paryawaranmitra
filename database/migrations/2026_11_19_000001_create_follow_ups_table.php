<?php

use App\Services\FollowUps\ImportLegacyEnquiryFollowUps;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->string('followable_type');
            $table->unsignedBigInteger('followable_id');
            $table->string('type', 20);
            $table->dateTime('scheduled_at')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes');
            $table->dateTime('reminder_at')->nullable()->index();
            $table->string('status', 20)->index();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('reminded_at')->nullable();
            $table->unsignedBigInteger('legacy_enquiry_follow_up_id')->nullable()->unique();
            $table->string('import_key', 80)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['followable_type', 'followable_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['assigned_to', 'status']);
        });

        app(ImportLegacyEnquiryFollowUps::class)->execute();
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
