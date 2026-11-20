<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SubjectHistory
{
    /**
     * @return Collection<int, AuditLog>
     */
    public function for(Model $subject, int $limit = 30): Collection
    {
        return AuditLog::query()
            ->with('actor')
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
