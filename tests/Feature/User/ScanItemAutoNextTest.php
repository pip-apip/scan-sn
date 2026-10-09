<?php

use App\Models\Item;
use App\Models\Mapping_lists;
use App\Models\Region;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    session(['guest_uuid' => 'test-guest-uuid']);
    config(['broadcasting.default' => 'null']);

    // saveMapping memakai user_id = 1 (FK mapping_lists -> users) — buat
    // eksplisit id 1 agar stabil walau auto-increment tidak ikut rollback
    User::factory()->create(['id' => 1]);

    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 1', 'project_id' => 72]);
});

test('saving a scan auto selects the next unscanned item and focuses the manual SN form', function () {
    $itemA = Item::create(['name' => 'Item A', 'project_id' => 72]);
    $itemB = Item::create(['name' => 'Item B', 'project_id' => 72]);
    $itemC = Item::create(['name' => 'Item C', 'project_id' => 72]);

    $page = Livewire::test('user.scan-item', ['categoryRegion' => 'Area A', 'subRegion' => 'Region 1'])
        ->assertSet('idSelectedItem', $itemA->id);

    // Save SN item A → auto pindah ke item B + event focus
    $page->set('serial_number', 'SN-A')
        ->call('saveMapping')
        ->assertSet('idSelectedItem', $itemB->id)
        ->assertDispatched('focus-manual-sn');

    expect(Mapping_lists::where('item_id', $itemA->id)->where('region_id', Region::first()->id)->count())->toBe(1);

    // Save SN item B → auto pindah ke item C + event focus
    $page->set('serial_number', 'SN-B')
        ->call('saveMapping')
        ->assertSet('idSelectedItem', $itemC->id)
        ->assertDispatched('focus-manual-sn');

    // Save SN item C → semua item selesai, tidak ada auto-next/focus lagi
    $page->set('serial_number', 'SN-C')
        ->call('saveMapping')
        ->assertSet('idSelectedItem', $itemC->id)
        ->assertNotDispatched('focus-manual-sn');

    expect(Mapping_lists::count())->toBe(3);
});

test('auto next skips items that already have a serial number', function () {
    $itemA = Item::create(['name' => 'Item A', 'project_id' => 72]);
    $itemB = Item::create(['name' => 'Item B', 'project_id' => 72]);
    $itemC = Item::create(['name' => 'Item C', 'project_id' => 72]);

    // Item B sudah punya SN
    Mapping_lists::create([
        'item_id' => $itemB->id,
        'region_id' => Region::first()->id,
        'serial_number' => 'SN-B-EXISTING',
        'project_id' => 72,
        'user_id' => 1,
    ]);

    $page = Livewire::test('user.scan-item', ['categoryRegion' => 'Area A', 'subRegion' => 'Region 1'])
        ->assertSet('idSelectedItem', $itemA->id);

    $page->set('serial_number', 'SN-A')
        ->call('saveMapping')
        ->assertSet('idSelectedItem', $itemC->id) // B dilewati karena sudah ada SN
        ->assertDispatched('focus-manual-sn');
});

test('auto next falls back to an earlier unscanned item when the list ends', function () {
    Item::create(['name' => 'Item A', 'project_id' => 72]);
    Item::create(['name' => 'Item B', 'project_id' => 72]);
    $itemC = Item::create(['name' => 'Item C', 'project_id' => 72]);

    $page = Livewire::test('user.scan-item', ['categoryRegion' => 'Area A', 'subRegion' => 'Region 1'])
        ->call('columnItemSelected', $itemC->id);

    $page->set('serial_number', 'SN-C')
        ->call('saveMapping')
        ->assertSet('idSelectedItem', Item::where('name', 'Item A')->value('id'))
        ->assertDispatched('focus-manual-sn');
});

test('page listens for the focus event to switch tab and focus the manual SN input', function () {
    Item::create(['name' => 'Item A', 'project_id' => 72]);

    $html = Livewire::test('user.scan-item', ['categoryRegion' => 'Area A', 'subRegion' => 'Region 1'])->html();

    expect($html)->toContain('@focus-manual-sn.window')
        ->toContain('typeSerialNumberSection = true')
        ->toContain('$refs.typedInput && $refs.typedInput.focus()');
});
