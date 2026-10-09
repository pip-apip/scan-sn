<?php

use App\Models\Item;
use App\Models\Region;
use Livewire\Livewire;

beforeEach(function () {
    session(['guest_uuid' => 'test-guest-uuid']);
    config(['broadcasting.default' => 'null']);
});

test('save sn section and inventory list sit in a two column grid for web view', function () {
    Item::create(['name' => 'Item A', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 1', 'project_id' => 72]);

    $html = Livewire::test('user.scan-item', ['categoryRegion' => 'Area A', 'subRegion' => 'Region 1'])
        ->html();

    // Wrapper responsive: 1 kolom di mobile, 2 kolom di web view (lg+)
    expect($html)->toContain('grid-cols-1')
        ->toContain('lg:grid-cols-2');

    // Verifikasi lewat DOM: Save SN (x-data tabs) dan Daftar Inventaris adalah
    // dua anak langsung dari wrapper grid yang sama
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<html><body>'.$html.'</body></html>', LIBXML_NOERROR);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $wrappers = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' lg:grid-cols-2 ')]");

    expect($wrappers->length)->toBe(1);

    $wrapper = $wrappers->item(0);
    $children = $xpath->query('./div', $wrapper);

    // Kolom kiri = section Save SN (punya x-data tabs), kolom kanan = Daftar Inventaris
    expect($children->length)->toBe(2);

    $firstColumn = $children->item(0);
    $secondColumn = $children->item(1);

    expect($firstColumn->getAttribute('x-data'))->toContain('barcodeScannerSection');
    expect($secondColumn->textContent)->toContain('Daftar Inventaris Daerah');
});

test('inventory cards are compacted for web view only', function () {
    Item::create(['name' => 'Item A', 'project_id' => 72]);
    Region::create(['category_region' => 'Area A', 'name_region' => 'Region 1', 'project_id' => 72]);

    $html = Livewire::test('user.scan-item', ['categoryRegion' => 'Area A', 'subRegion' => 'Region 1'])
        ->html();

    // Variant lg (web view): card super compact — padding, jarak, radius, tipografi,
    // baris kategori disembunyikan, dan area scroll ditinggikan
    expect($html)->toContain('lg:p-2.5')
        ->toContain('lg:space-y-1.5')
        ->toContain('lg:rounded-xl')
        ->toContain('lg:text-sm')
        ->toContain('lg:hidden')
        ->toContain('lg:max-h-[calc(100vh-220px)]');

    // Base class untuk mobile tidak berubah
    expect($html)->toContain('p-4 transition-colors')
        ->toContain('max-h-[calc(100vh-400px)] pr-1');
});
