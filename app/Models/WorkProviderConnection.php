<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A placeholder connection to an external Work Provider (GitHub/Jira).
 * "Connected" means `connected_at` is set; disconnecting clears it.
 *
 * @property int $id
 * @property string $provider
 * @property Carbon|null $connected_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['provider', 'connected_at'])]
class WorkProviderConnection extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
        ];
    }

    /**
     * Whether this provider is currently connected.
     */
    public function isConnected(): bool
    {
        return $this->connected_at !== null;
    }
}
