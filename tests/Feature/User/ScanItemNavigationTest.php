<?php

use App\Models\Region;
use Livewire\Livewire;

beforeEach(function () {
    session(['guest_uuid' => 'test-guest-uuid']);

    // Satu kelompok (category_region) 'Area A' berisi 3 sub region
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 1', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 2', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 3', 'project_id' => 72]);

    // Kelompok lain yang tidak boleh ikut dinavigasi
    Region::create(['category_region' => 'Area B', 'name_region' => 'Other Region', 'project_id' => 72]);
});

test('sibling sub regions only include regions from the same group', function () {
    $component = Livewire::test('user.scan-item', [
        'categoryRegion' => 'Area A',
        'subRegion' => 'Region 2',
    ]);

    $siblings = $component->get('siblingSubRegions');

    expect($siblings['prev'])->toBe('Region 1')
        ->and($siblings['next'])->toBe('Region 3')
        ->and($siblings['index'])->toBe(2)
        ->and($siblings['total'])->toBe(3);
});

test('prev and next are null for a single sub region group (no loop)', function () {
    $component = Livewire::test('user.scan-item', [
        'categoryRegion' => 'Area B',
        'subRegion' => 'Other Region',
    ]);

    $siblings = $component->get('siblingSubRegions');

    expect($siblings['prev'])->toBeNull()
        ->and($siblings['next'])->toBeNull()
        ->and($siblings['index'])->toBe(1)
        ->and($siblings['total'])->toBe(1);
});

test('scan page shows prev and next sub region names', function () {
    Livewire::test('user.scan-item', [
        'categoryRegion' => 'Area A',
        'subRegion' => 'Region 2',
    ])
        ->assertSee('Region 1')
        ->assertSee('Region 3')
        ->assertSee('2/3');
});

test('nextSubRegion redirects to the following sub region in the same group', function () {
    Livewire::test('user.scan-item', [
        'categoryRegion' => 'Area A',
        'subRegion' => 'Region 2',
    ])
        ->call('nextSubRegion')
        ->assertRedirectToRoute('user.scan-item', [
            'categoryRegion' => 'Area A',
            'subRegion' => 'Region 3',
        ]);
});

test('prevSubRegion redirects to the previous sub region in the same group', function () {
    Livewire::test('user.scan-item', [
        'categoryRegion' => 'Area A',
        'subRegion' => 'Region 2',
    ])
        ->call('prevSubRegion')
        ->assertRedirectToRoute('user.scan-item', [
            'categoryRegion' => 'Area A',
            'subRegion' => 'Region 1',
        ]);
});

test('prevSubRegion does not redirect at the first sub region of the group', function () {
    Livewire::test('user.scan-item', [
        'categoryRegion' => 'Area A',
        'subRegion' => 'Region 1',
    ])
        ->call('prevSubRegion')
        ->assertNoRedirect()
        ->assertSee('Pertama')
        ->assertSee('Region 2');
});

test('nextSubRegion does not redirect at the last sub region of the group', function () {
    Livewire::test('user.scan-item', [
        'categoryRegion' => 'Area A',
        'subRegion' => 'Region 3',
    ])
        ->call('nextSubRegion')
        ->assertNoRedirect()
        ->assertSee('Terakhir')
        ->assertSee('Region 2');
});

test('navigation never crosses to another group', function () {
    $component = Livewire::test('user.scan-item', [
        'categoryRegion' => 'Area B',
        'subRegion' => 'Other Region',
    ]);

    $component->call('prevSubRegion')->assertNoRedirect();
    $component->call('nextSubRegion')->assertNoRedirect();
});

test('mounting with an unknown sub region does not throw a null assignment error', function () {
    // Meniru akses URL manual seperti /user/SUMATERA%20SELATAN/ogan%20komering%20ulu%20selatan
    // yang tidak cocok dengan name_region mana pun di database
    $component = Livewire::test('user.scan-item', [
        'categoryRegion' => 'Area A',
        'subRegion' => 'ogan komering ulu selatan',
    ]);

    expect($component->get('subRegionId'))->toBe('');

    $siblings = $component->get('siblingSubRegions');

    expect($siblings['prev'])->toBeNull()
        ->and($siblings['next'])->toBeNull();

    $component
        ->call('prevSubRegion')
        ->assertNoRedirect()
        ->call('nextSubRegion')
        ->assertNoRedirect();
});
