<?php

use function Livewire\Volt\{state};

//

?>

<div x-data="{
    selected: 'daerah',
    showNewRegionModal: false,
    showNewItemModal: false,
    showImportRegionModal: false,
    showNewSNModal: false,
    showImportSNModal: false,
}">
    <div class="flex w-full border-b border-zinc-300 mb-4">
        <template
            x-for="tab in [
            { key: 'daerah', label: 'Daerah (Region)' },
            { key: 'jenis', label: 'Jenis Barang' },
            { key: 'master', label: 'Master Data (SN)' }
        ]"
            :key="tab.key">
            <button type="button" @click="selected = tab.key"
                class="px-5 py-2 text-2xl border-b-4 transition-colors duration-200 cursor-pointer"
                :class="selected === tab.key ?
                    'text-indigo-600 border-indigo-600' :
                    'text-zinc-600 border-transparent hover:text-indigo-600 hover:border-indigo-600'"
                x-text="tab.label"></button>
        </template>
    </div>
    {{-- Regions Section --}}
    <div x-show="selected === 'daerah'">
        <livewire:admin.main-region />
    </div>

    <div x-show="selected === 'jenis'">
        <livewire:admin.main-item />
    </div>

    <div x-show="selected === 'master'">


        {{-- Table Card --}}
        <livewire:admin.sn.sn-table />
    </div>

    {{-- New SN Modal --}}
    <livewire:admin.sn.new-sn-modal x-show="showNewSNModal" @close.window="showNewSNModal = false"
        @sn-saved.window="showNewSNModal = false" />

    {{-- Import SN Modal --}}
    <livewire:admin.sn.import-sn-modal x-show="showImportSNModal" @close.window="showImportSNModal = false"
        @import-success.window="showImportSNModal = false" />

    {{-- New Item Modal --}}
    <livewire:admin.items.new-item-modal x-show="showNewItemModal" @close.window="showNewItemModal = false"
        @region-saved.window="showNewItemModal = false" />

    {{-- New Region Modal --}}
    <livewire:admin.region.new-region-modal x-show="showNewRegionModal" @close.window="showNewRegionModal = false"
        @region-saved.window="showNewRegionModal = false" />

    {{-- Import Modal --}}
    <livewire:admin.region.import-region-modal x-show="showImportRegionModal"
        @close.window="showImportRegionModal = false" @import-success.window="showImportRegionModal = false" />

</div>
