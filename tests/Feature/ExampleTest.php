<?php

test('the home path redirects to the book surface', function () {
    $this->get(route('home'))
        ->assertRedirectToRoute('book');
});

test('the book surface renders', function () {
    $this->get(route('book'))
        ->assertSee('Book');
});
