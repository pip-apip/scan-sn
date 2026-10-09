<?php

use App\Models\Region;
use Livewire\Livewire;

beforeEach(function () {
    session(['guest_uuid' => 'test-guest-uuid']);
    config(['broadcasting.default' => 'null']);
});

/**
 * Kumpulkan nilai semua atribut wire:click dari HTML hasil render.
 */
function wireClickArgs(string $html): array
{
    preg_match_all('/wire:click(?:\.stop)?=([\'"])(.*?)\1/s', $html, $matches, PREG_SET_ORDER);

    return array_map(fn ($m) => $m[2], $matches);
}

test('sub region cards with newline names render JS-safe wire:click expressions', function () {
    Region::create([
        'category_region' => 'Area A',
        'name_region' => "KEJAKSAAN NEGERI OGAN KOMERING ULU\n SELATAN",
        'project_id' => 72,
    ]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region Biasa', 'project_id' => 72]);

    $html = Livewire::test('user.sub-region', ['categoryRegion' => 'Area A'])->html();

    // Ekspresi JS harus berupa string JSON, tanpa newline literal di dalam atribut
    $args = wireClickArgs($html);

    expect($args)->not->toBeEmpty();

    foreach ($args as $arg) {
        expect($arg)->not->toContain("\n");
    }

    // selectCard pada card div & chooseSubRegion pada tombol footer memakai escape \n
    expect($html)->toContain("selectCard('KEJAKSAAN NEGERI OGAN KOMERING ULU\\n SELATAN')");
    expect($html)->toContain("chooseSubRegion('KEJAKSAAN NEGERI OGAN KOMERING ULU\\n SELATAN')");
});

test('category cards with newline names render JS-safe wire:click expressions', function () {
    Region::create([
        'category_region' => "KEPULAUAN BANGKA\n BELITUNG",
        'name_region' => 'Region Satu',
        'project_id' => 72,
    ]);

    $html = Livewire::test('user.regions', ['guestUuid' => 'test-guest-uuid'])->html();

    $args = wireClickArgs($html);

    expect($args)->not->toBeEmpty();

    foreach ($args as $arg) {
        expect($arg)->not->toContain("\n");
    }

    expect($html)->toContain("selectCard('KEPULAUAN BANGKA\\n BELITUNG')");
    expect($html)->toContain("chooseRegion('KEPULAUAN BANGKA\\n BELITUNG')");
});

test('selecting a sub region card with newline name toggles the active state', function () {
    $name = "KEJAKSAAN NEGERI OGAN KOMERING ULU\n SELATAN";

    Region::create(['category_region' => 'Area A', 'name_region' => $name, 'project_id' => 72]);

    Livewire::test('user.sub-region', ['categoryRegion' => 'Area A'])
        ->call('selectCard', $name)
        ->assertSet('selectedCard', $name)
        ->call('selectCard', $name)
        ->assertSet('selectedCard', null);
});

test('footer button navigates to the scan page for a newline-named sub region', function () {
    $name = "KEJAKSAAN NEGERI OGAN KOMERING ULU\n SELATAN";

    Region::create(['category_region' => 'Area A', 'name_region' => $name, 'project_id' => 72]);

    Livewire::test('user.sub-region', ['categoryRegion' => 'Area A'])
        ->call('chooseSubRegion', $name)
        ->assertRedirectToRoute('user.scan-item', [
            'categoryRegion' => 'Area A',
            'subRegion' => $name,
        ]);
});

test('footer button navigates to the sub region page for a newline-named category', function () {
    $category = "KEPULAUAN BANGKA\n BELITUNG";

    Region::create(['category_region' => $category, 'name_region' => 'Region Satu', 'project_id' => 72]);

    Livewire::test('user.regions', ['guestUuid' => 'test-guest-uuid'])
        ->call('chooseRegion', $category)
        ->assertRedirectToRoute('user.sub-region', [
            'categoryRegion' => $category,
        ]);
});
