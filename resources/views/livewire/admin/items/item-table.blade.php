<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\Item;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    public function mount()
    {
        $this->resetPage();
    }

    #[Computed]
    public function getItemsProperty()
    {
        return Item::simplePaginate(10);
    }

    #[On('refreshTable')]
    public function refreshTable()
    {
        unset($this->items); // clear computed cache
    }

    #[On('echo:scanner-room,.UserLocationUpdated')]
    public function refreshActiveUsers()
    {

    }
}; ?>

<div>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-zinc-50">
                    <tr class="border-b border-zinc-200">
                        <th class="w-16 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            No
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Item Name
                        </th>
                        <th
                            class="w-44 px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @if ($this->items->isEmpty())
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-zinc-500">
                                No items found.
                            </td>
                        </tr>
                    @else
                        @foreach ($this->items as $item)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-6 py-4">{{ $this->items->firstItem() + $loop->index}}</td>
                                <td class="px-6 py-4 font-medium">
                                    {{ $item->name }}
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
            {{ $this->items->links() }}
        </div>

    </div>
</div>
