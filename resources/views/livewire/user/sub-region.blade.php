<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Events\UserLocationUpdated;
use App\Models\Guest_User;
use App\Models\Region;

new #[Layout('layouts.app-mobile')] class extends Component {
    use WithPagination;

    public string $categoryRegion;

    public $selectedFilter = 'all';
    public $selectedCard = null;
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

    public function keepAlive()
    {
        $guestUuid = session('guest_uuid');
        if ($guestUuid) {
            Guest_User::where('uuid', $guestUuid)->update([
                'last_seen_at' => now(),
                'current_location' => $this->categoryRegion,
            ]);
        }
    }

    public function getRegionsProperty()
    {
        return Region::query()
            ->select([
                'regions.id',
                'regions.name_region',
                'regions.category_region',
                'regions.project_id',
                // 1. Menghitung total item yang terdaftar di project tersebut
                DB::raw('(SELECT COUNT(*) FROM items WHERE items.project_id = regions.project_id) as total_items'),
                // 2. Menghitung berapa item (item_id) yang sudah ter-scan/mapping untuk region ini
                DB::raw('COUNT(DISTINCT mapping_lists.item_id) as count_items_scanned'),
            ])
            ->leftJoin('mapping_lists', 'regions.id', '=', 'mapping_lists.region_id')
            ->where('regions.category_region', $this->categoryRegion)
            ->when($this->search, function ($query, $search) {
                $query->where('regions.name_region', 'like', "%{$search}%");
            })
            ->groupBy('regions.id', 'regions.name_region', 'regions.category_region', 'regions.project_id')
            ->orderBy('regions.id', 'asc')
            ->paginate(6);
    }

    public function getActiveUsersProperty()
    {
        // Ambil user yang aktif dalam 1 menit terakhir
        $users = Guest_User::where('last_seen_at', '>=', now()->subMinutes(1))
            ->whereNotIn('current_location', ['idle', 'offline'])
            ->get();

        // Buat mapping dari name_region ke category_region
        $regionMap = Region::pluck('category_region', 'name_region')->toArray();

        $activeMap = [];
        foreach ($users as $user) {
            $loc = $user->current_location;
            $category = $regionMap[$loc] ?? $loc;

            if (!isset($activeMap[$category])) {
                $activeMap[$category] = [];
            }

            $activeMap[$category][] = [
                'name' => $user->name,
                'location' => $loc,
            ];
        }

        return $activeMap;
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

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function selectFilter($filter)
    {
        $this->selectedFilter = $filter;
        $this->resetPage();
    }

    public function selectCard($id)
    {
        $this->selectedCard = $this->selectedCard === $id ? null : $id;
    }

    public function chooseSubRegion($region)
    {
        $guestUuid = session('guest_uuid');
        if ($guestUuid) {
            Guest_User::where('uuid', $guestUuid)->update([
                'last_seen_at' => now(),
                'current_location' => $region,
            ]);
            event(new UserLocationUpdated());
        }

        return redirect()->route('user.scan-item', [
            'categoryRegion' => $this->categoryRegion,
            'subRegion' => $region,
        ]);
    }
};
?>

<div x-data="{
    init() {
        Echo.channel('scanner-room')
            .listen('.UserLocationUpdated', (e) => {
                $wire.$refresh();
            });
    }
}" class="flex flex-col gap-4 md:gap-6 w-full max-w-7xl mx-auto pb-8">

    <div class="flex gap-2 mt-4 items-center">
        <div wire:poll.30s="keepAlive"></div>
        <span class="text-xs text-[#64748b] font-semibold uppercase tracking-wide hover:text-[#4338CB] cursor-pointer"
            wire:click='backToRegion'>Region</span>
        <flux:icon.chevron-right class="w-3 h-3 text-[#64748b]" />
        <span class="text-sm text-[#4338CB] font-semibold uppercase tracking-wide">{{ $categoryRegion }}</span>
    </div>

    <div
        class="flex items-center w-full border-[1.5px] border-[#cbd5e1] bg-[#ffffff] rounded-lg focus-within:border-[#4338ca] focus-within:ring-2 focus-within:ring-[#c7d2fe] transition-all h-[48px] px-3">
        <flux:icon.magnifying-glass class="text-[#64748b] w-5 h-5 shrink-0" />
        <input type="text" placeholder="Cari daerah, kota atau satker..."
            class="flex-1 bg-transparent border-none focus:outline-none focus:ring-0 text-[#0f172a] ml-2 text-sm md:text-base placeholder:text-[#64748b]"
            wire:model.live.debounce.300ms="search">
        <button x-show="$wire.search.length > 0" wire:click="clearSearch" type="button" class="shrink-0 p-1">
            <flux:icon.x-circle class="text-[#64748b] w-5 h-5 hover:text-[#0f172a]" />
        </button>
    </div>

    {{-- Filter --}}
    <div class="flex w-full gap-2 overflow-x-auto flex-nowrap md:flex-wrap scrollbar-hide">
        {{-- Semua --}}
        <button wire:click="selectFilter('all')" type="button" @class([
            'flex shrink-0 gap-2 h-[48px] px-4 rounded-md items-center justify-center transition-colors font-medium text-sm md:text-base cursor-pointer',
            'bg-[#4338ca] text-[#ffffff] border border-[#4338ca]' => $selectedFilter === 'all',
            'bg-[#ffffff] border-[1.5px] border-[#cbd5e1] text-[#0f172a] hover:bg-[#f8fafc]' => $selectedFilter !== 'all',
        ])>
            Semua
            <span @class([
                'text-xs py-0.5 px-2 rounded font-bold font-jetbrains',
                'bg-[#3730a3] text-white' => $selectedFilter === 'all',
                'bg-[#f1f5f9] text-[#64748b]' => $selectedFilter !== 'all',
            ])>
                {{ $this->regions->total() }}
            </span>
        </button>

        {{-- Tersedia --}}
        <button wire:click="selectFilter('ready')" type="button" @class([
            'flex shrink-0 gap-2 h-[48px] px-4 rounded-md items-center justify-center transition-colors font-medium text-sm md:text-base cursor-pointer',
            'bg-[#4338ca] text-[#ffffff] border border-[#4338ca]' =>
                $selectedFilter === 'ready',
            'bg-[#ffffff] border-[1.5px] border-[#cbd5e1] text-[#0f172a] hover:bg-[#f8fafc]' =>
                $selectedFilter !== 'ready',
        ])>
            <span
                class="rounded-full w-2 h-2 {{ $selectedFilter === 'ready' ? 'bg-[#ffffff]' : 'bg-[#059669]' }}"></span>
            Tersedia
            <span @class([
                'text-xs py-0.5 px-2 rounded font-bold font-jetbrains',
                'bg-[#3730a3] text-white' => $selectedFilter === 'ready',
                'bg-[#f1f5f9] text-[#64748b]' => $selectedFilter !== 'ready',
            ])>
                34
            </span>
        </button>

        {{-- Sedang Dikerjakan --}}
        <button wire:click="selectFilter('working')" type="button" @class([
            'flex shrink-0 gap-2 h-[48px] px-4 rounded-md items-center justify-center transition-colors font-medium text-sm md:text-base cursor-pointer',
            'bg-[#4338ca] text-[#ffffff] border border-[#4338ca]' =>
                $selectedFilter === 'working',
            'bg-[#ffffff] border-[1.5px] border-[#cbd5e1] text-[#0f172a] hover:bg-[#f8fafc]' =>
                $selectedFilter !== 'working',
        ])>
            <span
                class="rounded-full w-2 h-2 {{ $selectedFilter === 'working' ? 'bg-[#ffffff]' : 'bg-[#d97706]' }}"></span>
            Sedang Dikerjakan
            <span @class([
                'text-xs py-0.5 px-2 rounded font-bold font-jetbrains',
                'bg-[#3730a3] text-white' => $selectedFilter === 'working',
                'bg-[#f1f5f9] text-[#64748b]' => $selectedFilter !== 'working',
            ])>
                34
            </span>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 w-full">
        @forelse ($this->regions as $region)
            <div wire:key="region-{{ $region->name_region }}" wire:click="selectCard('{{ $region->name_region }}')"
                @class([
                    'flex flex-col w-full border bg-[#ffffff] rounded-lg overflow-hidden transition-all cursor-pointer',
                    'border-[#4338ca] ring-1 ring-[#4338ca] shadow-[0_1px_2px_rgba(15,23,42,0.12)]' =>
                        $selectedCard === $region->name_region,
                    'border-[#e2e8f0] hover:border-[#cbd5e1] hover:shadow-[0_1px_2px_rgba(15,23,42,0.06)]' =>
                        $selectedCard !== $region->name_region,
                ])>
                @php
                    $workers = $this->activeUsers[$region->name_region] ?? [];

                    $worker_count = count($workers);

                    // dd($region);

                    $status = '';
                    $status_bg = '';
                    $status_border = '';
                    $status_text = '';
                    $dot_color = '';

                    if ($region->count_items_scanned === $region->total_items) {
                        // Total region sudah selesai semua
                        $status = 'Selesai';
                        $status_bg = 'bg-[#ecfdf5]';
                        $status_border = 'border-[#a7f3d0]';
                        $status_text = 'text-[#059669]';
                        $dot_color = 'bg-[#059669]';
                    } elseif ($worker_count === 0) {
                        // Masih ada slot worker
                        $status = 'Tersedia';
                        $status_bg = 'bg-[#ecfdf5]';
                        $status_border = 'border-[#a7f3d0]';
                        $status_text = 'text-[#059669]';
                        $dot_color = 'bg-[#059669]';
                    } else {
                        // Worker sudah memenuhi / melebihi slot
                        $status = 'Penuh';
                        $status_bg = 'bg-[#fffbeb]';
                        $status_border = 'border-[#fcd34d]';
                        $status_text = 'text-[#92400e]';
                        $dot_color = 'bg-[#d97706]';
                    }
                @endphp

                {{-- Header --}}
                <div class="flex justify-between items-center p-4 border-b border-[#e2e8f0] bg-[#ffffff]">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex items-center gap-2 px-2.5 py-1 rounded border {{ $status_bg }} {{ $status_border }} {{ $status_text }}">
                            <span class="rounded-full w-1.5 h-1.5 {{ $dot_color }}"></span>
                            <span class="text-xs font-bold uppercase tracking-wider">
                                {{ $status }}
                            </span>
                        </div>
                        <span
                            class="text-xs font-semibold text-[#64748b] font-jetbrains bg-[#f8fafc] px-2 py-1 rounded border border-[#e2e8f0]">
                            ID: REG-01
                        </span>
                    </div>
                </div>

                {{-- Body --}}
                <div class="flex flex-col gap-4 p-4">
                    <h2 class="text-lg md:text-xl font-bold text-[#0f172a] leading-tight"
                        style="font-family: 'Space Grotesk', sans-serif;">
                        {{ $region->name_region }}
                    </h2>

                    <div class="grid grid-cols-2 gap-3 text-sm text-[#334155]">
                        <div class="flex flex-col p-2.5 bg-[#f8fafc] rounded border border-[#e2e8f0]">
                            <span class="text-xs text-[#64748b] font-semibold mb-1 uppercase tracking-wide">Total
                                Item</span>
                            <div class="flex items-center gap-1.5 font-bold text-[#0f172a] font-jetbrains">
                                <flux:icon.archive-box class="w-4 h-4 text-[#3b82f6]" />
                                <span>{{ $region->total_items }} Item</span>
                            </div>
                        </div>
                        <div class="flex flex-col p-2.5 bg-[#f8fafc] rounded border border-[#e2e8f0]">
                            <span class="text-xs text-[#64748b] font-semibold mb-1 uppercase tracking-wide">Total Done
                                Scan</span>
                            <div class="flex items-center gap-1.5 font-bold text-[#0f172a] font-jetbrains">
                                <flux:icon.archive-box class="w-4 h-4 text-[#3b82f6]" />
                                <span>{{ $region->count_items_scanned }} Item</span>
                            </div>
                        </div>
                    </div>

                    {{-- List Team Section --}}
                    <div class="flex flex-col gap-2.5 pt-1">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-2 text-xs md:text-sm font-semibold text-[#334155]">
                                <flux:icon.users class="w-4 h-4 text-[#64748b]" />
                                <span>Tim Aktif ({{ count($workers) }} Operator):</span>
                            </div>
                            <div
                                class="flex items-center px-2 py-0.5 bg-[#f1f5f9] border border-[#e2e8f0] rounded text-[11px] font-bold font-jetbrains text-[#64748b] tracking-wider uppercase">
                                {{ count($workers) }} / 1 Worker
                            </div>
                        </div>

                        @if (count($workers) > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($workers as $worker)
                                    @php
                                        $wName = is_array($worker) ? $worker['name'] ?? 'User' : $worker;
                                        $wLocation = is_array($worker) ? $worker['location'] ?? 'Operator' : 'Operator';

                                        // Generate initials
                                        $nameParts = preg_split('/\s+/', trim($wName));
                                        if (count($nameParts) >= 2) {
                                            $initials = strtoupper(
                                                mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1),
                                            );
                                        } else {
                                            $initials = strtoupper(mb_substr($wName, 0, 2));
                                        }
                                    @endphp
                                    <div
                                        class="flex items-center gap-2 p-2 bg-[#ffffff] border border-[#e2e8f0] rounded-lg shadow-sm">
                                        <span class="w-2 h-2 rounded-full bg-[#10b981] shrink-0"></span>
                                        <div
                                            class="w-7 h-7 rounded-full bg-[#f1f5f9] text-[#475569] font-bold text-xs flex items-center justify-center shrink-0 border border-[#e2e8f0]">
                                            {{ $initials }}
                                        </div>
                                        <div class="flex flex-col min-w-0 leading-tight">
                                            <span
                                                class="text-xs font-bold text-[#0f172a] truncate">{{ $wName }}</span>
                                            <span
                                                class="text-[11px] text-[#64748b] truncate">{{ $wLocation }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- Empty State --}}
                            <div
                                class="flex items-center gap-2 p-2.5 bg-[#f8fafc] border border-dashed border-[#cbd5e1] rounded-lg text-[#64748b]">
                                <span class="w-2 h-2 rounded-full bg-[#94a3b8] shrink-0"></span>
                                <span class="text-xs font-medium">Belum ada operator aktif di daerah ini</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer Action --}}
                <div class="p-4 pt-0">
                    @php
                        $isLocked = $worker_count !== 0;
                    @endphp
                    <button
                        @if (!$isLocked) wire:click.stop="chooseSubRegion('{{ $region->name_region }}')"
                        @else
                            disabled @endif
                        @class([
                            'w-full flex items-center justify-center gap-2 border-[1.5px] font-bold text-sm md:text-base h-[48px] rounded transition-colors focus:ring-2 focus:ring-[#c7d2fe] cursor-pointer',
                            // active selected
                            'bg-[#4338ca] text-[#ffffff] border-[#4338ca] hover:bg-[#3730a3]' =>
                                $selectedCard === $region->name_region && !$isLocked,
                            // active not selected
                            'bg-[#ffffff] text-[#0f172a] border-[#cbd5e1] hover:bg-[#f8fafc]' =>
                                $selectedCard !== $region->name_region && !$isLocked,
                            // disabled style
                            'bg-gray-300 text-gray-500 border-gray-400 cursor-not-allowed' => $isLocked,
                        ])>
                        @if ($isLocked)
                            <flux:icon.lock-closed @class(['w-5 h-5', 'text-gray-500' => $isLocked]) />
                        @else
                            <flux:icon.user-plus @class([
                                'w-5 h-5',
                                'text-[#ffffff]' => $selectedCard === $region->name_region && !$isLocked,
                                'text-[#4338ca]' => $selectedCard !== $region->name_region && !$isLocked,
                            ]) />
                        @endif
                        @if ($isLocked)
                            <span>Team Penuh</span>
                        @else
                            <span>{{ $selectedCard === $region->name_region ? 'Lanjutkan Proses' : 'Mulai Kolaborasi' }}</span>
                        @endif
                    </button>
                </div>
            </div>
        @empty
            <div
                class="col-span-full flex flex-col items-center justify-center p-12 bg-[#ffffff] border border-[#e2e8f0] rounded-lg text-center">
                <flux:icon.magnifying-glass class="w-8 h-8 text-[#64748b] mb-2" />
                <p class="text-base font-semibold text-[#0f172a]">Tidak ada region ditemukan</p>
                <p class="text-sm text-[#64748b] mt-1">Coba sesuaikan kata kunci pencarian Anda.</p>
            </div>
        @endforelse
    </div>

    {{-- Custom Pagination --}}
    @if ($this->regions->hasPages())
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-[#e2e8f0]">
            <div class="text-sm text-[#64748b] font-medium order-2 sm:order-1">
                Menampilkan <span
                    class="font-bold text-[#0f172a] font-jetbrains">{{ $this->regions->firstItem() }}</span> - <span
                    class="font-bold text-[#0f172a] font-jetbrains">{{ $this->regions->lastItem() }}</span> dari <span
                    class="font-bold text-[#0f172a] font-jetbrains">{{ $this->regions->total() }}</span> daerah
            </div>

            <div class="flex items-center gap-1.5 order-1 sm:order-2">
                {{-- Previous Page Button --}}
                @if ($this->regions->onFirstPage())
                    <button type="button" disabled
                        class="flex items-center justify-center min-w-[40px] h-[40px] px-3 rounded border border-[#e2e8f0] bg-[#f8fafc] text-[#94a3b8] cursor-not-allowed text-sm font-medium">
                        <flux:icon.chevron-left class="w-4 h-4 mr-1" />
                        <span class="hidden sm:inline">Sebelumnya</span>
                    </button>
                @else
                    <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                        class="flex items-center justify-center min-w-[40px] h-[40px] px-3 rounded border border-[#cbd5e1] bg-[#ffffff] text-[#0f172a] hover:bg-[#f8fafc] hover:border-[#94a3b8] transition-colors text-sm font-medium">
                        <flux:icon.chevron-left class="w-4 h-4 mr-1 text-[#64748b]" />
                        <span class="hidden sm:inline">Sebelumnya</span>
                    </button>
                @endif

                {{-- Page Numbers --}}
                <div class="flex items-center gap-1">
                    @foreach ($this->regions->getUrlRange(max(1, $this->regions->currentPage() - 2), min($this->regions->lastPage(), $this->regions->currentPage() + 2)) as $page => $url)
                        @if ($page == $this->regions->currentPage())
                            <button type="button"
                                class="flex items-center justify-center w-[40px] h-[40px] rounded border border-[#4338ca] bg-[#4338ca] text-[#ffffff] font-bold font-jetbrains text-sm">
                                {{ $page }}
                            </button>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }})"
                                wire:loading.attr="disabled"
                                class="flex items-center justify-center w-[40px] h-[40px] rounded border border-[#cbd5e1] bg-[#ffffff] text-[#0f172a] hover:bg-[#f8fafc] hover:border-[#94a3b8] font-jetbrains text-sm font-medium transition-colors">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                </div>

                {{-- Next Page Button --}}
                @if ($this->regions->hasMorePages())
                    <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                        class="flex items-center justify-center min-w-[40px] h-[40px] px-3 rounded border border-[#cbd5e1] bg-[#ffffff] text-[#0f172a] hover:bg-[#f8fafc] hover:border-[#94a3b8] transition-colors text-sm font-medium">
                        <span class="hidden sm:inline">Selanjutnya</span>
                        <flux:icon.chevron-right class="w-4 h-4 ml-1 text-[#64748b]" />
                    </button>
                @else
                    <button type="button" disabled
                        class="flex items-center justify-center min-w-[40px] h-[40px] px-3 rounded border border-[#e2e8f0] bg-[#f8fafc] text-[#94a3b8] cursor-not-allowed text-sm font-medium">
                        <span class="hidden sm:inline">Selanjutnya</span>
                        <flux:icon.chevron-right class="w-4 h-4 ml-1" />
                    </button>
                @endif
            </div>
        </div>
    @endif

</div>
