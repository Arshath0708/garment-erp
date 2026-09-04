<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * `php artisan test` loads .env before PHPUnit env vars. Without this,
     * RefreshDatabase was migrate:fresh-ing the local database.sqlite and
     * wiping login + demo data after every test run.
     */
    public function createApplication()
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('DB_URL=');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_ENV['DB_URL'] = '';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_URL'] = '';

        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Draft work orders stay allowed without this. Release and raised POs need a signed sheet.
     */
    protected function approveStyleCosting(\App\Models\GarmentStyle $style, float $cmCost = 10): \App\Models\StyleCosting
    {
        $costing = new \App\Models\StyleCosting([
            'costing_date'       => now()->toDateString(),
            'garment_style_id'   => $style->id,
            'cm_cost'            => $cmCost,
            'other_cost'         => 0,
            'material_cost'      => 0,
            'total_cost_per_pc'  => $cmCost,
            'status'             => 'approved',
            'approved_at'        => now(),
        ]);
        $costing->financial_year = \App\Support\FinancialYear::current();
        $costing->costing_num = 'CS-T-'.$style->id.'-'.uniqid();
        $costing->save();

        return $costing;
    }
}
