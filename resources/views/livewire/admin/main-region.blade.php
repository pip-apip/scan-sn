<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
    <div class="space-y-4">
        {{-- Toolbar --}}
        <div class="flex items-center justify-between">
            <div>
                <input type="text" placeholder="Search region..."
                    class="w-72 rounded-lg border border-zinc-300 px-4 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none">
            </div>

            <div class="flex items-center gap-3">
                <button @click="showImportRegionModal = true"
                    class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50">
                    Import
                </button>

                <button @click="showNewRegionModal = true"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    + New Region
                </button>
            </div>
        </div>
        {{-- Table Card --}}
        <livewire:admin.region.region-table />
    </div>
</div>
