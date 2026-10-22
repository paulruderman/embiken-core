<?php

use Embiken\Weather\Support\NominatimPlaceLabel;

test('a nominatim reverse payload labels a us city and state', function () {
    $label = NominatimPlaceLabel::fromNominatim([
        'name' => '123',
        'display_name' => '123 Main St, Burlington, Chittenden County, Vermont, United States',
        'address' => [
            'house_number' => '123',
            'road' => 'Main Street',
            'city' => 'Burlington',
            'state' => 'Vermont',
            'country' => 'United States',
        ],
    ]);

    expect($label)->toBe('Burlington, Vermont');
});

test('a nominatim reverse payload labels a town when city is absent', function () {
    $label = NominatimPlaceLabel::fromNominatim([
        'address' => [
            'town' => 'Middlebury',
            'state' => 'Vermont',
        ],
    ]);

    expect($label)->toBe('Middlebury, Vermont');
});

test('a nominatim reverse payload falls back to county and state', function () {
    $label = NominatimPlaceLabel::fromNominatim([
        'address' => [
            'county' => 'Chittenden County',
            'state' => 'Vermont',
            'country' => 'United States',
        ],
    ]);

    expect($label)->toBe('Chittenden County, Vermont');
});

test('a nominatim reverse payload uses country when region is missing', function () {
    $label = NominatimPlaceLabel::fromNominatim([
        'address' => [
            'city' => 'Reykjavík',
            'country' => 'Iceland',
        ],
    ]);

    expect($label)->toBe('Reykjavík, Iceland');
});

test('a nominatim reverse payload without address details is blank', function () {
    expect(NominatimPlaceLabel::fromNominatim(['name' => '123']))->toBe('');
});
