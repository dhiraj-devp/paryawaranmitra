<?php

namespace App\Models;

use App\Enums\ChargeSide;
use App\Enums\QuotationLineType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class QuotationLine extends Model
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
            'line_type' => QuotationLineType::class,
            'charge_side' => ChargeSide::class,
            'quantity' => 'decimal:2',
            'government_fee' => 'decimal:2',
            'consultancy_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'schedule_government_fee' => 'decimal:2',
            'schedule_consultancy_fee' => 'decimal:2',
            'amounts_adjusted' => 'boolean',
            'tax_amount' => 'decimal:2',
            'government_total' => 'decimal:2',
            'consultancy_total' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Quotation lines are immutable. Save a new revision instead.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Quotation lines are not deleted.');
        });
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(QuotationRevision::class, 'quotation_revision_id');
    }

    public function feeSchedule(): BelongsTo
    {
        return $this->belongsTo(FeeSchedule::class);
    }
}
