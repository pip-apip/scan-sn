<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Region;
use App\Models\Guest_User;
use App\Events\UserLocationUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('layouts.app-mobile')] class extends Component {
    public $search = '';

    // Properti untuk autentikasi Guest
    public ?string $guestUuid = null;
    public bool $isChecking = true; // Status loading saat mengecek localStorage
    public string $guestName = '';
    public $existingUsers = [];
    public bool $showConfirmModal = false;

    // 1. Fungsi ini dipanggil oleh Alpine.js saat halaman dimuat
    public function verifyUuid($uuid)
    {
        logger()
            ->channel('stderr')
            ->info('verifyUuid called with UUID: ' . ($uuid ?? 'null'));
        try {
            if ($uuid) {
                logger()->channel('stderr')->info('Searching for user with UUID...');
                $user = Guest_User::where('uuid', $uuid)->first();
                if ($user) {
                    logger()->channel('stderr')->info('User found. Updating status.');
                    $this->guestUuid = $uuid;
                    session()->put('guest_uuid', $uuid);
                    // Update status menjadi idle/standby karena sedang berada di menu utama
                    $user->update([
                        'current_location' => 'idle',
                        'last_seen_at' => now(),
                    ]);
                    $this->dispatch('$refresh');
                }
            } else {
                logger()->channel('stderr')->info('UUID is null. Skipping user search.');
            }
        } catch (\Throwable $e) {
            // Log the error for debugging purposes
            logger()
                ->channel('stderr')
                ->error('Error in verifyUuid: ' . $e->getMessage());
            \Log::error('Error in verifyUuid: ' . $e->getMessage());
        } finally {
            $this->isChecking = false;
            logger()->channel('stderr')->info('isChecking set to false.');
        }
    }

    // 2. Fungsi saat user submit nama
    public function submitName()
    {
        $this->validate([
            'guestName' => 'required|min:3|max:50',
        ]);

        // Cek apakah ada user dengan nama yang sama di DB
        $users = Guest_User::where('name', $this->guestName)->get();

        if ($users->count() > 0) {
            $this->existingUsers = $users;
            $this->showConfirmModal = true;
        } else {
            $this->registerNewUser();
        }
    }

    // 3. Fungsi membuat identitas (UUID) baru
    public function registerNewUser()
    {
        $uuid = Str::uuid()->toString();

        Guest_User::create([
            'uuid' => $uuid,
            'name' => $this->guestName,
            'current_location' => 'idle',
            'last_seen_at' => now(),
        ]);

        $this->guestUuid = $uuid;
        session()->put('guest_uuid', $uuid);
        $this->showConfirmModal = false;

        // Kirim event ke browser untuk menyimpan UUID di localStorage
        $this->dispatch('save-uuid', uuid: $uuid);
    }

    // 4. Fungsi jika user mengklaim bahwa akun lama adalah dirinya
    public function claimIdentity($uuid)
    {
        $this->guestUuid = $uuid;
        session()->put('guest_uuid', $uuid);

        Guest_User::where('uuid', $uuid)->update([
            'last_seen_at' => now(),
            'current_location' => 'idle',
        ]);

        $this->showConfirmModal = false;
        $this->dispatch('save-uuid', uuid: $uuid);
    }

    // 5. Fungsi heartbeat untuk menjaga status online (Dipanggil otomatis tiap 30 detik)
    public function keepAlive()
    {
        if ($this->guestUuid) {
            Guest_User::where('uuid', $this->guestUuid)->update([
                'last_seen_at' => now(),
            ]);
        }
    }

    public function getRegionsProperty()
    {
        return Region::query()
            ->select('category_region', DB::raw('COUNT(*) as total'))
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('category_region', 'like', "%{$search}%")->orWhereIn('category_region', Region::select('category_region')->where('name_region', 'like', "%{$search}%"));
                });
            })
            ->groupBy('category_region')
            ->get();
    }

    public function getActiveUsersProperty()
    {
        // 1. Ambil user yang aktif dalam 1 menit terakhir
        $users = Guest_User::where('last_seen_at', '>=', now()->subMinutes(1))
            ->whereNotIn('current_location', ['idle', 'offline'])
            ->get();

        // 2. Buat mapping (Peta) dari name_region ke category_region
        $regionMap = Region::pluck('category_region', 'name_region')->toArray();

        $activeMap = [];
        foreach ($users as $user) {
            $loc = $user->current_location;
            $category = $regionMap[$loc] ?? $loc;

            if (!isset($activeMap[$category])) {
                $activeMap[$category] = [];
            }

            $activeMap[$category][] = $user->name;
        }

        return $activeMap;
    }

    public function selectRegion($categoryRegion)
    {
        if ($this->guestUuid) {
            Guest_User::where('uuid', $this->guestUuid)->update([
                'last_seen_at' => now(),
            ]);
            event(new UserLocationUpdated());
        }

        return redirect()->route('user.sub-region', [
            'categoryRegion' => $categoryRegion,
        ]);
    }
}; ?>

<div x-data="{
    init() {
        Echo.channel('scanner-room')
            .listen('.UserLocationUpdated', (e) => {
                $wire.$refresh();
            });
    }
}" x-init="$wire.verifyUuid(localStorage.getItem('guest_uuid') || '')" @save-uuid.window="localStorage.setItem('guest_uuid', $event.detail.uuid);"
    class="space-y-4">

    {{-- LAYAR LOADING SAAT CEK LOCALSTORAGE --}}
    @if ($isChecking)
        <div class="flex min-h-[50vh] flex-col items-center justify-center space-y-4">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600"></div>
            <p class="text-sm font-medium text-zinc-500">Memeriksa akses...</p>
        </div>

        {{-- FORM INPUT NAMA JIKA UUID TIDAK ADA --}}
    @elseif (!$guestUuid)
        <div class="flex min-h-[70vh] items-center justify-center px-4">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-8 shadow-sm">

                @if (!$showConfirmModal)
                    {{-- Form Default --}}
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-zinc-800">Selamat Datang</h2>
                        <p class="mt-2 text-sm text-zinc-500">Silakan masukkan nama Anda untuk mulai melakukan scan.</p>
                    </div>

                    <form wire:submit.prevent="submitName" class="mt-6 space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-zinc-700">Nama Panggilan</label>
                            <input type="text" id="name" wire:model="guestName" placeholder="Contoh: Budi"
                                class="mt-1 block w-full rounded-lg border border-zinc-300 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                required autofocus>
                            @error('guestName')
                                <span class="text-xs text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit"
                            class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            Mulai Scan
                        </button>
                    </form>
                @else
                    {{-- Modal Konfirmasi jika Nama Sudah Ada --}}
                    <div class="text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h2 class="mt-4 text-xl font-bold text-zinc-800">Nama Sudah Digunakan</h2>
                        <p class="mt-2 text-sm text-zinc-500">
                            Kami menemukan nama <span class="font-bold text-zinc-800">"{{ $guestName }}"</span> di
                            sistem kami. Apakah salah satu dari daftar di bawah ini adalah Anda sebelumnya?
                        </p>
                    </div>

                    <div class="mt-6 space-y-3">
                        @foreach ($existingUsers as $user)
                            <div
                                class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-800">{{ $user->name }}</p>
                                    <p class="text-xs text-zinc-500">Last seen:
                                        {{ $user->last_seen_at?->diffForHumans() ?? 'Belum pernah login' }}</p>
                                    <p class="text-xs text-zinc-500">Total Scan: {{ $user->total_scans }}</p>
                                </div>
                                <button wire:click="claimIdentity('{{ $user->uuid }}')"
                                    class="rounded bg-indigo-100 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-200 cursor-pointer">
                                    Ya, ini saya
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 border-t border-zinc-200 pt-6">
                        <p class="text-center text-sm text-zinc-500 mb-3">Bukan salah satu dari di atas?</p>
                        <button wire:click="registerNewUser"
                            class="w-full rounded-lg bg-zinc-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-900">
                            Buat Profil Baru "{{ $guestName }}"
                        </button>
                        <button wire:click="$set('showConfirmModal', false)"
                            class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50">
                            Batal & Ganti Nama
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- HALAMAN UTAMA (TAMPIL JIKA SUDAH ADA UUID) --}}
    @else
        {{-- Heartbeat ping ke server tiap 30 detik agar status last_seen_at terus update --}}
        <div wire:poll.30s="keepAlive"></div>

        <div class="flex gap-4">
            <h1 class="text-xl font-semibold text-zinc-700">
                {{ __('Regions') }}
            </h1>
        </div>

        <div x-data="{
            regionSection: true,
            subRegionSection: false,
            scanItemSection: false,
        }">
            <div x-show="regionSection" class="space-y-4">
                <div class="flex w-full items-center justify-between">
                    <div class="flex items-center">
                        <input type="text" placeholder="Search Regions..." wire:model.live.debounce.300ms="search"
                            class="w-68 rounded-lg border border-zinc-300 px-4 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                    </div>
                    <span class="ml-2 text-xs text-zinc-500">
                        {{ $this->regions->count() }} result(s)
                    </span>
                </div>
                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
                    {{-- Skeleton Loading --}}
                    <div wire:loading wire:target="search" class="w-full overflow-x-auto">
                        <table class="min-w-full w-full">
                            <thead class="bg-zinc-50">
                                <tr class="border-b border-zinc-200">
                                    <th class="w-16 px-6 py-4 text-left text-xs font-semibold uppercase text-zinc-500">
                                        No</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-zinc-500">Region
                                        Name</th>
                                    <th
                                        class="w-44 px-6 py-4 text-center text-xs font-semibold uppercase text-zinc-500">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @for ($i = 0; $i < 5; $i++)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="h-4 w-8 animate-pulse rounded bg-zinc-200"></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="h-4 w-48 animate-pulse rounded bg-zinc-200"></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="mx-auto h-6 w-20 animate-pulse rounded-full bg-zinc-200"></div>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    {{-- Aktual Data --}}
                    <div wire:loading.remove wire:target="search" class="overflow-x-auto">
                        <div class="overflow-x-auto">
                            <table class="min-w-full w-full">
                                <thead class="bg-zinc-50">
                                    <tr class="border-b border-zinc-200">
                                        <th
                                            class="w-16 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                            No
                                        </th>
                                        <th
                                            class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                            Region Name
                                        </th>
                                        <th
                                            class="w-44 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100">
                                    @if ($this->regions->isEmpty())
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-center text-sm text-zinc-500">
                                                No regions found.
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($this->regions as $region)
                                            <tr class="hover:bg-zinc-50 cursor-pointer"
                                                wire:click="selectRegion('{{ $region->category_region }}')">
                                                <td class="px-6 py-4 text-sm">
                                                    {{ $loop->index + 1 }}
                                                </td>
                                                <td class="flex flex-row gap-2 px-6 py-4 text-sm whitespace-nowrap">
                                                    <span
                                                        class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600/50 text-xs font-bold text-white">
                                                        {{ $region->total }}
                                                    </span>
                                                    <span>
                                                        {{ $region->category_region }}
                                                    </span>

                                                </td>
                                                <td class="px-6 py-4 text-sm text-center">
                                                    @php
                                                        $workers = $this->activeUsers[$region->category_region] ?? [];
                                                    @endphp

                                                    @if (count($workers) > 0)
                                                        <div class="flex flex-wrap items-center justify-center gap-1">
                                                            <span
                                                                class="flex flex-col items-center gap-1 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                                @foreach ($workers as $worker)
                                                                    <div class="flex items-center gap-1">
                                                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                                                        {{ $worker }}
                                                                    </div>
                                                                @endforeach
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">
                                                            <span
                                                                class="h-2 w-2 rounded-full bg-slate-400 animate-pulse"></span>
                                                            Available
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
