<?php

namespace App\Models;

use App\Enums\EnquirySource;
use App\Enums\EnquiryStatus;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use RuntimeException;

class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'enquiry_date',
        'client_id',
        'industry_unit_id',
        'contact_name',
        'mobile',
        'email',
        'source',
        'service_id',
        'requirement',
        'internal_notes',
        'assigned_to',
        'next_follow_up_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enquiry_date' => 'date',
            'next_follow_up_on' => 'date',
            'source' => EnquirySource::class,
            'status' => EnquiryStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Enquiry $enquiry): void {
            if ($enquiry->hasDependents()) {
                throw new RuntimeException('Enquiries are not deleted. Close the enquiry with a status instead.');
            }
        });
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

    public function followUps(): HasMany
    {
        return $this->hasMany(EnquiryFollowUp::class)->orderByDesc('followed_up_at')->orderByDesc('id');
    }

    public function followUpRecords(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'followable')->orderBy('scheduled_at')->orderBy('id');
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    public function hasDependents(): bool
    {
        return $this->workOrder()->exists()
            || $this->followUps()->exists()
            || $this->followUpRecords()->exists();
    }

    public function isDeletable(): bool
    {
        $flags = ['work_order_exists', 'follow_ups_exists', 'follow_up_records_exists'];

        if (array_key_exists($flags[0], $this->attributes)) {
            foreach ($flags as $flag) {
                if ($this->attributes[$flag] ?? false) {
                    return false;
                }
            }

            return true;
        }

        return ! $this->hasDependents();
    }

    public function followUpIsOverdue(): bool
    {
        if ($this->next_follow_up_on === null) {
            return false;
        }

        if (in_array($this->status, [EnquiryStatus::Converted, EnquiryStatus::Lost], true)) {
            return false;
        }

        return $this->next_follow_up_on->toDateString() < today()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        return [
            'number' => $this->number,
            'enquiry_date' => $this->enquiry_date?->toDateString(),
            'client_id' => $this->client_id,
            'industry_unit_id' => $this->industry_unit_id,
            'contact_name' => $this->contact_name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'source' => $this->source?->value,
            'service_id' => $this->service_id,
            'requirement' => $this->requirement,
            'internal_notes' => $this->internal_notes,
            'assigned_to' => $this->assigned_to,
            'next_follow_up_on' => $this->next_follow_up_on?->toDateString(),
            'status' => $this->status?->value,
        ];
    }
}
