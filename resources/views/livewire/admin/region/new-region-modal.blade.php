<?php

use Livewire\Volt\Component;
use App\Models\Region;

new class extends Component {
    public $name;
    public $category;

    public function mount()
    {
        $this->name = '';
    }

    public function save()
    {
        try {
            $data = [
                'category-region' => $this->name,
                'name-region' => $this->name,
                'project_id' => 72,
            ];

            // $save = Region::create($data);
            $save = true;

            if ($save) {
                Flux::toast('Your changes have been saved.', null, 3000, 'success');
                // Log::info('Region has been created', $save->getAttributes());
            }
            // dd($data);

            $this->clear();
            $this->dispatch('region-saved');
            $this->dispatch('refreshTable')->to('admin.region.region-table');
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function clear()
    {
        $this->reset('name');
    }
}; ?>

<div x-show="showNewRegionModal" x-transition
    x-on:region-saved.window="
        $wire.clear()
        showNewRegionModal = false
    "
    @keydown.escape.window="
        showNewRegionModal = false
    "
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
    <div @click.outside="showNewRegionModal = false" class="w-full max-w-md rounded-xl bg-white shadow-xl">
        <div class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold">New Region</h2>
        </div>
        <form wire:submit.prevent="save">
            <div class="space-y-4 p-6">
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Region Category
                    </label>

                    <input type="text" wire:model.live="category" placeholder="Enter region category"
                        class="w-full rounded-lg border border-zinc-300 px-4 py-2 focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Region Name
                    </label>

                    <input type="text" wire:model.live="name" placeholder="Enter region name"
                        class="w-full rounded-lg border border-zinc-300 px-4 py-2 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t px-6 py-4">
                <button @click="showNewRegionModal = false" wire:click="clear" type="button"
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
