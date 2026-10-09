<?php

use App\Models\Item;
use App\Models\Mapping_lists;
use App\Models\Region;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['broadcasting.default' => 'null']);
    $this->user = User::factory()->create();
});

test('total_region_done_mapping counts only satker that scanned all items', function () {
    $item1 = Item::create(['name' => 'Laptop', 'project_id' => 72]);
    $item2 = Item::create(['name' => 'Printer', 'project_id' => 72]);

    $done = Region::create(['category_region' => 'Area A', 'name_region' => 'Region Done', 'project_id' => 72]);
    $partial = Region::create(['category_region' => 'Area A', 'name_region' => 'Region Partial', 'project_id' => 72]);
    $empty = Region::create(['category_region' => 'Area A', 'name_region' => 'Region Empty', 'project_id' => 72]);

    // Region Done: seluruh item project sudah di-scan
    Mapping_lists::create(['item_id' => $item1->id, 'region_id' => $done->id, 'serial_number' => 'SN-D1', 'project_id' => 72, 'user_id' => $this->user->id]);
    Mapping_lists::create(['item_id' => $item2->id, 'region_id' => $done->id, 'serial_number' => 'SN-D2', 'project_id' => 72, 'user_id' => $this->user->id]);

    // Region Partial: baru 1 dari 2 item
    Mapping_lists::create(['item_id' => $item1->id, 'region_id' => $partial->id, 'serial_number' => 'SN-P1', 'project_id' => 72, 'user_id' => $this->user->id]);

    // Region Empty: belum ada mapping sama sekali

    $regions = Livewire::test('user.regions', ['guestUuid' => 'test-guest-uuid'])->get('regions');
    $areaA = collect($regions->items())->firstWhere('category_region', 'Area A');

    expect($areaA)->not->toBeNull()
        ->and((int) $areaA->count_total_region)->toBe(3)
        ->and((int) $areaA->total_region_done_mapping)->toBe(1);
});

test('total_region_done_mapping no longer counts raw mapping rows', function () {
    // Banyaknya baris mapping tidak boleh dipakai sebagai jumlah satker done
    $item = Item::create(['name' => 'Laptop', 'project_id' => 72]);

    $region = Region::create(['category_region' => 'Area B', 'name_region' => 'Region One', 'project_id' => 72]);

    // Satu satker dengan beberapa mapping tetap dihitung HANYA 1 satker
    Mapping_lists::create(['item_id' => $item->id, 'region_id' => $region->id, 'serial_number' => 'SN-1', 'project_id' => 72, 'user_id' => $this->user->id]);

    $regions = Livewire::test('user.regions', ['guestUuid' => 'test-guest-uuid'])->get('regions');
    $areaB = collect($regions->items())->firstWhere('category_region', 'Area B');

    expect((int) $areaB->count_total_region)->toBe(1)
        ->and((int) $areaB->total_region_done_mapping)->toBe(1);
});

test('satker is not counted as done when its project has no items', function () {
    Region::create(['category_region' => 'Area Kosong', 'name_region' => 'Region X', 'project_id' => 999]);

    $regions = Livewire::test('user.regions', ['guestUuid' => 'test-guest-uuid'])->get('regions');
    $area = collect($regions->items())->firstWhere('category_region', 'Area Kosong');

    expect((int) $area->count_total_region)->toBe(1)
        ->and((int) $area->total_region_done_mapping)->toBe(0);
});

test('category shows Selesai status when every satker finished scanning all items', function () {
    $item = Item::create(['name' => 'Laptop', 'project_id' => 72]);
    $region = Region::create(['category_region' => 'Area Selesai', 'name_region' => 'Region One', 'project_id' => 72]);
    Mapping_lists::create(['item_id' => $item->id, 'region_id' => $region->id, 'serial_number' => 'SN-1', 'project_id' => 72, 'user_id' => $this->user->id]);

    $html = Livewire::test('user.regions', ['guestUuid' => 'test-guest-uuid'])->html();

    expect($html)->toContain('Selesai');
});
