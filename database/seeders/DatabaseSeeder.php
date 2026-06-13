<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Test login: test@example.com / Passw0rd!
        // The factory verifies the email and creates a personal team set as current.
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('Passw0rd!'),
            ]);
        }

        // A Department is a non-personal Team. The factory only gives the user a
        // personal team, so seed a real Department and make it their current team.
        // (Model events are muted here, so set the slug explicitly.)
        if (! $user->teams()->where('is_personal', false)->exists()) {
            $department = Team::create([
                'name' => 'Engineering',
                'slug' => 'engineering',
                'is_personal' => false,
            ]);

            $department->members()->attach($user, ['role' => TeamRole::Owner->value]);

            $user->switchTeam($department);
        }

        $this->call(PassportDeviceClientSeeder::class);
        $this->call(ModelPricingSeeder::class);
        $this->call(TaskSeeder::class);
    }
}
