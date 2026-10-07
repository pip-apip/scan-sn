<x-layouts::app.mobile :title="$title ?? null">
    <flux:main class="py-0 lg:py-0">
        {{ $slot }}
    </flux:main>
</x-layouts::app.mobile>
