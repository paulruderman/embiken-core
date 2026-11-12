<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\EarlyAccessInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $used_at
 */
class EarlyAccessInvitation extends Model
{
    /** @use HasFactory<EarlyAccessInvitationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'early_access_signup_id',
        'sent_by',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected $hidden = ['token_hash'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<EarlyAccessSignup, $this> */
    public function signup(): BelongsTo
    {
        return $this->belongsTo(EarlyAccessSignup::class, 'early_access_signup_id');
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
