<?php

use App\Interfaces\GeminiAiInterfaceClass;
use App\Interfaces\InterfaceClass;
use App\Interfaces\WaApiMetaInterfaceClass;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

describe('Interface helpers', function () {
    it('reads application version and flushes caches', function () {
        $v = InterfaceClass::readApplicationVersion();
        expect($v)->toBeString();
        expect(strlen($v))->toBeGreaterThanOrEqual(7);

        InterfaceClass::flushRolePermissionCache();
        expect(true)->toBeTrue();
    });

    it('reads application version hash from constants file', function () {
        $path = base_path('.constants');
        file_put_contents($path, "APP_VERSION_HASH=1234567890\nOTHER=1\n");
        try {
            expect(InterfaceClass::readApplicationVersion())->toBe('12345678');
        } finally {
            @unlink($path);
        }
    });

    it('returns unknown if version hash is missing', function () {
        $path = base_path('.constants');
        file_put_contents($path, "OTHER=1\n");
        try {
            expect(InterfaceClass::readApplicationVersion())->toBe('unknown');
        } finally {
            @unlink($path);
        }
    });

    it('checks recent whatsapp messages', function () {
        $svc = new WaApiMetaInterfaceClass;
        $log = WaMessageWebhookLog::create([
            'message_from' => '6281',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);
        $log->created_at = now();
        $log->updated_at = now();
        $log->save();
        expect($svc->hasRecentMessage('6281'))->toBeTrue();
        expect($svc->hasRecentMessage('000'))->toBeFalse();
    });

    it('sends prompt via gemini interface wrapper', function () {
        Config::set('services.geminiai.api_key', 'k');
        Config::set('services.geminiai.base_url', 'https://gemini.test/');
        Config::set('services.geminiai.selected_model', 'm1');

        Http::fake([
            'https://gemini.test/models/m1:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'hello']]],
                ]],
            ], 200),
        ]);

        $g = new GeminiAiInterfaceClass;
        $res = $g->sendPrompt('hi', [['role' => 'user', 'text' => 'u1']]);
        expect($res)->toBe('hello');
    });
});
