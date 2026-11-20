<?php

namespace App\Services\Audit;

class AuditDiff
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function changed(array $before, array $after): array
    {
        $old = [];
        $new = [];

        foreach ($after as $key => $value) {
            $previous = $before[$key] ?? null;

            if ($previous !== $value) {
                $old[$key] = $previous;
                $new[$key] = $value;
            }
        }

        return [$old, $new];
    }
}
