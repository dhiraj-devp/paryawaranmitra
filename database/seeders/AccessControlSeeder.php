<?php

namespace Database\Seeders;

use App\Services\Access\AccessCatalog;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(AccessCatalog::class)->sync();
    }
}
