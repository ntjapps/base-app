<?php

use App\Interfaces\MenuItemClass;
use App\Interfaces\PermissionConstants;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

describe('MenuItemClass', function () {
    it('returns expanded keys for known routes', function () {
        expect(MenuItemClass::currentRouteExpandedKeys('profile'))->toBe('9999');
        expect(MenuItemClass::currentRouteExpandedKeys('whatsapp-man'))->toBe('1000');
        expect(MenuItemClass::currentRouteExpandedKeys('unknown'))->toBeNull();
    });

    it('builds whatsapp menu based on permissions', function () {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        Gate::shouldReceive('forUser')->andReturnSelf();
        Gate::shouldReceive('allows')->with('hasPermission', PermissionConstants::WHATSAPP_VIEW)->andReturn(false, true);
        expect(MenuItemClass::whatsappMenu())->toBe([]);
        $menu = MenuItemClass::whatsappMenu();
        expect($menu['key'])->toBe('1000');
        expect($menu['children'])->toBeArray();
    });

    it('builds administration menu based on super permission', function () {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        Gate::shouldReceive('forUser')->andReturnSelf();
        Gate::shouldReceive('allows')->with('hasSuperPermission', User::class)->andReturn(false, true);
        $menu = MenuItemClass::administrationMenu();
        expect($menu['key'])->toBe('9999');
        expect(count($menu['children']))->toBe(1);

        $menu = MenuItemClass::administrationMenu();
        expect($menu['key'])->toBe('9999');
        expect(count($menu['children']))->toBeGreaterThan(1);
    });
});
