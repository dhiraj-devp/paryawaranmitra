<?php

namespace App\Http\Controllers;

use App\Services\Search\GlobalSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request, GlobalSearch $search): View
    {
        $term = trim((string) $request->query('q', ''));
        $term = mb_substr($term, 0, 100);

        $results = mb_strlen($term) >= 2
            ? $search->search($request->user(), $term)
            : new Collection;

        return view('search.index', [
            'term' => $term,
            'results' => $results,
        ]);
    }
}
