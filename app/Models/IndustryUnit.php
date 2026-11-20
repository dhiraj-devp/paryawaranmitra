<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Support\NameKey;
use Database\Factories\IndustryUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class IndustryUnit extends Model
{
    /** @use HasFactory<IndustryUnitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'industry_category',
        'activity',
        'site_address_line',
        'site_landmark',
        'site_village',
        'site_taluka',
        'site_district',
        'site_state',
        'site_pincode',
        'plot_number',
        'site_contact_name',
        'site_contact_phone',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (IndustryUnit $unit): void {
            $unit->name_key = NameKey::make((string) $unit->name);
        });

        static::deleting(function (): void {
            throw new RuntimeException('Industry units are not deleted. Deactivate the unit instead.');
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categoryLabel(): string
    {
        $key = 'ui.units.categories.'.$this->industry_category;
        $label = __($key);

        return $label === $key ? $this->industry_category : $label;
    }

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        return [
            'client_id' => $this->client_id,
            'name' => $this->name,
            'status' => $this->status?->value,
            'industry_category' => $this->industry_category,
            'activity' => $this->activity,
            'site_address_line' => $this->site_address_line,
            'site_landmark' => $this->site_landmark,
            'site_village' => $this->site_village,
            'site_taluka' => $this->site_taluka,
            'site_district' => $this->site_district,
            'site_state' => $this->site_state,
            'site_pincode' => $this->site_pincode,
            'plot_number' => $this->plot_number,
            'site_contact_name' => $this->site_contact_name,
            'site_contact_phone' => $this->site_contact_phone,
            'notes' => $this->notes,
        ];
    }
}
