<?php

use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\Item_sn_references;

new class extends Component {
    public $serial_number;
    public $item_id;

    public function getItemsProperty()
    {
        return Item::all();
    }

    public function save()
    {
        try {
            $data = [
                'serial_number' => $this->serial_number,
                'item_id' => $this->item_id,
                'is_used' => 0
            ];

            $check = Item_sn_references::where('serial_number', $this->serial_number)
                ->where('item_id', $this->item_id)
                ->first();

            if ($check) {
                Flux::toast('Serial number already exists.', null, 3000, 'error');
            }else{
                $save = Item_sn_references::create($data);
                if ($save) {
                    Flux::toast('Your changes have been saved.', null, 3000, 'success');
                }
                $this->clear();
                $this->dispatch('sn-saved');

            }

        } catch (\Throwable $th) {
            dd($th);
            Flux::toast('Something went wrong.', null, 3000, 'error');
        }
    }

    public function clear()
    {
        $this->reset('serial_number', 'item_id');
    }
}; ?>

<div x-show="showNewSNModal" x-transition
    x-on:sn-saved.window="
        $wire.clear()
        showNewSNModal = false
    "
    @keydown.escape.window="
        showNewSNModal = false
    "
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
    <div @click.outside="showNewSNModal = false" class="w-full max-w-md rounded-xl bg-white shadow-xl">
        <div class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold">New Serial Number</h2>
        </div>
        <form wire:submit.prevent="save">
            <div class="space-y-4 p-6">
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Item
                    </label>

                    <select class="block w-full rounded-lg border border-zinc-300 p-2" wire:model.live="item_id">
                        <option>Select Item</option>
                        @foreach ($this->items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Item Serial Number
                    </label>

                    <input type="text" wire:model.live="serial_number" placeholder="Enter Item Serial Number"
                        class="w-full rounded-lg border border-zinc-300 px-4 py-2 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t px-6 py-4">
                <button @click="showNewSNModal = false" wire:click="clear" type="button"
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
