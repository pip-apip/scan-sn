<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Paksa environment testing selalu memakai SQLite in-memory, terlepas dari
     * config cache (bootstrap/cache/config.php) yang bisa menunjuk ke database
     * MySQL development. Tanpa guard ini, RefreshDatabase akan menjalankan
     * "migrate:fresh" ke database development setiap kali suite dijalankan.
     *
     * Dipanggil sebelum setUpTraits() (RefreshDatabase) berjalan.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'broadcasting.default' => 'null',
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
