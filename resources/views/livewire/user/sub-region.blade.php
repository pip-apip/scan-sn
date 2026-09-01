<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Events\UserLocationUpdated;
use App\Models\Guest_User; // Import model Guest_User

new #[Layout('layouts.app-mobile')] class extends Component {
    public string $categoryRegion;
    public string $search = '';

    public function mount($categoryRegion)
    {
        $this->categoryRegion = $categoryRegion;

        // Update lokasi user menandakan sedang berada di dalam Category Region tertentu
        $guestUuid = session('guest_uuid');
        if (!$guestUuid) {
            return redirect()->route('user.index');
        }

        Guest_User::where('uuid', $guestUuid)->update([
            'current_location' => $categoryRegion,
            'last_seen_at' => now(),
        ]);
        event(new UserLocationUpdated());
    }

    // Fungsi Heartbeat untuk halaman Sub-Region
    public function keepAlive()
    {
        $guestUuid = session('guest_uuid');
        if ($guestUuid) {
            Guest_User::where('uuid', $guestUuid)->update([
                'last_seen_at' => now(),
            ]);
        }
    }

    public function getRegionsProperty()
    {
        return \App\Models\Region::query()
            ->where('category_region', $this->categoryRegion)
            ->when($this->search, function ($query) {
                $query->where('name_region', 'like', "%{$this->search}%");
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function backToRegion()
    {
        // Set kembali menjadi idle saat kembali ke halaman Index (Regions)
        $guestUuid = session('guest_uuid');
        if ($guestUuid) {
            Guest_User::where('uuid', $guestUuid)->update([
                'current_location' => 'idle',
                'last_seen_at' => now(),
            ]);
        }
        event(new UserLocationUpdated());

        return redirect()->route('user.index');
    }

    public function getActiveUsersProperty()
    {
        // 1. Ambil user yang aktif dalam 1 menit terakhir
        $users = Guest_User::where('last_seen_at', '>=', now()->subMinutes(1))
            ->whereNotIn('current_location', ['idle', 'offline'])
            ->get();

        $activeMap = [];
        foreach ($users as $user) {
            if (!isset($activeMap[$user->current_location])) {
                $activeMap[$user->current_location] = [];
            }
            // Kelompokkan user ke sub-region tempat ia berada
            $activeMap[$user->current_location][] = $user->name;
        }

        return $activeMap;
    }

    #[On('echo:scanner-room,.UserLocationUpdated')]
    public function refreshActiveUsers() {}

    public function selectRegion($region)
    {
        // Update lokasi menjadi nama sub-region sebelum pindah ke halaman Scan Item
        $guestUuid = session('guest_uuid');
        if ($guestUuid) {
            Guest_User::where('uuid', $guestUuid)->update([
                'current_location' => $region,
                'last_seen_at' => now(),
            ]);
        }
        event(new UserLocationUpdated());

        return redirect()->route('user.scan-item', [
            'categoryRegion' => $this->categoryRegion,
            'subRegion' => $region,
        ]);
    }
}; ?>

<div x-data="{
    init() {
        Echo.channel('scanner-room')
            .listen('.UserLocationUpdated', (e) => {
                console.log('🔄 Refreshing regions...');
                $wire.$refresh();
            });
    }
}" class="space-y-4">
    {{-- Trigger otomatis update status user setiap 30 detik --}}
    <div wire:poll.30s="keepAlive"></div>

    <div class="flex gap-2">
        <h1 class="text-lg font-semibold text-zinc-700/50 hover:text-zinc-700 cursor-pointer" wire:click="backToRegion()">
            {{ __('Regions') }}
        </h1>
        <span class="text-lg font-semibold text-zinc-700/50">
            /
        </span>
        <h1 class="text-xl font-semibold text-zinc-700">
            {{ $this->categoryRegion }}
        </h1>
    </div>

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
        <div wire:loading.remove wire:target="search" class="overflow-x-auto">
            <div class="overflow-x-auto">
                <table class="min-w-full w-full">
                    <thead class="bg-zinc-50">
                        <tr class="border-b border-zinc-200">
                            <th
                                class="w-16 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                No
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
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
                                    wire:click="selectRegion('{{ $region->name_region }}')">
                                    <td class="px-6 py-4 text-sm">
                                        {{ $loop->index + 1 }}
                                    </td>
                                    <td class="flex flex-row gap-2 px-6 py-4 text-sm whitespace-nowrap">
                                        <span
                                            class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600/50 text-xs font-bold text-white">
                                            {{ $region->total }}
                                        </span>
                                        <span>
                                            {{ $region->name_region }}
                                        </span>

                                    </td>
                                    <td class="px-6 py-4 text-sm text-center">
                                        @php
                                            $workers = $this->activeUsers[$region->name_region] ?? [];
                                        @endphp

                                        @if (count($workers) > 0)
                                            <div class="flex flex-wrap items-center justify-center gap-1">
                                                @foreach ($workers as $worker)
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                        <span
                                                            class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                                        {{ $worker }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">
                                                <span class="h-2 w-2 rounded-full bg-slate-400 animate-pulse"></span>
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
