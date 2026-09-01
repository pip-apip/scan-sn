<?php
use Livewire\Volt\Component;
use App\Models\Guest_User; // Sesuaikan jika nama model Anda GuestUser (tanpa underscore)

new class extends Component {
    public ?string $userName = null;

    public function mount()
    {
        // Ambil UUID dari Session saat layout dimuat
        $uuid = session('guest_uuid');
        if ($uuid) {
            $user = Guest_User::where('uuid', $uuid)->first();
            $this->userName = $user?->name;
        }
    }

    public function logout()
    {
        $uuid = session('guest_uuid');
        if ($uuid) {
            // Update status menjadi offline sebelum logout
            Guest_User::where('uuid', $uuid)->update([
                'current_location' => 'offline',
                'last_seen_at' => now(),
            ]);
        }

        // 1. Hapus memori di sisi Server (Session)
        session()->forget('guest_uuid');

        // 2. Kirim perintah ke Javascript untuk menghapus localStorage dan Pindah Halaman
        $this->dispatch('execute-logout');
    }
};
?>
<header x-data
    @execute-logout.window="
            localStorage.removeItem('guest_uuid');
            window.location.href = '{{ route('user.index') }}';
        "
    class="sticky top-0 z-20 flex items-center justify-between border-b border-indigo-100 bg-white px-4 py-4 shadow-sm transition-all md:px-8 md:py-5 dark:border-indigo-900/50 dark:bg-slate-800">

    {{-- Bagian Kiri: Tombol Back & Judul --}}
    <div class="flex flex-1 items-center gap-3 overflow-hidden md:gap-5">
        <button type="button"
            class="text-slate-500 transition hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400">
            {{-- Ikon Arrow Left --}}
            {{-- <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg> --}}
        </button>

        {{-- Judul Halaman --}}
        <h1 class="truncate text-2xl font-black tracking-tight text-indigo-700 md:text-3xl dark:text-indigo-400">
            {{ $title ?? 'Mapping' }}
        </h1>
    </div>

    {{-- Bagian Kanan: Ikon-ikon --}}
    <div class="flex items-center gap-4 text-indigo-900/60 md:gap-6 dark:text-indigo-300">
        <button type="button" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">
            {{-- Ikon Vibrate/Perangkat --}}
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="h-5 w-5 md:h-6 md:w-6">
                <path d="M4 8v8" />
                <path d="M20 8v8" />
                <rect width="8" height="14" x="8" y="5" rx="1" />
            </svg>
        </button>
        <button type="button" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">
            {{-- Ikon Volume --}}
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="h-5 w-5 md:h-6 md:w-6">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" />
                <path d="M15.54 8.46a5 5 0 0 1 0 7.07" />
                <path d="M19.07 4.93a10 10 0 0 1 0 14.14" />
            </svg>
        </button>

        {{-- Dropdown User Profile --}}
        <div x-data="{ open: false }" class="relative">
            {{-- Tombol Ikon User --}}
            <button @click="open = !open" @click.outside="open = false" type="button"
                class="flex items-center transition hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none cursor-pointer"
                :class="{ 'text-indigo-600 dark:text-indigo-400': open }">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="h-5 w-5 md:h-6 md:w-6">
                    <circle cx="12" cy="12" r="10" />
                    <circle cx="12" cy="10" r="3" />
                    <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662" />
                </svg>
            </button>

            {{-- Menu Dropdown --}}
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95" x-cloak
                class="absolute right-0 mt-3 w-48 origin-top-right rounded-xl border border-zinc-200 bg-white py-1 shadow-lg outline-none dark:border-zinc-700 dark:bg-slate-800">
                @if ($userName)
                    {{-- Info Nama (Bukan tombol) --}}
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-700">
                        {{-- <p class="text-xs text-zinc-500 dark:text-zinc-400">Masuk sebagai</p> --}}
                        <p class="truncate text-sm font-bold text-zinc-800 dark:text-zinc-100">
                            {{ $userName }}
                        </p>
                    </div>

                    {{-- Tombol Logout --}}
                    <button wire:click="logout" wire:confirm="Yakin ingin mengganti nama/akun scan?"
                        class="flex w-full items-center px-4 py-2.5 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10 cursor-pointer">
                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                        Ganti Akun
                    </button>
                @else
                    {{-- Jika tidak ada session --}}
                    <div class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                        Belum ada nama (Guest)
                    </div>
                @endif
            </div>
        </div>
    </div>
</header>
