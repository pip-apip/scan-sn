<?php

use Livewire\Volt\Component;

new class extends Component {

}; ?>

<div>
    <div class="space-y-4">
        {{-- Toolbar --}}
        <div class="flex items-center justify-between">
            <div>
                <input type="text" placeholder="Search Item..."
                    class="w-72 rounded-lg border border-zinc-300 px-4 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>

            <div class="flex items-center gap-3">
                <button @click="showNewItemModal = true"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    + New Item
                </button>
            </div>
        </div>
        {{-- Table Card --}}
        <livewire:admin.items.item-table />
    </div>
</div>
