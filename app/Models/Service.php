<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'detailed_description',
        'expected_processing_days',
        'internal_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'expected_processing_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): void {
            throw new RuntimeException('Services are not deleted. Deactivate the service instead.');
        });
    }

    public function industries(): HasMany
    {
        return $this->hasMany(ServiceIndustry::class);
    }

    public function feeSchedules(): HasMany
    {
        return $this->hasMany(FeeSchedule::class);
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
     * @return list<string>
     */
    public function categorySlugs(): array
    {
        return $this->industries
            ->pluck('industry_category')
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'detailed_description' => $this->detailed_description,
            'expected_processing_days' => $this->expected_processing_days,
            'status' => $this->status?->value,
            'internal_notes' => $this->internal_notes,
        ];
    }
}
