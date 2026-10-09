<?php

use App\Models\Item;
use App\Models\Region;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public string $projectSelected = '';

    public string $search = '';

    public string $tableMode = 'list';

    public function toggleTableMode(): void
    {
        $this->tableMode = $this->tableMode === 'list' ? 'grid' : 'list';
    }

    /**
     * Lifecycle hook Livewire calls whenever `$projectSelected` changes.
     * The report itself is a computed property (auto re-evaluated on every
     * request), so here we only reset state that belonged to the old project.
     */
    public function updatedProjectSelected(): void
    {
        $this->search = '';

        unset($this->data, $this->matrix, $this->items, $this->groups);
    }

    /**
     * Projects available in the <select>.
     *
     * There is no `projects` table yet, so the option list is derived from the
     * distinct project ids that actually have items registered. When a Project
     * model is added, only this method needs to change.
     *
     * @return Collection<int, array{id: string, name: string}>
     */
    #[Computed]
    public function projects()
    {
        $names = [
            72 => 'Paket N1',
        ];

        return Item::query()
            ->select('project_id')
            ->distinct()
            ->orderBy('project_id')
            ->pluck('project_id')
            ->map(fn ($id) => [
                'id' => (string) $id,
                'name' => $names[$id] ?? "Project #{$id}",
            ]);
    }

    /**
     * Report rows (grouped per region) for the selected project.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function data()
    {
        return $this->getData($this->projectSelected);
    }

    /**
     * Item columns for the selected project.
     *
     * Ordered by id so the header columns and the cells inside every region
     * always line up.
     *
     * @return Collection<int, Item>
     */
    #[Computed]
    public function items()
    {
        if (blank($this->projectSelected)) {
            return collect();
        }

        return Item::where('project_id', $this->projectSelected)
            ->orderBy('id')
            ->get(['id', 'name']);
    }

    /**
     * Matrix rows for the "List View" table.
     *
     * - one row per region
     * - regions sharing a `category_region` are merged through `category_rowspan`
     * - one column per item, and the cell holds the region's `serial_number`
     *
     * @return array{items: Collection<int, Item>, rows: Collection<int, array<string, mixed>>}
     */
    #[Computed]
    public function matrix()
    {
        $columns = $this->items;

        $number = 0;
        $rows = [];

        $this->data
            ->groupBy(fn ($region) => $region['category_region'] ?: 'Tanpa Kategori')
            ->each(function ($regions, $categoryRegion) use ($columns, &$number, &$rows) {
                $regions = $regions->values();
                $rowspan = $regions->count();

                foreach ($regions as $index => $region) {
                    $number++;

                    $serialsByItem = collect($region['items'])->keyBy('item_id');

                    $rows[] = [
                        'no' => $number,
                        'category_region' => $categoryRegion,
                        'category_rowspan' => $index === 0 ? $rowspan : 0,
                        'name_region' => $region['name_region'],
                        'serials' => $columns
                            ->mapWithKeys(fn ($item) => [
                                $item->id => $serialsByItem->get($item->id)['serial_number'] ?? null,
                            ])
                            ->all(),
                    ];
                }
            });

        return [
            'items' => $columns,
            'rows' => collect($rows),
        ];
    }

    /**
     * Scan-status groups for the "Group View".
     *
     * Regions are bucketed and ordered by how complete their scan is:
     *   complete    -> every item of the project is scanned (e.g. 5/5)
     *   ongoing     -> some, but not all, items are scanned
     *   not_scanned -> no item has been scanned yet
     *
     * @return Collection<int, array{status: string, label: string, count: int, regions: Collection<int, array<string, mixed>>}>
     */
    #[Computed]
    public function groups()
    {
        $grouped = $this->data
            ->map(function ($region) {
                $total = $region['items']->count();
                $scanned = $region['items']->where('is_scanned', true)->count();

                return array_merge($region, [
                    'scanned_count' => $scanned,
                    'total_items' => $total,
                    'status' => match (true) {
                        $total > 0 && $scanned === $total => 'complete',
                        $scanned === 0 => 'not_scanned',
                        default => 'ongoing',
                    },
                ]);
            })
            ->groupBy('status');

        $labels = [
            'complete' => 'Complete Scan',
            'ongoing' => 'On Going',
            'not_scanned' => 'Not Scan',
        ];

        return collect($labels)
            ->map(function ($label, $status) use ($grouped) {
                $regions = $grouped->get($status, collect());

                return [
                    'status' => $status,
                    'label' => $label,
                    'count' => $regions->count(),
                    'regions' => $regions,
                ];
            })
            ->values();
    }

    /**
     * Build the report for a given project.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getData(?string $projectId)
    {
        if (blank($projectId)) {
            return collect();
        }

        // 1. Ambil semua item yang terdaftar pada project_id tersebut.
        //    Diurutkan konsisten agar header kolom & isi sel selalu sejajar.
        $allItems = Item::where('project_id', $projectId)->orderBy('id')->get(['id', 'name']);

        // 2. Ambil regions beserta data mapping_lists terkait pada project_id tersebut.
        //    Diurutkan per kategori agar baris berkategori sama berdekatan (untuk di-merge).
        $regions = Region::where('project_id', $projectId)
            ->orderBy('id')
            ->orderBy('category_region')
            ->orderBy('name_region')
            ->with([
                'mappingLists' => function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                },
            ])
            ->when(filled($this->search), function ($query) {
                $query->where(function ($q) {
                    $q->where('name_region', 'like', "%{$this->search}%")
                        ->orWhere('category_region', 'like', "%{$this->search}%");
                });
            })
            ->get();

        // 3. Format/Transformasi Data ke struktur JSON yang diinginkan
        return $regions->map(function ($region) use ($allItems) {
            // Key-by item_id untuk pencarian O(1) yang cepat
            $mappingsByItem = $region->mappingLists->keyBy('item_id');

            return [
                'region_id' => $region->id,
                'category_region' => $region->category_region,
                'name_region' => $region->name_region,
                'items' => $allItems
                    ->map(function ($item) use ($mappingsByItem) {
                        $mapping = $mappingsByItem->get($item->id);

                        return [
                            'item_id' => $item->id,
                            'item_name' => $item->name,
                            'serial_number' => $mapping?->serial_number,
                            'is_scanned' => $mapping !== null, // Status penanda opsional
                        ];
                    })
                    ->values(),
            ];
        });
    }
}; ?>

<div class="flex flex-col gap-4">
    <h1>Results</h1>

    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <label for="project" class="text-sm font-medium text-gray-700">Project :</label>
                <select id="project" wire:model.live="projectSelected"
                    class="w-48 rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring focus:ring-blue-200">
                    <option value="">Select Project</option>
                    @foreach ($this->projects as $project)
                        <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search region..."
                class="w-72 rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring focus:ring-blue-200" />
        </div>

        <div class="flex flex-col items-center justify-center p-2">
            <button type="button" role="switch" wire:click="toggleTableMode"
                aria-checked="{{ $tableMode === 'grid' ? 'true' : 'false' }}"
                class="relative inline-flex h-6 w-12 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors duration-200
                    {{ $tableMode === 'list' ? 'bg-[#4338ca]' : 'bg-zinc-300 dark:bg-slate-600' }}">
                <span
                    class="inline-flex h-5 w-5 transform items-center justify-center rounded-full bg-white shadow-sm transition-transform duration-200
                        {{ $tableMode === 'list' ? 'translate-x-6' : 'translate-x-0' }}">
                    @if ($tableMode === 'list')
                        <flux:icon.list-bullet class="h-4 w-4 text-[#4338ca]" />
                    @else
                        <flux:icon.rectangle-group class="h-4 w-4 text-zinc-400" />
                    @endif
                </span>
            </button>
            <span class="text-sm text-gray-500">
                {{ $tableMode === 'list' ? 'List View' : 'Group View' }}
            </span>
        </div>
    </div>

    @if (blank($projectSelected))
        <div class="rounded-xl border border-zinc-200 bg-white px-6 py-10 text-center text-sm text-zinc-500">
            Select a project to view its scan report.
        </div>
    @elseif ($tableMode === 'list')
        @php
            $matrix = $this->matrix;
        @endphp
        {{-- Matrix View: 3 kolom pertama di-freeze, kolom item bisa di-scroll horizontal --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-12rem)]">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead class="sticky top-0 z-30 bg-white shadow-sm">
                        <tr>
                            <th
                                class="sticky left-0 z-20 w-16 max-w-16 min-w-16 border-r border-b border-zinc-200 bg-zinc-50 px-3 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                No
                            </th>
                            <th
                                class="sticky left-16 z-20 w-40 min-w-40 border-r border-b border-zinc-200 bg-zinc-50 px-3 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Category Region
                            </th>
                            <th
                                class="sticky left-56 z-20 w-56 min-w-56 border-r border-b border-zinc-200 bg-zinc-50 px-3 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Region
                            </th>
                            @foreach ($matrix['items'] as $item)
                                <th
                                    class="min-w-40 border-b border-zinc-200 bg-zinc-50 px-4 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    {{ $item->name }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($matrix['rows'] as $row)
                            <tr class="group">
                                <td
                                    class="sticky left-0 z-10 w-16 max-w-16 min-w-16 border-r border-b border-zinc-100 bg-white px-3 py-4 group-hover:bg-zinc-50">
                                    {{ $row['no'] }}
                                </td>
                                @if ($row['category_rowspan'] > 0)
                                    <td rowspan="{{ $row['category_rowspan'] }}"
                                        class="sticky left-16 z-10 w-40 min-w-40 border-r border-b border-zinc-100 bg-white px-3 py-4 align-top font-medium group-hover:bg-zinc-50">
                                        {{ $row['category_region'] }}
                                    </td>
                                @endif
                                <td
                                    class="sticky left-56 z-10 w-56 min-w-56 truncate border-r border-b border-zinc-100 bg-white px-3 py-4 font-medium group-hover:bg-zinc-50">
                                    {{ $row['name_region'] }}
                                </td>
                                @foreach ($matrix['items'] as $item)
                                    <td class="border-b border-zinc-100 px-4 py-4 whitespace-nowrap">
                                        {{ $row['serials'][$item->id] ?? '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $matrix['items']->count() + 3 }}"
                                    class="px-6 py-4 text-center text-sm text-zinc-500">
                                    No data found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- Group View: dikelompokkan & diurutkan berdasarkan status scan --}}
        @if ($this->data->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white px-6 py-10 text-center text-sm text-zinc-500">
                No data found.
            </div>
        @else
            <div class="flex flex-col gap-8">
                @foreach ($this->groups as $group)
                    @if ($group['count'] > 0)
                        @php
                            $badgeClasses = match ($group['status']) {
                                'complete' => 'bg-green-100 text-green-700',
                                'ongoing' => 'bg-amber-100 text-amber-700',
                                default => 'bg-zinc-100 text-zinc-500',
                            };
                            $dotClasses = match ($group['status']) {
                                'complete' => 'bg-green-500',
                                'ongoing' => 'bg-amber-500',
                                default => 'bg-zinc-400',
                            };
                        @endphp
                        <section class="flex flex-col gap-3" data-test="scan-group-{{ $group['status'] }}">
                            {{-- Group header --}}
                            <div class="flex items-center gap-3">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold {{ $badgeClasses }}">
                                    <span class="h-2 w-2 rounded-full {{ $dotClasses }}"></span>
                                    {{ $group['label'] }}
                                </span>
                                <span class="text-sm text-zinc-500">
                                    {{ $group['count'] }} {{ $group['count'] === 1 ? 'region' : 'regions' }}
                                </span>
                                <span class="h-px flex-1 bg-zinc-200"></span>
                            </div>

                            {{-- Region cards --}}
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                @foreach ($group['regions'] as $region)
                                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                                        <div class="mb-3 flex items-start justify-between gap-2">
                                            <div>
                                                <p class="text-xs uppercase tracking-wide text-zinc-500">
                                                    {{ $region['category_region'] }}</p>
                                                <h2 class="text-lg font-semibold">{{ $region['name_region'] }}</h2>
                                            </div>
                                            <span
                                                class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600">
                                                {{ $region['scanned_count'] }}/{{ $region['total_items'] }} scanned
                                            </span>
                                        </div>

                                        <ul class="divide-y divide-zinc-100">
                                            @foreach ($region['items'] as $item)
                                                <li class="flex items-center justify-between gap-2 py-2">
                                                    <span class="text-sm font-medium">{{ $item['item_name'] }}</span>
                                                    <span
                                                        class="text-sm {{ $item['is_scanned'] ? 'text-green-600' : 'text-zinc-400' }}">
                                                        {{ $item['serial_number'] ?? 'Not Scanned' }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </div>
        @endif
    @endif
</div>
