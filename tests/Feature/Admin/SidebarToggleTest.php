<?php

use App\Models\User;

test('the admin sidebar exposes a hide and show toggle on desktop', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('admin.result'));
    $response->assertOk();

    $html = $response->getContent();

    // The sidebar must be collapsible (not only on mobile), which is what
    // lets Flux toggle it on desktop as well.
    expect($html)->toContain('collapsible="true"');

    // The collapse button must be rendered and must NOT be limited to mobile
    // screens (no `lg:hidden`), otherwise there is no desktop toggle.
    preg_match('/<ui-sidebar-toggle[^>]*data-flux-sidebar-collapse[^>]*>/', $html, $matches);

    expect($matches)->not->toBeEmpty()
        ->and($matches[0])->not->toContain('lg:hidden');
});

test('the mobile sidebar toggle is still available', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('admin.result'));
    $response->assertOk();

    $response->assertSee('data-flux-sidebar-toggle', false);
});
