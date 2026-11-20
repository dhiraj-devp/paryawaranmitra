<?php

namespace App\Models;

use App\Enums\ClientConstitution;
use App\Enums\RecordStatus;
use App\Support\NameKey;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use RuntimeException;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legal_name',
        'trade_name',
        'constitution',
        'pan',
        'gstin',
        'email',
        'phone',
        'contact_name',
        'address_line',
        'city',
        'district',
        'state',
        'pincode',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'constitution' => ClientConstitution::class,
            'status' => RecordStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Client $client): void {
            $client->name_key = NameKey::make((string) $client->legal_name);
        });

        static::deleting(function (Client $client): void {
            if ($client->hasDependents()) {
                throw new RuntimeException('Clients are not deleted. Deactivate the client instead.');
            }
        });
    }

    public function industryUnits(): HasMany
    {
        return $this->hasMany(IndustryUnit::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function hasDependents(): bool
    {
        return $this->industryUnits()->exists()
            || $this->enquiries()->exists()
            || $this->workOrders()->exists()
            || $this->followUpRecords()->exists();
    }

    public function isDeletable(): bool
    {
        if (array_key_exists('industry_units_count', $this->attributes) && array_key_exists('enquiries_exists', $this->attributes)) {
            return (int) $this->industry_units_count === 0
                && ! $this->enquiries_exists
                && ! $this->work_orders_exists
                && ! $this->follow_up_records_exists;
        }

        return ! $this->hasDependents();
    }

    public function followUpRecords(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'followable')->orderBy('scheduled_at')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        return [
            'legal_name' => $this->legal_name,
            'trade_name' => $this->trade_name,
            'constitution' => $this->constitution?->value,
            'status' => $this->status?->value,
            'pan' => $this->pan,
            'gstin' => $this->gstin,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_name' => $this->contact_name,
            'address_line' => $this->address_line,
            'city' => $this->city,
            'district' => $this->district,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'notes' => $this->notes,
        ];
    }
}
