<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\Item_sn_references;
use App\Models\Item;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;
    use WithoutUrlPagination;

    public $itemFilter = null;
    public $sort = 'asc';

    public $filter = [
        [
            'type' => 'sort',
            'value' => 'asc',
        ],
        [
            'type' => 'group_by',
            'value' => 'item_id',
        ],
    ];

    public function mount()
    {
        $this->resetPage();
    }

    public function updatedItemFilter()
    {
        $this->resetPage();
        unset($this->serials);
    }

    public function getItemsProperty()
    {
        return Item::all();
    }

    public function getSerialsProperty()
    {
        return Item_sn_references::query()->when($this->itemFilter, fn($q) => $q->where('item_id', $this->itemFilter))->orderBy('item_id', $this->sort)->Paginate(10);
    }

    #[On('refreshTable')]
    public function refreshTable()
    {
        unset($this->serials); // clear computed cache
    }
}; ?>

<div class="space-y-4">
    {{-- Toolbar --}}
    <div class="flex items-center justify-between">
        {{-- Item Filter --}}
        <div class="flex items-center gap-3">
            <span>Filter by Item:</span>

            {{-- All --}}
            <button wire:click="$set('itemFilter', null)"
                class="rounded-lg border px-4 py-2 text-sm font-medium transition cursor-pointer
                    {{ $itemFilter === null
                        ? 'border-indigo-600 bg-indigo-600 text-white'
                        : 'border-zinc-300 text-zinc-500 hover:border-indigo-600 hover:bg-zinc-50 hover:text-indigo-600' }}">
                All
            </button>

            {{-- Items --}}
            @foreach ($this->items as $item)
                <button wire:click="$set('itemFilter', {{ $item->id }})"
                    class="rounded-lg border px-4 py-2 text-sm font-medium transition cursor-pointer
                        {{ $itemFilter == $item->id
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-zinc-300 text-zinc-500 hover:border-indigo-600 hover:bg-zinc-50 hover:text-indigo-600' }}">
                    {{ $item->name }}
                </button>
            @endforeach

        </div>

        <div class="flex items-center gap-3">
            <button @click="showImportSNModal = true"
                class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50">
                Import
            </button>
            <button @click="showNewSNModal = true"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                + New SN
            </button>
        </div>
    </div>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-zinc-50">
                    <tr class="border-b border-zinc-200">
                        <th
                            class="w-16 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            No
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Item Name
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Serial Number
                        </th>
                        <th
                            class="w-44 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @if ($this->serials->isEmpty())
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-zinc-500">
                                No Serial Numbers found.
                            </td>
                        </tr>
                    @else
                        @foreach ($this->serials as $serial)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-6 py-4">{{ $this->serials->firstItem() + $loop->index }}</td>
                                <td class="px-6 py-4 font-medium">
                                    {{ $serial->item->name }}
                                </td>
                                <td class="px-6 py-4 font-medium">
                                    {{ $serial->serial_number }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            class="rounded-md border border-indigo-600 px-3 py-1.5 text-sm font-medium text-indigo-600 transition hover:bg-indigo-600 hover:text-white">
                                            Edit
                                        </button>
                                        <button
                                            class="rounded-md border border-red-500 px-3 py-1.5 text-sm font-medium text-red-500 transition hover:bg-red-500 hover:text-white">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        {{-- Footer --}}
        <div class="border-t border-zinc-200 px-6 py-4">
            {{ $this->serials->links() }}
        </div>

    </div>
</div>
