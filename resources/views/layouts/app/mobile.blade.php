<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
    @stack('styles')
</head>

<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-900">

    @livewire('components.mobile.navbar', ['title' => $title ?? 'Mapping'])

    {{-- Main Content Area --}}
    <main class="mx-auto w-full max-w-7xl flex-1 p-4 md:p-8">
        {{ $slot }}
    </main>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
