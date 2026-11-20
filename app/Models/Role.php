<?php

namespace App\Models;

use App\Enums\Portal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use RuntimeException;

class Role extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'portal',
        'description',
        'is_system',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'portal' => Portal::class,
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Role $role) {
            if ($role->is_system) {
                throw new RuntimeException('System roles cannot be deleted.');
            }
        });
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function label(): string
    {
        $key = 'ui.roles.'.str_replace('-', '_', $this->slug);
        $label = __($key);

        return $label === $key ? $this->name : $label;
    }
}
