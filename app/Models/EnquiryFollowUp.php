<?php

namespace App\Models;

use App\Enums\FollowUpMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class EnquiryFollowUp extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'followed_up_at',
        'method',
        'notes',
        'outcome',
        'next_follow_up_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'followed_up_at' => 'datetime',
            'next_follow_up_on' => 'date',
            'method' => FollowUpMethod::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Follow-up history is append-only.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Follow-up history is append-only.');
        });
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
