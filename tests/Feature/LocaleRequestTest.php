<?php

it('shares the regional locale selected from the browser language', function () {
    $this->withHeaders(['Accept-Language' => 'fr-CA,fr;q=0.9,en;q=0.8'])
        ->get('http://localhost/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'fr-CA'));
});

it('shares the saved locale preference ahead of the browser language', function () {
    $this->withSession(['locale' => 'es_419'])
        ->withHeaders(['Accept-Language' => 'fr-CA,fr;q=0.9,en;q=0.8'])
        ->get('http://localhost/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'es-419'));
});
