<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Item;
use App\Models\Mapping_lists;
use App\Models\Item_sn_references;
use App\Models\Guest_User;
use App\Events\UserLocationUpdated;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new #[Layout('layouts.app-mobile')] class extends Component {
    public string $categoryRegion;
    public string $subRegion;
    public string $subRegionId;
    public string $serial_number = '';
    public string $idSelectedItem = '';
    public bool $compareSN = true;
    public string $deleteItemId = '';
    public string $deleteSerialNumber = '';

    public bool $showSnModal = false;
    public string $editItemId = '';
    public string $editItemName = '';
    public string $originalSerialNumber = '';
    public string $editSerialNumber = '';

    public function mount($categoryRegion, $subRegion)
    {
        $this->categoryRegion = $categoryRegion;
        $this->subRegion = $subRegion;
        $this->subRegionId = $this->getSubRegionId();

        $guestUuid = session('guest_uuid');
        if (!$guestUuid) {
            return redirect()->route('user.index');
        }

        Guest_User::where('uuid', $guestUuid)->update([
            'current_location' => $subRegion,
            'last_seen_at' => now(),
        ]);
    }

    public function keepAlive()
    {
        $guestUuid = session('guest_uuid');
        if ($guestUuid) {
            Guest_User::where('uuid', $guestUuid)->update([
                'last_seen_at' => now(),
            ]);
        }
    }

    public function backToRegion()
    {
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

    public function backToSubRegion()
    {
        $guestUuid = session('guest_uuid');
        if ($guestUuid) {
            Guest_User::where('uuid', $guestUuid)->update([
                'current_location' => $this->categoryRegion,
                'last_seen_at' => now(),
            ]);
        }

        event(new UserLocationUpdated());
        return redirect()->route('user.sub-region', [
            'categoryRegion' => $this->categoryRegion,
        ]);
    }

    public function getSubRegionId()
    {
        return DB::table('regions')->where('name_region', $this->subRegion)->value('id');
    }

    public function getItemSerialsProperty()
    {
        $subRegionId = $this->getSubRegionId();

        $items = Item::select('items.id', 'items.name', 'mapping_lists.serial_number', 'mapping_lists.created_at')
            ->leftJoin('mapping_lists', function ($join) use ($subRegionId) {
                $join->on('mapping_lists.item_id', '=', 'items.id')->where('mapping_lists.region_id', '=', $subRegionId);
            })
            ->where('items.project_id', 72)
            ->get();

        if (!$this->idSelectedItem) {
            $this->idSelectedItem = $items->first()?->id ?? '';
        }

        return $items;
    }

    public function columnItemSelected($itemId)
    {
        $this->idSelectedItem = $itemId;
    }

    public function getSelectedItemNameProperty()
    {
        if (empty($this->idSelectedItem)) {
            return null;
        }

        return Item::where('id', $this->idSelectedItem)->value('name');
    }

    public function checkEmptyMappingList($item_id, $subRegion_id, $serial_number, $project_id)
    {
        return Mapping_lists::where('item_id', $item_id)->where('region_id', $subRegion_id)->where('project_id', $project_id)->exists();
    }

    public function checkItemSNReference($item_id, $serial_number)
    {
        return Item_sn_references::where('item_id', $item_id)->where('serial_number', $serial_number)->exists();
    }

    public function barcodeDetected($barcode)
    {
        $barcode = trim((string) $barcode);

        if ($barcode === '') {
            return;
        }

        if (empty($this->idSelectedItem)) {
            Flux::toast('Pilih item terlebih dahulu sebelum scan.', null, 3000, 'danger');

            return;
        }

        $this->serial_number = $barcode;

        if ($this->compareSN) {
            $checkSerialReference = $this->checkItemSNReference($this->idSelectedItem, $this->serial_number);

            if (!$checkSerialReference) {
                $this->dispatch('show-dialog', title: 'Serial Number Tidak Ditemukan', message: "Serial Number '{$this->serial_number}' tidak ditemukan di reference table untuk item ini. Apakah Anda ingin melanjutkan?", action: 'save-mapping');

                return;
            }
        }

        $checkMappingList = $this->checkEmptyMappingList($this->idSelectedItem, $this->subRegionId, $this->serial_number, 72);

        if ($checkMappingList) {
            $itemName = Item::find($this->idSelectedItem)?->name ?? 'Unknown Item';

            $this->dispatch('show-dialog', title: 'Serial Number Sudah Ada', message: "Serial Number '{$this->serial_number}' sudah ada untuk item '{$itemName}' pada region yang dipilih. Apakah Anda ingin melanjutkan?", action: 'save-mapping');

            return;
        }

        $this->saveMapping();
    }

    #[On('dialog-result')]
    public function dialogResult(bool $confirmed, string $action = '', $itemId = null, $serialNumber = null)
    {
        if (!$confirmed) {
            return;
        }

        if ($action === 'save-mapping') {
            // Re-check duplicate mapping (the earlier check was skipped when the dialog was shown)
            $checkMappingList = $this->checkEmptyMappingList($this->idSelectedItem, $this->subRegionId, $this->serial_number, 72);

            if ($checkMappingList) {
                $itemName = Item::find($this->idSelectedItem)?->name ?? 'Unknown Item';

                Flux::toast("Serial Number '{$this->serial_number}' sudah ada untuk item '{$itemName}'.", null, 3000, 'danger');
                $this->clear();

                return;
            }

            $this->saveMapping();
            return;
        }

        if ($action === 'delete-mapping') {
            $this->deleteMapping();
            return;
        }
    }

    public function submitTypedSerial()
    {
        $this->barcodeDetected($this->serial_number);
    }

    public function saveMapping()
    {
        if (empty($this->serial_number) || empty($this->idSelectedItem)) {
            Flux::toast('Serial Number atau item belum dipilih.', null, 3000, 'danger');

            return;
        }

        $data = [
            'item_id' => $this->idSelectedItem,
            'region_id' => $this->subRegionId,
            'serial_number' => $this->serial_number,
            'project_id' => 72,
            'user_id' => 1,
        ];

        $save = Mapping_lists::create($data);

        if ($save) {
            Flux::toast('Your changes have been saved.', null, 3000, 'success');
        }

        $this->clear();
    }

    public function deleteMapping()
    {
        if (empty($this->deleteItemId) || empty($this->deleteSerialNumber)) {
            return;
        }

        $deleted = Mapping_lists::where('item_id', $this->deleteItemId)->where('region_id', $this->subRegionId)->where('project_id', 72)->where('serial_number', $this->deleteSerialNumber)->delete();

        if ($deleted) {
            Flux::toast('Serial Number berhasil dihapus.', null, 3000, 'success');
        } else {
            Flux::toast('Serial Number tidak ditemukan.', null, 3000, 'danger');
        }

        $this->deleteItemId = '';
        $this->deleteSerialNumber = '';

        // $this->idSelectedItem = '';
        $this->serial_number = '';

        $this->dispatch('refreshTable');
    }

    public function confirmDeleteSN($itemId, $serialNumber)
    {
        if (empty($serialNumber)) {
            return;
        }

        $this->deleteItemId = (string) $itemId;
        $this->deleteSerialNumber = (string) $serialNumber;

        $itemName = Item::find($itemId)?->name ?? 'Unknown Item';

        $this->dispatch('show-dialog', title: 'Hapus Serial Number', message: "Apakah Anda yakin ingin menghapus Serial Number '{$serialNumber}' dari item '{$itemName}'?", action: 'delete-mapping');
    }

    public function openSnModal($itemId, $serialNumber)
    {
        if (empty($serialNumber)) {
            return;
        }

        $this->editItemId = (string) $itemId;
        $this->editItemName = Item::find($itemId)?->name ?? 'Unknown Item';
        $this->originalSerialNumber = (string) $serialNumber;
        $this->editSerialNumber = (string) $serialNumber;

        $this->showSnModal = true;
    }

    public function closeSnModal()
    {
        $this->showSnModal = false;
        $this->editItemId = '';
        $this->editItemName = '';
        $this->originalSerialNumber = '';
        $this->editSerialNumber = '';
    }

    public function updateSerialNumber()
    {
        $newSerial = trim($this->editSerialNumber);

        if (empty($this->editItemId) || empty($newSerial)) {
            Flux::toast('Serial Number tidak boleh kosong.', null, 3000, 'danger');

            return;
        }

        if ($newSerial === $this->originalSerialNumber) {
            $this->closeSnModal();

            return;
        }

        $updated = Mapping_lists::where('item_id', $this->editItemId)
            ->where('region_id', $this->subRegionId)
            ->where('project_id', 72)
            ->where('serial_number', $this->originalSerialNumber)
            ->update(['serial_number' => $newSerial]);

        if ($updated) {
            Flux::toast('Serial Number berhasil diperbarui.', null, 3000, 'success');
        } else {
            Flux::toast('Serial Number tidak ditemukan.', null, 3000, 'danger');
        }

        $this->closeSnModal();
        $this->dispatch('refreshTable');
    }

    public function confirmDeleteFromModal()
    {
        if (empty($this->editItemId) || empty($this->originalSerialNumber)) {
            return;
        }

        $itemId = $this->editItemId;
        $serialNumber = $this->originalSerialNumber;
        $itemName = $this->editItemName;

        $this->closeSnModal();

        $this->confirmDeleteSN($itemId, $serialNumber);
    }

    public function clear()
    {
        $this->serial_number = '';
        // Keep idSelectedItem so consecutive scans target the same item
        $this->dispatch('refreshTable');
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
        <span
            class="text-xs text-[#64748b] font-semibold uppercase tracking-wide hover:text-[#4338CB] cursor-pointer">{{ $categoryRegion }}</span>
        <flux:icon.chevron-right class="w-3 h-3 text-[#64748b]" />
        <span class="text-sm text-[#4338CB] font-semibold uppercase tracking-wide">{{ $subRegion }}</span>
    </div>

    <div x-data="{
        barcodeScannerSection: true,
        ocrScannerSection: false,
        typeSerialNumberSection: false
    }">

        <div class="mb-4 flex w-full items-center gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-slate-800">
            <button
                class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-xs font-bold transition-colors sm:text-sm"
                :class="barcodeScannerSection ? 'bg-[#4338ca] text-white shadow-sm' :
                    'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white'"
                type="button"
                @click="barcodeScannerSection = true; ocrScannerSection = false; typeSerialNumberSection = false; $dispatch('tab-changed', 'barcode')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="h-4 w-4 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 4.5v15m3-15v15m3-15v15m3-15v15m3-15v15m3-15v15" />
                </svg>
                <span class="truncate">Barcode 1D/2D</span>
            </button>
            <button
                class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-xs font-bold transition-colors sm:text-sm"
                :class="ocrScannerSection ? 'bg-[#4338ca] text-white shadow-sm' :
                    'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white'"
                type="button"
                @click="barcodeScannerSection = false; ocrScannerSection = true; typeSerialNumberSection = false; $dispatch('tab-changed', 'ocr')">
                <x-flux::icon name="document-text" class="h-4 w-4 shrink-0" />
                <span class="truncate">OCR Teks</span>
            </button>
            <button
                class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-xs font-bold transition-colors sm:text-sm"
                :class="typeSerialNumberSection ? 'bg-[#4338ca] text-white shadow-sm' :
                    'text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white'"
                type="button"
                @click="barcodeScannerSection = false; ocrScannerSection = false; typeSerialNumberSection = true; $dispatch('tab-changed', 'type')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="h-4 w-4 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6.75A2.25 2.25 0 0 1 6 4.5h12a2.25 2.25 0 0 1 2.25 2.25v10.5A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75Z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7.5 8.25h.008v.008H7.5V8.25Zm4.5 0h.008v.008H12V8.25Zm4.5 0h.008v.008h-.008V8.25Zm-9 3h.008v.008H7.5v-.008Zm4.5 0h.008v.008H12v-.008Zm4.5 0h.008v.008h-.008v-.008Zm-9 3h.008v.008H7.5v-.008Zm4.5 0h.008v.008H12v-.008Zm4.5 0h.008v.008h-.008v-.008Z" />
                </svg>
                <span class="truncate">Ketik SN</span>
            </button>
        </div>

        <div x-show="barcodeScannerSection" class="flex flex-col w-full items-center justify-center">
            <div id="reader" wire:ignore class="w-full sm:w-100 h-72 sm:h-100 overflow-hidden rounded-lg border">
            </div>

            {{-- <div class="text-sm font-medium text-zinc-600">
                <span class="font-bold">
                    {{ $serial_number ?: '' }}
                </span>
            </div> --}}
        </div>

        <div x-show="ocrScannerSection" class="flex w-full items-center justify-center">
            <div class="w-full sm:w-100 h-72 sm:h-100 bg-green-100 rounded-lg flex items-center justify-center">
                <span class="text-2xl font-semibold text-accent-600">
                    OCR Placeholder
                </span>
            </div>
        </div>

        <div x-show="typeSerialNumberSection" x-cloak class="flex w-full items-center justify-center">
            <div
                class="flex flex-col w-full sm:w-100 h-72 sm:h-100 gap-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-slate-800">

                {{-- Header --}}
                <div class="flex items-center gap-2">
                    <flux:icon.pencil-square class="h-5 w-5 shrink-0 text-[#4338ca] dark:text-indigo-300" />
                    <h3 class="font-grotesk text-sm font-bold text-zinc-900 sm:text-base dark:text-zinc-100">
                        Input Manual Serial Number
                    </h3>
                </div>

                {{-- Selected item info --}}
                <div
                    class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-600 dark:bg-slate-700/50">
                    <flux:icon.cube class="h-4 w-4 shrink-0 text-zinc-400 dark:text-zinc-400" />
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            Item Dipilih
                        </p>
                        <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ $this->selectedItemName ?? 'Belum ada item dipilih' }}
                        </p>
                    </div>
                </div>

                {{-- Input --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300"
                        for="typed-serial-number">
                        Serial Number
                    </label>
                    <div class="relative mt-1.5">
                        <flux:icon.qr-code
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                        <input id="typed-serial-number" x-ref="typedInput" type="text" wire:model="serial_number"
                            autocomplete="off" placeholder="Ketik atau scan SN di sini"
                            class="w-full rounded-lg border border-zinc-300 py-2 pl-9 pr-9 font-jetbrains text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-600 dark:bg-slate-700 dark:text-zinc-100"
                            @keydown.enter.prevent="$wire.submitTypedSerial" />
                        <button type="button" title="Bersihkan input"
                            @click="$wire.serial_number = ''; $refs.typedInput.focus()"
                            class="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-slate-600 dark:hover:text-zinc-200">
                            <flux:icon.x-mark class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <p class="mt-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                        Tekan <kbd
                            class="rounded border border-zinc-300 bg-zinc-100 px-1 font-jetbrains text-[10px] dark:border-zinc-600 dark:bg-slate-700">Enter</kbd>
                        atau klik Simpan untuk memproses SN.
                    </p>
                </div>

                {{-- Actions --}}
                <div class="mt-auto flex items-center gap-2">
                    <button type="button" wire:click="submitTypedSerial"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#4338ca] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#3730a3]">
                        <flux:icon.check class="h-4 w-4" />
                        Simpan SN
                    </button>
                </div>
            </div>
        </div>

        {{-- Switch Compare SN Reference --}}
        <div class="mt-4">
            <div
                class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm transition-colors dark:border-zinc-700 dark:bg-slate-800">
                <flux:icon.shield-check class="h-6 w-6 shrink-0 text-[#4338ca] dark:text-indigo-300" />

                <div class="min-w-0 flex-1">
                    <p class="truncate font-grotesk text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        Validasi SN Database Admin
                    </p>
                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                        Cek kesesuaian referensi pengadaan otomatis
                    </p>
                </div>

                <button type="button" role="switch" wire:click="$toggle('compareSN')" :aria-checked="$wire.compareSN"
                    class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors duration-200"
                    :class="$wire.compareSN ? 'bg-[#4338ca]' : 'bg-zinc-300 dark:bg-slate-600'">
                    <span
                        class="inline-block h-7 w-7 transform rounded-full bg-white shadow-sm transition-transform duration-200"
                        :class="$wire.compareSN ? 'translate-x-6' : 'translate-x-0'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Inventory Card List (placed above the Items table) --}}
    <div class="space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
                <flux:icon.squares-2x2 class="h-5 w-5 shrink-0 text-[#4338ca] dark:text-indigo-300" />
                <h2 class="font-grotesk text-base font-bold text-zinc-900 sm:text-lg dark:text-zinc-100">
                    Daftar Inventaris Daerah
                </h2>
            </div>
            <span class="shrink-0 text-xs font-semibold text-zinc-500 sm:text-sm dark:text-zinc-400">
                Done: {{ $this->itemSerials->whereNotNull('serial_number')->count() }} / {{ $this->itemSerials->count() }} Items
            </span>
        </div>

        <p class="-mt-1 text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">
            Pilih atau tap item untuk menetapkan serial number hasil scan.
        </p>

        <div class="space-y-3 overflow-y-auto max-h-[calc(100vh-400px)] pr-1">
            @foreach ($this->itemSerials as $item)
                @php
                    $isSelected = $item->id == $this->idSelectedItem;
                    $hasSerial = !empty($item->serial_number);
                @endphp

                {{-- Card states: done scan (has SN) > selected > idle --}}
                <div wire:click="columnItemSelected({{ $item->id }})"
                    @if ($hasSerial) wire:dblclick="openSnModal({{ $item->id }}, @js($item->serial_number))"
                        title="Ketuk dua kali untuk edit / hapus Serial Number" @endif
                    @class([
                        'cursor-pointer rounded-2xl border p-4 transition-colors',
                        'border-2 border-[#4338ca] bg-indigo-50/70 dark:border-indigo-400 dark:bg-indigo-500/10' =>
                            $isSelected && !$hasSerial,
                        'border border-[#a7f3d0] bg-[#ecfdf5] dark:border-emerald-500/40 dark:bg-emerald-500/10' => $hasSerial,
                        'border border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-700 dark:bg-slate-800' =>
                            !$isSelected && !$hasSerial,
                    ])>

                    {{-- Header row --}}
                    <div class="flex items-center gap-3">
                        @if ($hasSerial)
                            <flux:icon.check-circle class="h-6 w-6 shrink-0 text-[#059669]" />
                        @else
                            <span @class([
                                'flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 bg-white dark:bg-transparent',
                                'border-[#4338ca]' => $isSelected,
                                'border-zinc-300 dark:border-zinc-500' => !$isSelected,
                            ])>
                                @if ($isSelected)
                                    <span class="h-3 w-3 rounded-full bg-[#4338ca]"></span>
                                @endif
                            </span>
                        @endif

                        <h3
                            class="min-w-0 flex-1 truncate font-grotesk text-base font-bold text-zinc-900 sm:text-lg dark:text-zinc-100">
                            {{ $item->name }}
                        </h3>

                        @if ($hasSerial)
                            <button type="button"
                                wire:click.stop="openSnModal({{ $item->id }}, @js($item->serial_number))"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-slate-700 dark:text-zinc-300"
                                aria-label="Edit atau hapus Serial Number">
                                <flux:icon.pencil class="h-4 w-4" />
                            </button>
                        @endif
                    </div>

                    {{-- Badge + category --}}
                    <div class="mt-2 pl-9">
                        @if ($hasSerial)
                            <span
                                class="inline-flex items-center gap-1 rounded-full border border-[#a7f3d0] bg-[#ecfdf5] px-2 py-0.5 text-[11px] font-bold tracking-wide text-[#059669]">
                                <flux:icon.check-badge class="h-3.5 w-3.5" />
                                Verified Match
                            </span>
                        @elseif ($isSelected)
                            <span
                                class="inline-flex items-center rounded-md bg-[#4338ca] px-2 py-0.5 text-[11px] font-bold tracking-wide text-white">
                                Sedang Di-Scan
                            </span>
                        @endif

                        <p class="mt-1 truncate text-xs text-zinc-500 sm:text-sm dark:text-zinc-400">
                            Kategori: {{ $subRegion }}
                        </p>
                    </div>

                    {{-- SN chip / scan placeholder --}}
                    @if ($hasSerial)
                        <div
                            class="mt-3 flex items-center gap-3 rounded-xl border border-[#a7f3d0] bg-white/70 px-3 py-2.5 dark:bg-slate-800/60">
                            <flux:icon.qr-code class="h-6 w-6 shrink-0 text-[#059669]" />
                            <span
                                class="min-w-0 flex-1 truncate font-jetbrains text-sm font-semibold text-zinc-800 sm:text-base dark:text-zinc-100">
                                {{ $item->serial_number }}
                            </span>
                            <span
                                class="inline-flex shrink-0 items-center gap-1 rounded-full border border-[#a7f3d0] bg-[#ecfdf5] px-2 py-0.5 text-[11px] font-bold text-[#059669]">
                                <flux:icon.check-badge class="h-3.5 w-3.5" />
                                Verified Match
                            </span>
                        </div>
                    @elseif ($isSelected)
                        <div
                            class="mt-3 flex items-center gap-2 rounded-xl border border-zinc-200 bg-white p-2 dark:border-zinc-600 dark:bg-slate-800">
                            <flux:icon.magnifying-glass class="h-5 w-5 shrink-0 text-[#4338ca] dark:text-indigo-300" />
                            <div
                                class="min-w-0 flex-1 rounded-lg border border-dashed border-zinc-300 px-3 py-2 text-sm text-zinc-400 dark:border-zinc-600 dark:text-zinc-500">
                                Menunggu scan barcode...
                            </div>
                            <button type="button"
                                class="flex shrink-0 items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-[#4338ca] transition hover:bg-indigo-100 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-300">
                                <flux:icon.camera class="h-4 w-4" />
                                Foto
                            </button>
                        </div>
                    @endif

                    {{-- Footer --}}
                    <div class="mt-3 flex items-center justify-between gap-2 text-xs font-semibold sm:text-sm">
                        @if ($hasSerial)
                            <span class="text-zinc-500 dark:text-zinc-400">
                                Tercatat: {{ Carbon::parse($item->created_at)->format('H:i') }} WIB
                            </span>
                            <span class="text-zinc-500 dark:text-zinc-400">
                                Metode: Barcode Optik
                            </span>
                        @elseif ($isSelected)
                            <span class="text-zinc-500 dark:text-zinc-400"></span>
                            <span class="text-[#4338ca] dark:text-indigo-300">
                                Target Utama
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- SN Detail Modal: Cancel / Edit / Delete --}}
    <div x-data="{ show: @entangle('showSnModal') }" x-show="show" x-cloak
        class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="show = false" class="w-full max-w-md rounded-xl bg-white shadow-xl dark:bg-slate-800"
            @click.stop>

            {{-- Header --}}
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <h2 class="font-grotesk text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    Detail Serial Number
                </h2>
                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $editItemName }}
                </p>
            </div>

            {{-- Body --}}
            <div class="px-6 py-6">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300" for="edit-serial-number">
                    Serial Number
                </label>
                <input id="edit-serial-number" type="text" wire:model="editSerialNumber"
                    class="mt-2 w-full rounded-lg border border-zinc-300 px-4 py-2 font-jetbrains text-sm text-zinc-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-zinc-600 dark:bg-slate-700 dark:text-zinc-100"
                    placeholder="Masukkan Serial Number" />
            </div>

            {{-- Actions --}}
            <div
                class="flex items-center justify-between gap-2 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <button type="button" wire:click="confirmDeleteFromModal"
                    class="flex items-center gap-1.5 rounded-lg bg-[#e11d48] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#be123c]">
                    <flux:icon.trash class="h-4 w-4" />
                    Delete
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" wire:click="closeSnModal"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button type="button" wire:click="updateSerialNumber"
                        class="rounded-lg bg-[#4338ca] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#3730a3]">
                        Edit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <livewire:components.modal.dialog />
</div>

@push('styles')
    <style>
        /* Memaksa video menyesuaikan div tanpa ruang kosong (black bars) */
        #reader video {
            object-fit: cover !important;
            width: 100% !important;
            height: 100% !important;
        }
    </style>
@endpush

@script
    <script>
        if (typeof window.isScanning === 'undefined') {
            window.isScanning = false;
            window.myScanner = null;
            window.isCooldown = false;
        }

        function startScanner() {
            if (window.isScanning || !window.Html5Qrcode) return;

            window.Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    // console.log("📷 [CAMERAS FOUND]:", devices);
                    // Kirim ID kamera belakang utama jika terdeteksi, atau biarkan fallback
                    const backCamera = devices.find(device =>
                        /back|rear|environment/i.test(device.label)
                    );
                    const cameraId = backCamera ? backCamera.id : devices[0].id;

                    initializeScanner(cameraId);
                } else {
                    // console.error("❌ Kamera tidak ditemukan.");
                }
            }).catch(err => {
                // console.error("❌ Error akses kamera: ", err);
                // Fallback panggil tanpa cameraId
                initializeScanner(null);
            });
        }

        function initializeScanner(cameraId = null) {
            const readerElement = document.getElementById("reader");

            if (readerElement) {
                readerElement.innerHTML = '';
                readerElement.classList.add('transition-colors', 'duration-300');
            }

            // console.log("🔍 [SCANNER STATUS]: Initializing Html5Qrcode instance...");
            window.myScanner = new window.Html5Qrcode("reader");

            let scannerConfig = {
                fps: 15,
                qrbox: function(viewfinderWidth, viewfinderHeight) {
                    return {
                        width: Math.floor(viewfinderWidth * 0.85),
                        height: Math.floor(viewfinderHeight * 0.55)
                    };
                },
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                }
            };

            // Gunakan Camera ID spesifik jika ada, atau fallback ke objek facingMode yang aman (tanpa `exact`)
            const cameraConfig = cameraId ?
                cameraId :
                {
                    facingMode: "environment"
                };

            // console.log("📷 [SCANNER CONFIG]: Starting camera with target:", cameraConfig);

            window.myScanner.start(
                cameraConfig,
                scannerConfig,
                (decodedText, decodedResult) => {
                    if (window.isCooldown) {
                        // console.warn("⏳ [SCAN WAITING]: Barcode terdeteksi ('" + decodedText + "'), tapi scanner sedang COOLDOWN. Discarded.");
                        return;
                    }

                    // --- SCAN RESULT SUCCESS ---
                    // console.group("✅ [SCAN RESULT SUCCESS]");
                    // console.log("Raw Decoded Text :", decodedText);
                    // console.log("Decoded Details   :", decodedResult);
                    // console.log("Target Item ID    :", $wire.idSelectedItem);
                    // console.groupEnd();

                    $wire.barcodeDetected(decodedText);

                    window.isCooldown = true;
                    // console.log("⏸️ [SCANNER COOLDOWN]: Cooldown started (3 seconds)...");

                    readerElement.classList.remove('border-zinc-200');
                    readerElement.classList.add('border-4', 'border-green-500');

                    setTimeout(() => {
                        readerElement.classList.remove('border-green-500');
                        readerElement.classList.add('border-red-500');
                    }, 500);

                    setTimeout(() => {
                        readerElement.classList.remove('border-4', 'border-red-500');
                        window.isCooldown = false;
                        // console.log("▶️ [SCANNER READY]: Cooldown finished. Ready for next scan!");
                    }, 3000);

                },
                (errorMessage) => {
                    // Waiting frame continuous loop
                }
            ).then(() => {
                window.isScanning = true;
                // console.log("🚀 [SCANNER STATUS]: Camera stream started successfully. WAITING FOR BARCODE...");
            }).catch((err) => {
                // console.error("❌ [SCANNER ERROR]: Failed to start camera with primary config:", err);

                // FALLBACK JIKA MASIH ERROR: Pakai Facing Mode Paling Sederhana
                if (cameraId) {
                    // console.warn("⚠️ [SCANNER FALLBACK]: Retrying with simple facingMode 'environment'...");
                    window.myScanner.start({
                            facingMode: "environment"
                        },
                        scannerConfig,
                        /* callback success & error disamakan */
                    ).catch(fallbackErr => console.error("❌ [SCANNER FATAL]: Fallback camera failed:",
                        fallbackErr));
                }
            });
        }

        function stopScanner() {
            if (window.myScanner && window.isScanning) {
                window.myScanner.stop().then(() => {
                    window.myScanner.clear();
                    window.isScanning = false;
                    window.isCooldown = false;

                    const readerElement = document.getElementById("reader");
                    if (readerElement) {
                        readerElement.innerHTML = '';
                        // Reset class warna ke awal jika dihentikan di tengah cooldown
                        readerElement.classList.remove('border-4', 'border-green-500', 'border-red-500');
                    }
                }).catch(err => {
                    // console.error("Gagal stop scanner:", err);
                    window.isScanning = false;
                });
            }
        }

        document.addEventListener('livewire:initialized', startScanner);

        // wire:navigate does not re-fire livewire:initialized on subsequent visits
        document.addEventListener('livewire:navigated', () => {
            startScanner();
        });

        document.addEventListener('livewire:navigating', () => {
            stopScanner();
        });

        window.addEventListener('tab-changed', (e) => {
            if (e.detail === 'barcode') {
                setTimeout(() => {
                    startScanner();
                }, 100);
            } else {
                stopScanner();
            }
        });
    </script>
@endscript
