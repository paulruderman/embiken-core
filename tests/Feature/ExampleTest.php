<?php

test('the central host serves the marketing home page', function () {
    $this->withoutVite()
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Marketing/Home'));
});
