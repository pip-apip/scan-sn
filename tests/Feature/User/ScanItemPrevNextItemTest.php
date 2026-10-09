<?php

use App\Models\Item;
use App\Models\Region;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    session(['guest_uuid' => 'test-guest-uuid']);
    config(['broadcasting.default' => 'null']);

    User::factory()->create(['id' => 1]);

    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 1', 'project_id' => 72]);
});

test('next and prev item buttons move the selection and refocus the manual form', function () {
    $itemA = Item::create(['name' => 'Item A', 'project_id' => 72]);
    $itemB = Item::create(['name' => 'Item B', 'project_id' => 72]);

    $page = Livewire::test('user.scan-item', ['categoryRegion' => 'Area A', 'subRegion' => 'Region 1'])
        ->assertSet('idSelectedItem', $itemA->id);

    // SN yang sedang diketik tidak boleh ikut pindah ke item lain
    $page->set('serial_number', 'SN-DRAFT')
        ->call('nextItem')
        ->assertSet('idSelectedItem', $itemB->id)
        ->assertSet('serial_number', '')
        ->assertDispatched('focus-manual-sn');

    $page->call('prevItem')
        ->assertSet('idSelectedItem', $itemA->id)
        ->assertDispatched('focus-manual-sn');
});

test('prev and next item do not loop at the ends', function () {
    $itemA = Item::create(['name' => 'Item A', 'project_id' => 72]);
    $itemB = Item::create(['name' => 'Item B', 'project_id' => 72]);

    $page = Livewire::test('user.scan-item', ['categoryRegion' => 'Area A', 'subRegion' => 'Region 1'])
        ->assertSet('idSelectedItem', $itemA->id);

    // Di item pertama: prev tidak bergerak dan tidak ada event focus
    $page->call('prevItem')
        ->assertSet('idSelectedItem', $itemA->id)
        ->assertNotDispatched('focus-manual-sn');

    $page->call('nextItem')->assertSet('idSelectedItem', $itemB->id);

    // Di item terakhir: next tidak bergerak dan tidak ada event focus
    $page->call('nextItem')
        ->assertSet('idSelectedItem', $itemB->id)
        ->assertNotDispatched('focus-manual-sn');
});

test('typing form renders prev next item buttons with position indicator', function () {
    Item::create(['name' => 'Item A', 'project_id' => 72]);
    Item::create(['name' => 'Item B', 'project_id' => 72]);
    Item::create(['name' => 'Item C', 'project_id' => 72]);

    $page = Livewire::test('user.scan-item', ['categoryRegion' => 'Area A', 'subRegion' => 'Region 1']);
    $html = $page->html();

    // Posisi awal (item pertama): prev disabled (tanpa wire:click), next aktif, indikator 1/3
    expect($html)->toContain('wire:click="nextItem"')
        ->toContain('1/3');
    expect($html)->not->toContain('wire:click="prevItem"');

    // Setelah pindah ke item kedua: tombol prev muncul, indikator 2/3
    $htmlAfter = $page->call('nextItem')->html();

    expect($htmlAfter)->toContain('wire:click="prevItem"')
        ->toContain('wire:click="nextItem"')
        ->toContain('2/3');
});
