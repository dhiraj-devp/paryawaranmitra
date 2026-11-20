<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class FollowUp extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'scheduled_at',
        'assigned_to',
        'notes',
        'reminder_at',
        'status',
        'completed_at',
        'reminded_at',
        'legacy_enquiry_follow_up_id',
        'import_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FollowUpType::class,
            'scheduled_at' => 'datetime',
            'reminder_at' => 'datetime',
            'status' => FollowUpStatus::class,
            'completed_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === FollowUpStatus::Pending
            && $this->scheduled_at instanceof Carbon
            && $this->scheduled_at->lt(now());
    }

    public function displayStatus(): string
    {
        if ($this->isOverdue()) {
            return __('ui.follow_ups.overdue');
        }

        return $this->status->label();
    }

    public function displayTone(): string
    {
        if ($this->isOverdue()) {
            return 'danger';
        }

        return $this->status->tone();
    }
}
