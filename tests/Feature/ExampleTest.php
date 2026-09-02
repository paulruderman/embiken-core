<?php

test('the central host sends operators to platform filament', function () {
    $this->get('/')->assertRedirect('/platform');
});
