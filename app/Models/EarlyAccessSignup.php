<?php

namespace App\Models;

use Database\Factories\EarlyAccessSignupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EarlyAccessSignup extends Model
{
    /** @use HasFactory<EarlyAccessSignupFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'email',
        'name',
        'shop_name',
        'message',
        'status',
        'locale',
        'internal_notes',
    ];

    /** @return HasMany<EarlyAccessInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(EarlyAccessInvitation::class);
    }
}
