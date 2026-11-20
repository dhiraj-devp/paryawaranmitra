<?php

namespace App\Support;

class LikeTerm
{
    public static function contains(string $value): string
    {
        return '%'.addcslashes($value, '%_\\').'%';
    }
}
