<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\LikeTerm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $search = trim((string) $request->query('q', ''));
        $search = mb_substr($search, 0, 100);

        $logs = AuditLog::query()
            ->with('actor')
            ->when(mb_strlen($search) >= 2, function ($query) use ($search) {
                $like = LikeTerm::contains($search);

                $query->where(function ($query) use ($like) {
                    $query->where('action', 'like', $like)
                        ->orWhereHas('actor', fn ($query) => $query->where('name', 'like', $like));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'search' => $search,
        ]);
    }
}
