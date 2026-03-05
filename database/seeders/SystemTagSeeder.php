<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class SystemTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define default system tags and their optional attributes
        $defaultTags = [
            // name => [description, color]
            'human-handoff' => [
                'description' => 'Tagged when a human agent should handle a conversation',
                'color' => '#2563EB',
            ],
        ];

        foreach ($defaultTags as $name => $attrs) {
            Tag::updateOrCreate(
                ['name' => $name],
                array_merge(
                    [
                        'is_system' => true,
                        'enabled' => true,
                    ],
                    $attrs
                )
            );
        }
    }
}
