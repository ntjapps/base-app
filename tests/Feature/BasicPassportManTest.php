<?php

namespace Tests\Feature;

use App\Interfaces\InterfaceClass;
use App\Models\PassportClient;
use Tests\AuthTestCase;

class BasicPassportManTest extends AuthTestCase
{
    /**
     * Accesssing passport management page
     */
    public function test_accessing_passport_management_page(): void
    {
        $user = $this->commonSeedTestData();
        $user->syncRoles([InterfaceClass::SUPERROLE]);

        $response = $this->actingAs($user)->get(route('passport-man'));

        $response->assertOk()->assertViewIs('base-components.base-vue');
    }

    /**
     * Get OAuth client list
     */
    public function test_get_oauth_client_list(): void
    {
        $user = $this->commonSeedTestData();
        $user->syncRoles([InterfaceClass::SUPERROLE]);

        $response = $this->actingAs($user)->postJson(route('passport.clients.index'));

        $response->assertOk()->assertJsonIsArray();
    }

    /**
     * Reset client secret
     */
    public function test_reset_client_secret(): void
    {
        $user = $this->commonSeedTestData();
        $user->syncRoles([InterfaceClass::SUPERROLE]);

        $passportClient = PassportClient::first();

        $this->assertNotEmpty($passportClient);

        $response = $this->actingAs($user)->postJson(route('passport.clients.reset-secret'), ['id' => $passportClient->id]);

        $response->assertOk();
    }

    /**
     * Delete client
     */
    public function test_delete_client(): void
    {
        $user = $this->commonSeedTestData();
        $user->syncRoles([InterfaceClass::SUPERROLE]);

        /** Create Client First */
        $passportClient = PassportClient::create([
            'name' => 'Test Client',
            'redirect' => 'http://localhost',
            'personal_access_client' => 1,
            'password_client' => 1,
            'revoked' => 0,
        ]);

        $this->assertNotEmpty($passportClient);

        $response = $this->actingAs($user)->postJson(route('passport.clients.destroy'), ['id' => $passportClient->id]);

        $response->assertOk();

        $this->assertDatabaseMissing(PassportClient::class, ['id' => $passportClient->id]);
    }

    /**
     * Update client information
     */
    public function test_update_client_information(): void
    {
        $user = $this->commonSeedTestData();
        $user->syncRoles([InterfaceClass::SUPERROLE]);

        /** Create Client First */
        $passportClient = PassportClient::create([
            'name' => 'Test Client',
            'redirect' => 'http://localhost',
            'personal_access_client' => 1,
            'password_client' => 1,
            'revoked' => 0,
        ]);

        $this->assertNotEmpty($passportClient);

        $response = $this->actingAs($user)->postJson(route('passport.clients.update'), [
            'id' => $passportClient->id,
            'name' => 'Test Client Updated',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas(PassportClient::class, ['id' => $passportClient->id, 'name' => 'Test Client Updated']);
    }

    /**
     * Create client
     */
    public function test_create_client(): void
    {
        $user = $this->commonSeedTestData();
        $user->syncRoles([InterfaceClass::SUPERROLE]);

        $response = $this->actingAs($user)->postJson(route('passport.clients.store'), [
            'name' => 'Test Client',
            'redirect' => 'http://localhost',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas(PassportClient::class, ['name' => 'Test Client']);
    }
}
