<?php

use App\Actions\Staff\InviteStaffAction;
use App\Enums\StaffRole;
use App\Models\Location;
use App\Models\Tenant;
use App\Notifications\SetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

test('inviting staff stores a password reset token on the tenant database', function () {
    Notification::fake();

    $tenant = Tenant::create([
        'name' => 'Invite Shop',
        'charges_enabled' => false,
        'transfers_active' => false,
    ]);

    $path = database_path($tenant->database()->getName());

    try {
        $tenant->run(function (): void {
            expect(Schema::hasTable('password_reset_tokens'))->toBeTrue()
                ->and(Schema::hasTable('sessions'))->toBeTrue();

            $location = Location::factory()->create();

            $staff = app(InviteStaffAction::class)(
                location: $location,
                name: 'Test Example',
                email: 'test@example.com',
                role: StaffRole::Manager,
            );

            expect($staff->email)->toBe('test@example.com')
                ->and($staff->password)->toBeNull();

            $this->assertDatabaseHas('password_reset_tokens', [
                'email' => 'test@example.com',
            ]);

            Notification::assertSentTo($staff, SetPasswordNotification::class);
        });
    } finally {
        tenancy()->end();

        if (is_file($path)) {
            unlink($path);
        }
    }
});
