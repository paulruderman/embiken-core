<?php

namespace Database\Factories;

use App\Models\EarlyAccessInvitation;
use App\Models\EarlyAccessSignup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EarlyAccessInvitation>
 */
class EarlyAccessInvitationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'early_access_signup_id' => EarlyAccessSignup::factory(),
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'expires_at' => now()->addDays(14),
        ];
    }
}
