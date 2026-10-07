<?php

use App\Models\Item;
use App\Models\Mapping_lists;
use App\Models\Region;
use App\Models\User;
use Livewire\Livewire;

test('the result page lists the available projects', function () {
    $user = User::factory()->create();

    Item::create(['name' => 'Laptop', 'project_id' => 72]);

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->assertSet('projectSelected', '')
        ->assertSee('Select Project')
        ->assertSee('Paket N1');
});

test('selecting a project loads the scan report for that project', function () {
    $user = User::factory()->create();

    $item = Item::create(['name' => 'Laptop', 'project_id' => 72]);
    $region = Region::create([
        'category_region' => 'Area A',
        'name_region' => 'Region 1',
        'project_id' => 72,
    ]);

    Mapping_lists::create([
        'project_id' => 72,
        'region_id' => $region->id,
        'item_id' => $item->id,
        'serial_number' => 'SN-001',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->assertSet('projectSelected', '72')
        ->assertSee('Region 1')
        ->assertSee('Laptop')
        ->assertSee('SN-001');
});

test('the report only contains data for the selected project', function () {
    $user = User::factory()->create();

    Item::create(['name' => 'Laptop', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 1', 'project_id' => 72]);
    Region::create(['category_region' => 'Area B', 'name_region' => 'Region 2', 'project_id' => 99]);

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->assertSee('Region 1')
        ->assertDontSee('Region 2');
});

test('the search field filters the report by region', function () {
    $user = User::factory()->create();

    Item::create(['name' => 'Laptop', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 1', 'project_id' => 72]);
    Region::create(['category_region' => 'Area B', 'name_region' => 'Region 2', 'project_id' => 72]);

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->assertSee('Region 1')
        ->assertSee('Region 2')
        ->set('search', 'Region 2')
        ->assertSee('Region 2')
        ->assertDontSee('Region 1');
});

test('changing the selected project resets the search filter', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->set('search', 'Region 1')
        ->set('projectSelected', '99')
        ->assertSet('search', '');
});

test('the table mode can be toggled to the group view', function () {
    $user = User::factory()->create();

    $item = Item::create(['name' => 'Laptop', 'project_id' => 72]);
    $region = Region::create([
        'category_region' => 'Area A',
        'name_region' => 'Region 1',
        'project_id' => 72,
    ]);

    Mapping_lists::create([
        'project_id' => 72,
        'region_id' => $region->id,
        'item_id' => $item->id,
        'serial_number' => 'SN-001',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->call('toggleTableMode')
        ->assertSet('tableMode', 'grid')
        ->assertSee('Region 1')
        ->assertSee('1/1');
});

test('every item becomes its own column holding the region serial number', function () {
    $user = User::factory()->create();

    $laptop = Item::create(['name' => 'Laptop', 'project_id' => 72]);
    Item::create(['name' => 'Printer', 'project_id' => 72]);

    $region = Region::create([
        'category_region' => 'Area A',
        'name_region' => 'Region 1',
        'project_id' => 72,
    ]);

    Mapping_lists::create([
        'project_id' => 72,
        'region_id' => $region->id,
        'item_id' => $laptop->id,
        'serial_number' => 'SN-001',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->assertSeeHtmlInOrder(['Laptop', 'Printer', 'SN-001']);
});

test('regions that share a category are merged into a single cell', function () {
    $user = User::factory()->create();

    Item::create(['name' => 'Laptop', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 1', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 2', 'project_id' => 72]);
    Region::create(['category_region' => 'Area B', 'name_region' => 'Region 3', 'project_id' => 72]);

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->assertSeeHtml('rowspan="2"')
        ->assertSee('Area A')
        ->assertSee('Region 3');
});

test('the table freezes the first three columns and scrolls the rest', function () {
    $user = User::factory()->create();

    Item::create(['name' => 'Laptop', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 1', 'project_id' => 72]);

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->assertSeeHtml('overflow-x-auto')
        ->assertSeeHtml('sticky left-0')
        ->assertSeeHtml('sticky left-16')
        ->assertSeeHtml('sticky left-56');
});

test('the group view buckets and orders regions by scan status', function () {
    $user = User::factory()->create();

    $items = collect(['Laptop', 'Printer', 'Monitor', 'Keyboard', 'Mouse'])
        ->map(fn ($name) => Item::create(['name' => $name, 'project_id' => 72]));

    $complete = Region::create(['category_region' => 'Area A', 'name_region' => 'Complete Region', 'project_id' => 72]);
    $ongoing = Region::create(['category_region' => 'Area A', 'name_region' => 'Ongoing Region', 'project_id' => 72]);
    Region::create(['category_region' => 'Area B', 'name_region' => 'Untouched Region', 'project_id' => 72]);

    $scan = function (Region $region, Item $item) use ($user) {
        Mapping_lists::create([
            'project_id' => 72,
            'region_id' => $region->id,
            'item_id' => $item->id,
            'serial_number' => "SN-{$item->id}",
            'user_id' => $user->id,
        ]);
    };

    // 5/5 items scanned -> complete
    $items->each(fn ($item) => $scan($complete, $item));

    // 2/5 items scanned -> ongoing
    $items->take(2)->each(fn ($item) => $scan($ongoing, $item));

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->call('toggleTableMode')
        ->assertSet('tableMode', 'grid')
        ->assertSeeHtmlInOrder([
            'data-test="scan-group-complete"',
            'data-test="scan-group-ongoing"',
            'data-test="scan-group-not_scanned"',
        ])
        ->assertSee('Complete Scan')
        ->assertSee('On Going')
        ->assertSee('Not Scan')
        ->assertSee('Complete Region')
        ->assertSee('Ongoing Region')
        ->assertSee('Untouched Region')
        ->assertSee('5/5 scanned')
        ->assertSee('2/5 scanned')
        ->assertSee('0/5 scanned');
});

test('the group view hides scan status groups that have no regions', function () {
    $user = User::factory()->create();

    Item::create(['name' => 'Laptop', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Untouched Region', 'project_id' => 72]);

    $this->actingAs($user);

    Livewire::test('admin.result')
        ->set('projectSelected', '72')
        ->call('toggleTableMode')
        ->assertSeeHtml('data-test="scan-group-not_scanned"')
        ->assertDontSeeHtml('data-test="scan-group-complete"')
        ->assertDontSeeHtml('data-test="scan-group-ongoing"');
});
