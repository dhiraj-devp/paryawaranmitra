<?php

namespace App\Services\Catalog;

use App\Enums\RecordStatus;
use App\Models\Service;

class ServiceEligibility
{
    public function allows(Service $service, string $category): bool
    {
        if ($service->status !== RecordStatus::Active) {
            return false;
        }

        if (! in_array($category, config('industries.categories', []), true)) {
            return false;
        }

        return $service->industries()->where('industry_category', $category)->exists();
    }
}
