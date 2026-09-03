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

new #[Layout('layouts.app-mobile')] class extends Component {
    public string $categoryRegion;
    public string $subRegion;
    public string $subRegionId;
    public string $serial_number = '';
    public string $idSelectedItem = '';
    public bool $compareSN = true;
    public string $deleteItemId = '';
    public string $deleteSerialNumber = '';

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
        $items = Item::select('items.id', 'items.name', 'mapping_lists.serial_number')
            ->leftJoin('mapping_lists', function ($join) {
                $join->on('mapping_lists.item_id', '=', 'items.id')->where(
                    'mapping_lists.region_id',
                    '=',
                    DB::raw("
                        (
                            SELECT id
                            FROM regions
                            WHERE name_region = '{$this->subRegion}'
                            LIMIT 1
                        )
                    "),
                );
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
            $this->saveMapping();
            return;
        }

        if ($action === 'delete-mapping') {
            $this->deleteMapping();
            return;
        }
    }

    public function saveMapping()
    {
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

    public function clear()
    {
        $this->serial_number = '';
        $this->idSelectedItem = '';
        $this->dispatch('refreshTable');
    }
};
?>

<div class="flex flex-col w-full space-y-4">

    <div wire:poll.30s="keepAlive"></div>

    <div class="hidden sm:flex gap-2">
        <h1 class="text-lg font-semibold text-zinc-700/50 hover:text-zinc-700 cursor-pointer" wire:click="backToRegion()">
            {{ __('Regions') }}
        </h1>
        <span class="text-lg font-semibold text-zinc-700/50">
            /
        </span>
        <h1 class="text-lg font-semibold text-zinc-700/50 hover:text-zinc-700 cursor-pointer whitespace-nowrap"
            wire:click="backToSubRegion()">
            {{ $this->categoryRegion }}
        </h1>
        <span class="text-lg font-semibold text-zinc-700/50">
            /
        </span>
        <h1 class="text-lg font-semibold text-zinc-700 whitespace-nowrap" wire:click="backToSubRegion()">
            {{ $this->subRegion }}
        </h1>
    </div>

    <div class="flex flex-row items-center justify-center gap-2 sm:hidden">
        <div class="flex gap-2">
            <button type="button" wire:click="backToSubRegion()"
                class="flex h-8 w-8 items-center justify-center rounded-full text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900"
                aria-label="Go back">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </div>
        <div class="w-82">
            <h1 class="text-md font-semibold text-zinc-700">
                {{ $this->subRegion }}
            </h1>
        </div>
    </div>

    <div x-data="{
        barcodeScannerSection: true,
        ocrScannerSection: false,
        typeSerialNumberSection: false
    }">

        <div
            class="flex w-auto mb-4 items-center justify-center border-2 border-zinc-200 rounded-lg bg-white px-1 py-1">
            <div class="flex items-center gap-2 justify-center">
                <button
                    class="flex flex-col items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold hover:bg-indigo-700 hover:text-white"
                    :class="{ 'bg-indigo-700 text-white': barcodeScannerSection }" type="button"
                    @click="barcodeScannerSection = true; ocrScannerSection = false; typeSerialNumberSection = false">
                    <x-flux::icon name="qr-code" class="h-4 w-4" />
                    Barcode Scanner
                </button>
                <button
                    class="flex flex-col items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold hover:bg-indigo-700 hover:text-white"
                    :class="{ 'bg-indigo-700 text-white': ocrScannerSection }" type="button"
                    @click="barcodeScannerSection = false; ocrScannerSection = true; typeSerialNumberSection = false">
                    <x-flux::icon name="document-text" class="h-4 w-4" />
                    OCR Scanner
                </button>
                <button
                    class="flex flex-col items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold hover:bg-indigo-700 hover:text-white"
                    :class="{ 'bg-indigo-700 text-white': typeSerialNumberSection }" type="button"
                    @click="barcodeScannerSection = false; ocrScannerSection = false; typeSerialNumberSection = true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    Type Serial Number
                </button>
            </div>
        </div>

        <div x-show="barcodeScannerSection" class="flex flex-col w-full items-center justify-center">
            <div id="reader" wire:ignore class="w-72 sm:w-100 h-72 sm:h-100 overflow-hidden rounded-lg border">
            </div>

            {{-- <div class="text-sm font-medium text-zinc-600">
                <span class="font-bold">
                    {{ $serial_number ?: '' }}
                </span>
            </div> --}}
        </div>

        <div x-show="ocrScannerSection" class="flex w-full items-center justify-center">
            <div class="w-72 sm:w-100 h-72 sm:h-100 bg-green-100 rounded-lg flex items-center justify-center">
                <span class="text-2xl font-semibold text-accent-600">
                    OCR Placeholder
                </span>
            </div>
        </div>

        <div x-show="typeSerialNumberSection" class="flex w-full items-center justify-center">
            <div
                class="flex flex-col w-72 sm:w-100 h-72 sm:h-100 bg-white rounded-lg px-4 gap-4 items-center justify-center">
                <label class="block text-sm font-medium">
                    Item Serial Number
                </label>

                <input type="text" wire:model.live="serial_number" placeholder="Enter Item Serial Number"
                    class="w-full rounded-lg border border-zinc-300 px-4 py-2 focus:border-indigo-500 focus:outline-none">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" type="submit">
                    Save
                </button>
            </div>
        </div>

        {{-- Switch Compare SN Reference --}}
        <div class="flex item-center justify-around mt-4">
            <flux:field variant="inline">
                <flux:label>Compare dengan Serial Number Referensi</flux:label>

                <flux:switch wire:model.live="compareSN" />

                {{-- <flux:error name="notifications" /> --}}
            </flux:field>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-zinc-50">
                    <tr class="border-b border-zinc-200">
                        <th
                            class="w-12 px-2 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">

                        </th>
                        <th
                            class="pl-2 pr-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Item Name
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            SN
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100">
                    @foreach ($this->itemSerials as $item)
                        <tr wire:click="columnItemSelected({{ $item->id }})"
                            @if (!empty($item->serial_number)) wire:dblclick="confirmDeleteSN({{ $item->id }}, @js($item->serial_number))" @endif
                            @class([
                                'cursor-pointer hover:bg-zinc-50' => $item->id != $this->idSelectedItem,
                                'cursor-pointer bg-zinc-200' => $item->id == $this->idSelectedItem,
                            ])>
                            <td class="w-12 px-4 py-4">
                                @if ($item->id == $this->idSelectedItem)
                                    <div class="h-2 w-2 rounded-full bg-indigo-600"></div>
                                @endif
                            </td>

                            <td class="pl-2 pr-6 py-4">
                                {{ $item->name }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $item->serial_number ?? 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
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

        /* Menghilangkan border default dari library jika ada */
        #reader {
            border: none !important;
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
                    initializeScanner();
                } else {
                    console.error("Kamera tidak ditemukan.");
                }
            }).catch(err => {
                console.error("Error akses kamera: ", err);
            });
        }

        function initializeScanner() {
            const readerElement = document.getElementById("reader");

            if (readerElement) {
                readerElement.innerHTML = '';
                // Pastikan class animasi transisi ada agar perubahan warna halus
                readerElement.classList.add('transition-colors', 'duration-300');
            }

            window.myScanner = new window.Html5Qrcode("reader");
            const isMobile = window.innerWidth < 640;

            let scannerConfig = {
                fps: 10,
            };

            if (!isMobile) {
                scannerConfig.qrbox = {
                    width: 350,
                    height: 150
                };
            } else {
                scannerConfig.qrbox = {
                    width: 250,
                    height: 100
                };
            }

            window.myScanner.start({
                    facingMode: "environment"
                },
                scannerConfig,
                (decodedText) => {
                    if (window.isCooldown) return;

                    // 1. Kirim data ke Livewire
                    $wire.barcodeDetected(decodedText);

                    // 2. Aktifkan mode jeda
                    window.isCooldown = true;

                    // --- LOGIKA PERUBAHAN WARNA VISUAL ---

                    // Ubah border jadi Hijau Tebal (Berhasil Scan)
                    readerElement.classList.remove('border-zinc-200'); // Hapus border default jika ada
                    readerElement.classList.add('border-4', 'border-green-500');

                    // Setelah 500ms, ubah warna dari Hijau menjadi Merah (Sedang Cooldown)
                    setTimeout(() => {
                        readerElement.classList.remove('border-green-500');
                        readerElement.classList.add('border-red-500');
                    }, 500);

                    // Setelah 3000ms (3 detik), kembalikan ke warna normal & matikan cooldown
                    setTimeout(() => {
                        readerElement.classList.remove('border-4', 'border-red-500');
                        // Jika Anda memiliki warna border default (misal border biasa), tambahkan lagi
                        // readerElement.classList.add('border-zinc-200');

                        window.isCooldown = false;
                    }, 3000);

                },
                (errorMessage) => {
                    // Abaikan error log frame kosong
                }
            ).then(() => {
                window.isScanning = true;
            }).catch((err) => {
                console.error("Gagal menjalankan kamera:", err);
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
                    console.error("Gagal stop scanner:", err);
                    window.isScanning = false;
                });
            }
        }

        document.addEventListener('livewire:initialized', startScanner);

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
