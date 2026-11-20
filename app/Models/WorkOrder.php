<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class WorkOrder extends Model
{
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'work_description',
        'start_date',
        'target_completion_date',
        'internal_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_completion_date' => 'date',
            'status' => WorkOrderStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new RuntimeException('Work orders are not deleted. Close the work order with a status instead.');
        });
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function industryUnit(): BelongsTo
    {
        return $this->belongsTo(IndustryUnit::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
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

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        return [
            'number' => $this->number,
            'enquiry_id' => $this->enquiry_id,
            'client_id' => $this->client_id,
            'industry_unit_id' => $this->industry_unit_id,
            'service_id' => $this->service_id,
            'assigned_to' => $this->assigned_to,
            'work_description' => $this->work_description,
            'start_date' => $this->start_date?->toDateString(),
            'target_completion_date' => $this->target_completion_date?->toDateString(),
            'status' => $this->status?->value,
            'internal_notes' => $this->internal_notes,
        ];
    }
}
