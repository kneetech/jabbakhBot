<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        User::factory()->create([
            'name'        => 'MK',
            'telegram_id' => 107042339,
            'role'        => UserRole::Root
        ]);

        User::factory()->create([
            'name'        => 'Астарта',
            'telegram_id' => 194795532,
            'role'        => UserRole::Root
        ]);
    }
}
