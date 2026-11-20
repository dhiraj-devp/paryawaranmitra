<?php

namespace App\Services\Search;

readonly class SearchResult
{
    public function __construct(
        public string $type,
        public string $title,
        public ?string $subtitle,
        public string $url,
    ) {}
}
