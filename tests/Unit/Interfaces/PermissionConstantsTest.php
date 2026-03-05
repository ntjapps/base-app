<?php

use App\Interfaces\PermissionConstants;

describe('PermissionConstants', function () {
    it('exposes permission lists and filters', function () {
        $all = PermissionConstants::all();
        expect($all)->toBeArray();
        expect(in_array(PermissionConstants::WHATSAPP_VIEW, $all, true))->toBeTrue();

        $priv = PermissionConstants::privileged();
        expect($priv)->toBeArray();
        expect(in_array(PermissionConstants::SUPER_ADMIN, $priv, true))->toBeTrue();

        $menus = PermissionConstants::menus();
        expect($menus)->toBeArray();
        expect(in_array(PermissionConstants::MENU_DASHBOARD, $menus, true))->toBeTrue();

        $userNs = PermissionConstants::byNamespace('user');
        expect($userNs)->toBeArray();
        expect(in_array(PermissionConstants::USER_VIEW, $userNs, true))->toBeTrue();
        expect(in_array(PermissionConstants::ROLE_VIEW, $userNs, true))->toBeFalse();
    });
});
