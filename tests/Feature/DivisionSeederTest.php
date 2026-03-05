<?php

use App\Models\Division;

describe('Division Seeder', function () {
    it('seeds Billing and Support divisions', function () {
        // Run only our seeder
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DivisionSeeder']);

        $billing = Division::where('name', 'Billing')->first();
        $support = Division::where('name', 'Support')->first();

        expect($billing)->not->toBeNull();
        expect($billing->enabled)->toBeTrue();
        expect($support)->not->toBeNull();
        expect($support->enabled)->toBeTrue();
    });

    it('is idempotent (no duplicates)', function () {
        // Seed twice
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DivisionSeeder']);
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DivisionSeeder']);

        $billingCount = Division::where('name', 'Billing')->count();
        $supportCount = Division::where('name', 'Support')->count();

        expect($billingCount)->toEqual(1);
        expect($supportCount)->toEqual(1);
    });
});
