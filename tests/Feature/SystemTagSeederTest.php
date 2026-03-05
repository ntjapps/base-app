<?php

use App\Models\Tag;

describe('System Tag Seeder', function () {
    it('seeds default system tags', function () {
        // Run only our seeder
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SystemTagSeeder']);

        $tag = Tag::where('name', 'human-handoff')->first();

        expect($tag)->not->toBeNull();
        expect($tag->enabled)->toBeTrue();
        expect($tag->is_system)->toBeTrue();
        expect($tag->color)->toBe('#2563EB');
    });

    it('is idempotent (no duplicates)', function () {
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SystemTagSeeder']);
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SystemTagSeeder']);

        $humanCount = Tag::where('name', 'human-handoff')->count();
        expect($humanCount)->toEqual(1);
    });
});
