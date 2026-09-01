<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use App\Models\Item;
use App\Models\Item_sn_references;

new class extends Component {
    use WithFileUploads;

    public $file;
    public $item_id;

    public array $preview = [];

    public int $totalRows = 0;
    public int $validRows = 0;
    public int $invalidRows = 0;

    public bool $importing = false;
    public int $progress = 0;
    public int $processedRows = 0;

    public function getItemsProperty()
    {
        return Item::all();
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $this->reset(['preview', 'totalRows', 'validRows', 'invalidRows']);

        $collection = Excel::toCollection(null, $this->file);

        /** @var \Illuminate\Support\Collection|null $rows */
        $rows = $collection->first();

        if (!$rows || $rows->isEmpty()) {
            return;
        }

        // Get headers from first row
        $headers = collect($rows->shift())->map(fn($header) => strtolower(trim($header)));

        // Expected columns
        $requiredColumns = ['serial number'];

        // Check if all required columns are present
        $missingColumns = array_diff($requiredColumns, $headers->toArray());

        if (!empty($missingColumns)) {
            $this->addError('file', "Invalid template. Missing columns: " . implode(', ', $missingColumns));
            return;
        }

        $this->totalRows = $rows->count();

        foreach ($rows as $index => $values) {
            $row = $headers->combine($values);

            $errors = [];

            if (blank($row['serial number'] ?? null)) {
                $errors[] = [
                    'column' => 'Serial Number',
                    'message' => 'Serial Number is required.',
                ];
            }

            $this->preview[] = [
                'row' => $index + 2, // +2 because we removed the header row and Excel rows start at 1
                'serial_number' => $row['serial number'] ?? null,
                'valid' => empty($errors),
                'errors' => $errors,
            ];
        }

        $this->validRows = count(array_filter($this->preview, fn($row) => $row['valid']));
        $this->invalidRows = count(array_filter($this->preview, fn($row) => !$row['valid']));
    }

    public function import()
    {
        if($this->item_id == null){
            $this->addError('item_id', 'Please select an item before importing.');
            return;
        }

        if ($this->invalidRows > 0) {
            $this->addError('file', 'Please fix invalid rows before importing.');

            return;
        }

        $this->importing = true;

        $total = count($this->preview);

        foreach ($this->preview as $index => $row) {
            if (!$row['valid']) {
                continue;
            }

            Item_sn_references::create([
                'item_id' => $this->item_id,
                'serial_number' => $row['serial_number'],
                'is_used' => 0,
            ]);

            $this->processedRows = $index + 1;
            $this->progress = intval((($index + 1) / $total) * 100);
        }

        $this->importing = false;
        Flux::toast('Import completed.', null, 3000, 'success');
        $this->showImportSNModal = false;
        $this->clear();

        $this->dispatch('refreshTable')->to('admin.sn.sn-table');
    }

    public function clear()
    {
        $this->reset(['file', 'preview', 'totalRows', 'validRows', 'invalidRows', 'importing', 'progress', 'processedRows']);
    }


}; ?>

<div x-show="showImportSNModal" x-transition
    x-on:import-success.window=" console.log('received');
        showImportSNModal = false
    "
    @keydown.escape.window="showImportSNModal = false"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display: none;">
    <div @click.outside="showImportSNModal = false" class="w-full max-w-6xl rounded-xl bg-white shadow-xl">
        <div class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold">Import Serial Number</h2>
        </div>

        <div class="space-y-5 p-6">
            <div>
                <label class="mb-2 block text-sm font-medium">Select Item</label>
                <select class="block w-full rounded-lg border border-zinc-300 p-2" wire:model.live="item_id">
                    <option>Select Item</option>
                    @foreach ($this->items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

            @if (empty($preview))
                <div>
                    <div>
                        <label class="mb-2 block text-sm font-medium">
                            Upload Excel File
                        </label>

                        <input type="file" wire:model.live="file" accept=".xlsx,.xls,.csv"
                            class="block w-full rounded-lg border border-zinc-300 p-2
                                file:mr-4
                                file:rounded-md
                                file:border-0
                                file:bg-indigo-600
                                file:px-4
                                file:py-2
                                file:text-white">

                        <div wire:loading wire:target="file" class="mt-2 text-sm text-indigo-600">
                            Reading Excel...
                        </div>

                        @error('file')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <div class="rounded-lg bg-zinc-50 p-4 text-sm text-zinc-600">
                        <p class="font-medium">Supported formats</p>

                        <ul class="mt-2 list-disc pl-5">
                            <li>.xlsx</li>
                            <li>.xls</li>
                            <li>.csv</li>
                        </ul>
                    </div>

                </div>
            @endif

            @if ($totalRows)
                <div class="grid grid-cols-3 gap-4">

                    <div class="rounded-lg bg-zinc-100 p-4 text-center">
                        <div class="text-2xl font-bold">
                            {{ $totalRows }}
                        </div>

                        <div>Total</div>
                    </div>

                    <div class="rounded-lg bg-green-100 p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">
                            {{ $validRows }}
                        </div>

                        <div>Valid</div>
                    </div>

                    <div class="rounded-lg bg-red-100 p-4 text-center">
                        <div class="text-2xl font-bold text-red-600">
                            {{ $invalidRows }}
                        </div>

                        <div>Invalid</div>
                    </div>

                </div>
            @endif

            @if (count($preview))
                <div class="max-h-76 overflow-auto rounded-lg border">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-100">
                            <tr>
                                <th class="px-3 py-2">Row</th>
                                <th class="px-3 py-2">Serial Number</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview as $row)
                                <tr class="{{ $row['valid'] ? '' : 'bg-red-50' }}">

                                    <td class="border px-3 py-2">
                                        {{ $row['row'] - 1 }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ $row['serial_number'] }}
                                    </td>

                                    <td class="border px-3 py-2">

                                        @if ($row['valid'])
                                            <span class="text-green-600">
                                                ✔ Valid
                                            </span>
                                        @else
                                            @foreach ($row['errors'] as $error)
                                                <div class="text-red-600">
                                                    {{ $error['column'] }} :
                                                    {{ $error['message'] }}
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- <div class="rounded-lg bg-zinc-100 p-4">

                </div> --}}
            @endif
        </div>

        <div class="grid grid-cols-6 items-center gap-2 border-t px-6 py-4">
            @if ($importing)
                <div class="col-span-5">
                    <h3 class="text-sm font-semibold">
                        Importing Serial Number...
                    </h3>

                    <p class="mt-1 text-xs text-zinc-500">
                        Please don't close this window.
                    </p>

                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-200">

                        <div class="h-full bg-indigo-600 transition-all duration-300"
                            style="width: {{ $progress }}%;">
                        </div>

                    </div>

                    <div class="mt-1 flex justify-between text-xs">

                        <span>
                            {{ $processedRows }}
                            /
                            {{ $totalRows }}
                            records
                        </span>

                        <span>
                            {{ $progress }}%
                        </span>

                    </div>
                </div>
            @else
                <div class="col-span-5">
                </div>
            @endif

            <div class="col-span-1">
                <button @click="showImportSNModal = false" x-ref="cancelButton" type="button"
                    class="rounded-lg border border-zinc-300 px-4 py-2 hover:bg-zinc-100" wire:click="clear">
                    Cancel
                </button>

                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" wire:click="import"
                    type="button">
                    Import
                </button>
            </div>
        </div>
    </div>
</div>
