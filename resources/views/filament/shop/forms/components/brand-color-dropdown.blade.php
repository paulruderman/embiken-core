@php
    use App\Support\BrandColor;
    use Filament\Support\Colors\Color;

    $families = BrandColor::familyNames();
    $swatchShades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        class="relative"
        x-data="{ open: false }"
        x-on:keydown.escape.window="open = false"
        x-on:click.outside="open = false"
    >
        <x-filament::input.wrapper :valid="! $errors->has($getStatePath())">
            <x-filament::input
                :attributes="(new \Filament\Support\View\ComponentAttributeBag)->merge([
                    'id' => $getId(),
                    'type' => 'text',
                    'wire:model.live' => $getStatePath(),
                    'placeholder' => $getPlaceholder(),
                    'autocomplete' => 'off',
                    'maxlength' => $getMaxLength(),
                    'aria-haspopup' => 'listbox',
                    'aria-expanded' => 'false',
                    'x-bind:aria-expanded' => 'open.toString()',
                ], escape: false)->class(['pe-11'])"
            />
        </x-filament::input.wrapper>

        <button
            type="button"
            class="absolute end-2 top-1/2 z-10 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-gray-500 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 dark:text-gray-400 dark:hover:bg-white/5"
            aria-label="{{ __('app.branding_ui.choose_color_family') }}"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open.toString()"
        >
            <x-filament::icon icon="heroicon-m-chevron-down" class="size-4" />
        </button>

        <div
            x-cloak
            x-show="open"
            x-transition.origin.top
            class="absolute inset-x-0 z-50 mt-1 max-h-80 overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-xl dark:border-white/10 dark:bg-gray-900"
            role="listbox"
            aria-label="{{ __('app.branding_ui.color_families') }}"
        >
            @foreach ($families as $family)
                @php
                    $palette = BrandColor::palette($family, '#000000');
                @endphp

                <button
                    type="button"
                    role="option"
                    aria-label="{{ __('app.branding_ui.color_family_option', ['family' => $family]) }}"
                    wire:click="$set('{{ $getStatePath() }}', '{{ $family }}')"
                    x-on:click="open = false"
                    class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-start text-sm text-gray-800 hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none dark:text-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
                >
                    <span class="min-w-16 font-medium">{{ $family }}</span>

                    <span class="flex min-w-0 flex-1 gap-0.5" aria-hidden="true">
                        @foreach ($swatchShades as $shade)
                            <span
                                class="h-5 min-w-0 flex-1 first:rounded-s last:rounded-e"
                                title="{{ $family }}-{{ $shade }}"
                                style="background-color: {{ Color::convertToHex($palette[$shade]) }}"
                            ></span>
                        @endforeach
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
