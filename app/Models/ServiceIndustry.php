<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceIndustry extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'industry_category',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
