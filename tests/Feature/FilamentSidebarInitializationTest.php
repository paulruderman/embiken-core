<?php

use Symfony\Component\Process\Process;

it('initializes sidebar state before Filament scripts on the sign-in page', function () {
    $response = $this->withoutVite()->get('http://127.0.0.1/platform/login');

    $response->assertSee('data-filament-sidebar-state', false);
    $html = $response->getContent();

    expect(strpos($html, 'data-filament-sidebar-state'))
        ->toBeLessThan(strpos($html, '/js/filament/filament/app.js'));
});

it('keeps the installed Filament sidebar usable with saved state', function (?string $stored, string $expected, bool $staleStore = false) {
    $html = view('filament.sidebar-state')->render();
    $script = preg_replace('/<\/?script[^>]*>/', '', $html);
    $process = new Process(['node', '-e', <<<'JS'
        const vm = require('node:vm');
        const fs = require('node:fs');
        const input = JSON.parse(fs.readFileSync(0, 'utf8'));
        let stored = input.stored;
        const localStorage = {
            getItem: () => stored,
            setItem: (key, value) => { stored = value; },
        };
        const listeners = new Map();
        let sidebar;
        const context = {
            module: { exports: {} },
            window: { Alpine: {
                store: () => sidebar,
                $persist: (initial) => ({
                    as: () => input.staleStore || stored === null ? initial : JSON.parse(stored),
                }),
            } },
        };
        vm.runInNewContext(input.script, {
            localStorage,
            window: context.window,
            document: { addEventListener: (event, listener) => listeners.set(event, listener) },
        });
        vm.runInNewContext(input.sidebar.replace('export default', 'module.exports ='), context);
        sidebar = context.module.exports();
        listeners.get('alpine:initializing')();
        const groups = Array.from(sidebar.collapsedGroups);
        const before = sidebar.groupIsCollapsed('Weather');
        sidebar.toggleCollapsedGroup('Weather');
        const after = sidebar.groupIsCollapsed('Weather');
        sidebar.toggleCollapsedGroup('Weather');
        process.stdout.write(JSON.stringify({ stored, groups, before, after, restored: sidebar.groupIsCollapsed('Weather') }));
        JS]);
    $process->setInput(json_encode([
        'stored' => $stored,
        'staleStore' => $staleStore,
        'script' => $script,
        'sidebar' => file_get_contents(base_path('vendor/filament/filament/resources/js/stores/sidebar.js')),
    ], JSON_THROW_ON_ERROR));

    $process->mustRun();

    expect(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR))->toBe([
        'stored' => $expected,
        'groups' => json_decode($expected, true, flags: JSON_THROW_ON_ERROR),
        'before' => false,
        'after' => true,
        'restored' => false,
    ]);
})->with([
    'first visit' => [null, '[]'],
    'null' => ['null', '[]'],
    'string null' => ['"null"', '[]'],
    'malformed JSON' => ['{', '[]'],
    'object' => ['{}', '[]'],
    'saved groups' => ['["Catalog"]', '["Catalog"]'],
    'empty groups' => ['[]', '[]'],
    'already initialized null store' => ['["Catalog"]', '["Catalog"]', true],
]);
