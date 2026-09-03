<?php

use App\Http\Middleware\InitializeTenancyByDomainIfTenantHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;

test('a central host does not initialize tenancy for livewire', function () {
    $request = Request::create('http://localhost/livewire/update', 'POST');

    app(InitializeTenancyByDomainIfTenantHost::class)->handle($request, function () {
        expect(tenancy()->initialized)->toBeFalse()
            ->and(Schema::hasTable('staff'))->toBeFalse();

        return response('ok');
    });
});

test('a tenant host identifies the shop before livewire runs', function () {
    $request = Request::create('http://demo.localhost/livewire/update', 'POST');

    expect(fn () => app(InitializeTenancyByDomainIfTenantHost::class)->handle(
        $request,
        fn () => response('ok'),
    ))->toThrow(TenantCouldNotBeIdentifiedException::class);
});
