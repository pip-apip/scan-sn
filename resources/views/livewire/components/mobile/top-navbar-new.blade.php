<?php

use Livewire\Volt\Component;
use App\Models\Guest_User;

new class extends Component {
    public ?string $userName = null;

    public function mount()
    {
        $this->loadUser();
    }

    #[\Livewire\Attributes\On('user-checked-in')]
    public function refreshUser($uuid = null)
    {
        $this->loadUser($uuid);
    }

    private function loadUser($uuid = null)
    {
        $uuid ??= session('guest_uuid');

        if (!$uuid) {
            $this->userName = null;
            return;
        }

        $user = Guest_User::where('uuid', $uuid)->first();

        $this->userName = $user?->name;
    }

    public function logout()
    {
        $uuid = session('guest_uuid');

        if ($uuid) {
            Guest_User::where('uuid', $uuid)->update([
                'current_location' => 'offline',
                'last_seen_at' => now(),
            ]);
        }

        session()->forget('guest_uuid');

        $this->userName = null;

        $this->dispatch('execute-logout');
    }
};
?>

<header x-data
    @execute-logout.window="
        localStorage.removeItem('guest_uuid');
        window.location.href = '{{ route('user.index') }}';
    "
    class="sticky top-0 z-20 flex flex-col">

    {{-- Main Header --}}
    <div
        class="w-full border-b border-indigo-100 bg-white shadow-sm transition-all dark:border-indigo-900/50 dark:bg-slate-800">

        <div
            class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:gap-4 md:px-8 md:py-4">

            <div class="flex min-w-0 flex-1 items-center gap-3 md:gap-5">

                <button type="button"
                    class="shrink-0 rounded-xl bg-[#E3DFFF] p-2 text-[#5148D7] transition hover:bg-[#d6d1ff]">
                    <flux:icon.archive-box class="h-5 w-5 md:h-6 md:w-6" />
                </button>

                <div class="flex min-w-0 flex-col font-grotesk">
                    <div class="flex min-w-0 items-center gap-2">
                        <h1
                            class="truncate text-lg font-black tracking-tight text-indigo-700 sm:text-xl md:text-2xl dark:text-indigo-400">
                            Mapping Scanner
                        </h1>
                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-[#68DBA9] bg-[#BEFFDC] px-2 py-0.5 text-[10px] font-bold tracking-wide text-[#25A475] sm:text-xs md:gap-2 md:px-3 md:py-1">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#25A475] md:h-2 md:w-2"></span>
                            Online
                        </span>
                    </div>
                    <span class="truncate text-xs tracking-tight text-slate-500/70 sm:text-sm dark:text-indigo-400">
                        Paket N1
                    </span>
                </div>
            </div>

            {{-- User Dropdown --}}
            <div x-data="{ open: false }" class="relative shrink-0">
                <button @click="open = !open" @click.outside="open = false" type="button"
                    class="flex cursor-pointer items-center justify-center rounded-2xl bg-[#4338ca] p-2.5 text-white transition hover:bg-[#5148D7] md:rounded-3xl md:p-3">
                    <flux:icon.user class="h-5 w-5 md:h-6 md:w-6" />
                </button>
                <div x-show="open" x-transition x-cloak
                    class="absolute right-0 mt-3 w-48 origin-top-right rounded-xl border border-zinc-200 bg-white py-1 shadow-lg outline-none md:w-56 dark:border-zinc-700 dark:bg-slate-800">

                    @if ($userName)
                        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-700">
                            <p class="truncate text-sm font-bold text-zinc-800 dark:text-zinc-100">
                                {{ $userName }}
                            </p>
                        </div>

                        {{-- Appearance Setting (Light / Dark Mode) --}}
                        <button type="button" role="switch" @click="$flux.dark = !$flux.dark"
                            :aria-checked="$flux.dark"
                            class="flex min-h-12 w-full cursor-pointer items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3 text-left transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/40">

                            {{-- <span class="flex items-center gap-2">
                                <flux:icon.sun x-show="!$flux.dark" class="h-4 w-4 text-[#4338ca] dark:text-indigo-300" />
                                <flux:icon.moon x-show="$flux.dark" x-cloak
                                    class="h-4 w-4 text-[#4338ca] dark:text-indigo-300" />
                                <span
                                    class="font-grotesk text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                    Mode Tampilan
                                </span>
                            </span> --}}

                            <span class="flex shrink-0 items-center gap-2 justify-between w-full">
                                <span class="text-[11px] font-bold tracking-wide text-slate-500 dark:text-indigo-300"
                                    x-text="$flux.dark ? 'Dark Mode' : 'Light Mode'"></span>

                                <span
                                    class="relative inline-flex h-8 w-15 shrink-0 items-center rounded-full p-0.5 shadow-[inset_0_1px_2px_rgba(15,23,42,0.08)] transition-colors duration-200"
                                    :class="$flux.dark ? 'bg-[#4338ca]' : 'bg-[#cbd5e1] dark:bg-slate-600'">

                                    <span
                                        class="relative z-10 flex h-7 w-7 items-center justify-center rounded-full bg-white text-[#4338ca] shadow-sm transition-transform duration-200 dark:bg-slate-900 dark:text-indigo-300"
                                        :class="$flux.dark ? 'translate-x-7' : 'translate-x-0'">
                                        <flux:icon.sun x-show="!$flux.dark" class="h-4 w-4" />
                                        <flux:icon.moon x-show="$flux.dark" x-cloak class="h-4 w-4" />
                                    </span>
                                </span>
                            </span>
                        </button>

                        <button wire:click="logout" wire:confirm="Yakin ingin mengganti nama/akun scan?"
                            class="flex w-full cursor-pointer items-center px-4 py-2.5 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                            <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                            Ganti Akun
                        </button>
                    @else
                        <div class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            Belum ada nama (Guest)
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- Reverb Status --}}
    <div x-data="{
        connected: false,
        latency: null,
        connection: null,
        pingInterval: null,
        echoCheckInterval: null,

        init() {
            this.waitForEcho();
        },

        waitForEcho() {
            if (window.Echo?.connector?.pusher?.connection) {
                this.setupConnection();
                return;
            }

            this.echoCheckInterval = setInterval(() => {
                if (window.Echo?.connector?.pusher?.connection) {
                    clearInterval(this.echoCheckInterval);
                    this.setupConnection();
                }
            }, 300);
        },

        setupConnection() {
            this.connection =
                window.Echo.connector.pusher.connection;

            this.connected =
                this.connection.state === 'connected';

            this.connection.bind('connected', () => {
                this.connected = true;
                this.measureLatency();
            });

            this.connection.bind('disconnected', () => {
                this.connected = false;
                this.latency = null;
            });

            this.connection.bind('error', () => {
                this.connected = false;
                this.latency = null;
            });

            this.connection.bind('state_change', (states) => {
                this.connected = states.current === 'connected';

                if (!this.connected) {
                    this.latency = null;
                }
            });

            if (this.connected) {
                this.measureLatency();
            }

            this.pingInterval = setInterval(() => {
                if (this.connected) {
                    this.measureLatency();
                }
            }, 5000);
        },

        async measureLatency() {
            if (!this.connected) {
                return;
            }
            const start = performance.now();
            try {
                await fetch('{{ route('realtime.ping') }}', {
                    method: 'GET',
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                this.latency = Math.round(
                    performance.now() - start
                );
            } catch (error) {
                this.latency = null;
            }
        }
    }"
        class="w-full border-b bg-slate-100 inset-shadow-sm dark:bg-slate-800/60 dark:border-slate-700">

        <div
            class="mx-auto flex w-full max-w-7xl items-center gap-2 px-4 py-2 sm:gap-3 md:px-8">

            <span class="relative flex h-2.5 w-2.5 shrink-0 md:h-3 md:w-3" :class="connected ? '' : 'opacity-40'">
                <span x-show="connected"
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-60"></span>

                <span class="relative inline-flex h-full w-full rounded-full"
                    :class="connected ? 'bg-emerald-500' : 'bg-gray-400'"></span>
            </span>

            <div class="text-[10px] font-bold tracking-wide sm:text-xs"
                :class="connected ? 'text-emerald-800 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400'">
                <span x-show="connected" class="hidden sm:inline">REVERB REALTIME: CONNECTED
                    <span x-show="latency !== null" x-text="`(${latency}MS)`" class="ml-1"></span>
                </span>
                <span x-show="connected" class="sm:hidden">CONNECTED
                    <span x-show="latency !== null" x-text="`(${latency}MS)`" class="ml-1"></span>
                </span>
                <span x-show="!connected" class="hidden sm:inline">REVERB REALTIME: DISCONNECTED</span>
                <span x-show="!connected" class="sm:hidden">DISCONNECTED</span>
            </div>

        </div>

    </div>

</header>
