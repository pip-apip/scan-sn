<?php

use Livewire\Volt\Component;
use App\Models\Item;

new class extends Component {
    public $name;

    public function mount()
    {
        $this->name = '';
    }

    public function save()
    {
        try {
            $data = [
                'name' => $this->name,
                'project_id' => 72,
            ];

            $save = Item::create($data);
            // $save = false;

            if ($save) {
                Flux::toast('Your changes have been saved.', null, 3000, 'success');
            }
            // dd($data);

            $this->clear();
            $this->dispatch('refreshTable')->to('admin.items.item-table');
            $this->dispatch('item-saved');
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function clear()
    {
        $this->reset('name');
    }
}; ?>

<div x-show="showNewItemModal" x-transition
    x-on:item-saved.window="
        $wire.clear()
        showNewItemModal = false
    "
    @keydown.escape.window="
        showNewItemModal = false
    "
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
    <div @click.outside="showNewItemModal = false" class="w-full max-w-md rounded-xl bg-white shadow-xl">
        <div class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold">New Item</h2>
        </div>
        <form wire:submit.prevent="save">
            <div class="space-y-4 p-6">
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Item Name
                    </label>

                    <input type="text" wire:model.live="name" placeholder="Enter Item Name"
                        class="w-full rounded-lg border border-zinc-300 px-4 py-2 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t px-6 py-4">
                <button @click="showNewItemModal = false" wire:click="clear" type="button"
                    class="rounded-lg border border-zinc-300 px-4 py-2 hover:bg-zinc-100">
                    Cancel
                </button>

                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" type="submit">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
