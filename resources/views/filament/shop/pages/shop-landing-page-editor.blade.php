<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            {{ __('app.shop_content_ui.save_home_page') }}
        </x-filament::button>
    </form>
</x-filament-panels::page>
