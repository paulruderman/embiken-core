<?php

namespace App\Models;

use App\Enums\StaffRole;
use Database\Factories\StaffFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property StaffRole $role
 * @property bool $is_platform_manager
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['location_id', 'name', 'email', 'password', 'role', 'is_platform_manager'])]
#[Hidden(['password', 'remember_token'])]
class Staff extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'shop') {
            return false;
        }

        if ($this->role !== StaffRole::Manager) {
            return false;
        }

        if ($this->is_platform_manager) {
            return true;
        }

        if (tenant()?->isSuspended()) {
            return false;
        }

        return $this->password !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => StaffRole::class,
            'is_platform_manager' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
