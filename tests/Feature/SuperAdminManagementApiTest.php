<?php

use App\Interfaces\RoleConstants;
use App\Models\AiModelInstruction;
use App\Models\Division;
use App\Models\Tag;
use App\Models\User;

describe('Super Admin Management API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->user->syncRoles([RoleConstants::SUPER_ADMIN]);
        $this->actingAs($this->user, 'api');
    });

    it('manages divisions', function () {
        Division::create(['name' => 'Sales', 'description' => 'Sales', 'enabled' => true]);

        $this->getJson(route('get-division-list'))->assertStatus(200)->assertJsonStructure(['data']);

        $this->postJson(route('post-division-man-submit'), [
            'type_create' => true,
            'name' => 'Support',
            'description' => 'Support',
            'enabled' => true,
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $division = Division::firstOrFail();
        $this->postJson(route('post-division-man-submit'), [
            'type_create' => 0,
            'id' => $division->id,
            'name' => 'Sales',
            'description' => 'Sales Updated',
            'enabled' => true,
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $this->deleteJson(route('post-delete-division-man-submit', ['division' => $division->id]), [
            'id' => $division->id,
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $this->postJson(route('post-division-man-submit'), [])->assertStatus(422);
    });

    it('manages tags', function () {
        Tag::create(['name' => 'vip', 'description' => 'VIP', 'color' => '#000000', 'enabled' => true, 'is_system' => false]);

        $this->getJson(route('get-tag-list'))->assertStatus(200)->assertJsonStructure(['data']);

        $this->postJson(route('post-tag-man-submit'), [
            'type_create' => true,
            'name' => 'urgent',
            'description' => 'Urgent',
            'color' => '#ff0000',
            'enabled' => true,
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $tag = Tag::firstOrFail();
        $this->postJson(route('post-tag-man-submit'), [
            'type_create' => 0,
            'id' => $tag->id,
            'name' => 'vip',
            'description' => 'VIP Updated',
            'color' => '#000000',
            'enabled' => true,
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $this->deleteJson(route('post-delete-tag-man-submit', ['tag' => $tag->id]), [
            'id' => $tag->id,
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $this->postJson(route('post-tag-man-submit'), [])->assertStatus(422);
    });

    it('manages ai model instructions', function () {
        $instruction = AiModelInstruction::create([
            'name' => 'Default',
            'key' => 'support_default',
            'instructions' => 'Hello',
            'enabled' => true,
            'scope' => null,
        ]);

        $this->getJson(route('get-ai-model-instruction-list'))->assertStatus(200)->assertJsonStructure(['data']);

        $this->postJson(route('post-ai-model-instruction-man-submit'), [
            'type_create' => true,
            'name' => 'Support Default',
            'key' => 'support_default',
            'instructions' => 'Hi',
            'enabled' => true,
            'scope' => [],
        ])->assertStatus(422);

        $this->postJson(route('post-ai-model-instruction-man-submit'), [
            'type_create' => true,
            'name' => 'Support Extra',
            'key' => 'support_extra',
            'instructions' => 'Hi',
            'enabled' => true,
            'scope' => [],
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $this->postJson(route('post-ai-model-instruction-man-submit'), [
            'type_create' => 0,
            'id' => $instruction->id,
            'name' => 'Default',
            'key' => 'support_default',
            'instructions' => 'Updated',
            'enabled' => true,
            'scope' => ['foo' => 'bar'],
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $this->deleteJson(route('post-delete-ai-model-instruction-man-submit', ['aiModelInstruction' => $instruction->id]), [
            'id' => $instruction->id,
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $this->postJson(route('post-ai-model-instruction-export-file'), [
            'file_name' => 'ai_instructions_export.json',
        ])->assertStatus(202)->assertJsonStructure(['task_id', 'status', 'message']);

        $this->postJson(route('post-ai-model-instruction-import-file'), [
            'file_name' => 'missing_file.json',
        ])->assertStatus(404);

        $this->postJson(route('post-ai-model-instruction-man-submit'), [])->assertStatus(422);
    });
});
