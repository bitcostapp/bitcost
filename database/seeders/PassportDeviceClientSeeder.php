<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\Passport;

class PassportDeviceClientSeeder extends Seeder
{
    /**
     * The fixed public device-authorization client used by the Bitcost CLI.
     *
     * Pinned so `migrate:fresh --seed` always recreates the same client id the
     * CLI ships with (see BITCOST_CLIENT_ID in dialog-bitcost-login.tsx).
     */
    public const CLIENT_ID = '019ec10f-3871-7361-90e8-2b7cfb38dbf7';

    public function run(): void
    {
        Passport::client()->newQuery()->updateOrCreate(
            ['id' => self::CLIENT_ID],
            [
                'name' => 'Bitcost CLI',
                'secret' => null, // public client — a distributed CLI cannot keep a secret
                'provider' => null,
                'redirect_uris' => [],
                'grant_types' => ['urn:ietf:params:oauth:grant-type:device_code', 'refresh_token'],
                'revoked' => false,
            ],
        );
    }
}
