<style>
    :root {
        @foreach ($palettes as $name => $palette)
            @foreach ($palette as $shade => $color)
                --tenant-brand-{{ $name }}-{{ $shade }}: {{ $color }};
            @endforeach
        @endforeach

        @foreach ($palettes['primary'] as $shade => $color)
            --primary-{{ $shade }}: {{ $color }};
        @endforeach

        @foreach ($palettes['gray'] as $shade => $color)
            --gray-{{ $shade }}: {{ $color }};
        @endforeach
    }
</style>
