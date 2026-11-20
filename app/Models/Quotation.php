<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Quotation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new RuntimeException('Quotations are not deleted. Cancel the quotation with a status instead.');
        });
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(QuotationRevision::class, 'current_revision_id');
    }

    public function acceptedRevision(): BelongsTo
    {
        return $this->belongsTo(QuotationRevision::class, 'accepted_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(QuotationRevision::class)->orderBy('revision_number');
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
            'quotation_number' => $this->quotation_number,
            'work_order_id' => $this->work_order_id,
            'current_revision_id' => $this->current_revision_id,
            'accepted_revision_id' => $this->accepted_revision_id,
            'status' => $this->status?->value,
        ];
    }
}
