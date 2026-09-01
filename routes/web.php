<?php

use App\Events\UserLocationUpdated;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Volt::route('/admin/project', 'admin.index')->name('admin.project.index');
    Volt::route('/admin/master-data', 'admin.master-data')->name('admin.master-data');
    Volt::route('/admin/guest-user-monitor', 'admin.guest-user-monitor')->name('admin.guest-user-monitor');
});

Volt::route('/user', 'user.index')->name('user.index');
Volt::route('/user/{categoryRegion}', 'user.sub-region')->name('user.sub-region');
Volt::route('/user/{categoryRegion}/{subRegion}', 'user.scan-item')->name('user.scan-item');

Route::get('/test-reverb', function () {
    broadcast(new UserLocationUpdated());

    return 'Broadcast sent';
});

require __DIR__.'/settings.php';
