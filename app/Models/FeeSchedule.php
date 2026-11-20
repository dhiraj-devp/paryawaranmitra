<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\FeeScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

class FeeSchedule extends Model
{
    /** @use HasFactory<FeeScheduleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'effective_from',
        'effective_until',
        'government_fee',
        'consultancy_fee',
        'internal_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'government_fee' => 'decimal:2',
            'consultancy_fee' => 'decimal:2',
            'status' => RecordStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new RuntimeException('Fee schedules are not deleted. Deactivate the schedule instead.');
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function period(?Carbon $on = null): string
    {
        $on ??= today();
        $from = $this->effective_from?->copy()->startOfDay();
        $until = $this->effective_until?->copy()->startOfDay();
        $day = $on->copy()->startOfDay();

        if ($from !== null && $from->gt($day)) {
            return 'upcoming';
        }

        if ($until !== null && $until->lt($day)) {
            return 'past';
        }

        return 'current';
    }

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        return [
            'service_id' => $this->service_id,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_until' => $this->effective_until?->toDateString(),
            'government_fee' => $this->government_fee,
            'consultancy_fee' => $this->consultancy_fee,
            'status' => $this->status?->value,
            'internal_notes' => $this->internal_notes,
        ];
    }
}
