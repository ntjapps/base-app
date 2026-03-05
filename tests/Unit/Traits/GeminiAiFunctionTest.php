<?php

namespace Tests\Unit\Traits;

use App\Traits\GeminiAiFunction;
use ErrorException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class GeminiAiFunctionHarness
{
    use GeminiAiFunction;

    public function gen(string $message, array $conversation = [], ?string $system = null): string
    {
        return $this->generateContent($message, $conversation, $system, null, null, null, null);
    }

    public function genWithFile(string $message, string $localFilePath, ?string $displayName = null, ?string $mimeType = null, array $conversation = [], ?string $system = null): string
    {
        return $this->generateContent($message, $conversation, $system, null, $localFilePath, $displayName, $mimeType);
    }

    public function body(string $message, array $conversation = [], ?array $fileData = null, ?string $system = null): array
    {
        return $this->buildGeminiRequestBody($message, $conversation, $fileData, $system);
    }

    public function upload(string $filePath, string $displayName, ?string $mimeType = null): string
    {
        return $this->uploadFileAndGetUri($filePath, $displayName, $mimeType);
    }
}

describe('GeminiAiFunction', function () {
    beforeEach(function () {
        Config::set('services.geminiai.api_key', 'k');
        Config::set('services.geminiai.base_url', 'https://gemini.test/');
        Config::set('services.geminiai.selected_model', 'm1');
        Config::set('services.geminiai.file_upload_base_url', 'https://upload.test/upload/v1beta/files');
    });

    it('returns content text on success', function () {
        Http::fake([
            'https://gemini.test/models/m1:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'hello']]],
                ]],
            ], 200),
        ]);

        $h = new GeminiAiFunctionHarness;
        $res = $h->gen('hi', [['role' => 'user', 'text' => 'u1']], 'sys');
        expect($res)->toBe('hello');
    });

    it('throws when response has no expected text', function () {
        Http::fake([
            'https://gemini.test/models/m1:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['nope' => true]]],
                ]],
            ], 200),
        ]);

        $h = new GeminiAiFunctionHarness;
        $h->gen('hi');
    })->throws(ErrorException::class);

    it('throws on API error', function () {
        Http::fake([
            'https://gemini.test/models/m1:generateContent' => Http::response(['error' => 'bad'], 500),
        ]);

        $h = new GeminiAiFunctionHarness;
        $h->gen('hi');
    })->throws(ErrorException::class);

    it('builds request body with history, system instruction, and file data', function () {
        $h = new GeminiAiFunctionHarness;
        $body = $h->body('hi', [['role' => 'user', 'text' => 'u1']], ['mime_type' => 'text/plain', 'file_uri' => 'u'], 'sys');

        expect($body['system_instruction']['parts'][0]['text'])->toBe('sys');
        expect($body['contents'][0]['role'])->toBe('user');
        expect($body['contents'][1]['parts'][1]['file_data']['file_uri'])->toBe('u');
    });

    it('throws when upload file is missing', function () {
        $h = new GeminiAiFunctionHarness;
        $h->upload('/nonexistent/file', 'DATA', 'text/plain');
    })->throws(ErrorException::class);

    it('uploads file and returns uri on success', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'gemini_');
        file_put_contents($tmp, 'abc');

        Http::fake([
            'https://upload.test/upload/v1beta/files?key=k' => Http::response([], 200, [
                'x-goog-upload-url' => 'https://upload.test/resumable/1',
            ]),
            'https://upload.test/resumable/1' => Http::response([
                'file' => ['uri' => 'https://generativelanguage.googleapis.com/v1beta/files/f1'],
            ], 200),
        ]);

        $h = new GeminiAiFunctionHarness;
        $uri = $h->upload($tmp, 'DATA', 'text/plain');
        expect($uri)->toBe('https://generativelanguage.googleapis.com/v1beta/files/f1');
        @unlink($tmp);
    });

    it('throws when upload start fails', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'gemini_');
        file_put_contents($tmp, 'abc');

        Http::fake([
            'https://upload.test/upload/v1beta/files?key=k' => Http::response(['error' => 'bad'], 500),
        ]);

        $h = new GeminiAiFunctionHarness;
        try {
            $h->upload($tmp, 'DATA', 'text/plain');
        } finally {
            @unlink($tmp);
        }
    })->throws(ErrorException::class);

    it('throws when upload start response has no upload url header', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'gemini_');
        file_put_contents($tmp, 'abc');

        Http::fake([
            'https://upload.test/upload/v1beta/files?key=k' => Http::response([], 200, []),
        ]);

        $h = new GeminiAiFunctionHarness;
        try {
            $h->upload($tmp, 'DATA', 'text/plain');
        } finally {
            @unlink($tmp);
        }
    })->throws(ErrorException::class);

    it('throws when upload finalize fails', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'gemini_');
        file_put_contents($tmp, 'abc');

        Http::fake([
            'https://upload.test/upload/v1beta/files?key=k' => Http::response([], 200, [
                'x-goog-upload-url' => 'https://upload.test/resumable/2',
            ]),
            'https://upload.test/resumable/2' => Http::response(['error' => 'no'], 500),
        ]);

        $h = new GeminiAiFunctionHarness;
        try {
            $h->upload($tmp, 'DATA', 'text/plain');
        } finally {
            @unlink($tmp);
        }
    })->throws(ErrorException::class);

    it('throws when upload finalize response has no file uri', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'gemini_');
        file_put_contents($tmp, 'abc');

        Http::fake([
            'https://upload.test/upload/v1beta/files?key=k' => Http::response([], 200, [
                'x-goog-upload-url' => 'https://upload.test/resumable/3',
            ]),
            'https://upload.test/resumable/3' => Http::response(['file' => []], 200),
        ]);

        $h = new GeminiAiFunctionHarness;
        try {
            $h->upload($tmp, 'DATA', 'text/plain');
        } finally {
            @unlink($tmp);
        }
    })->throws(ErrorException::class);

    it('generates content with uploaded file data', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'gemini_');
        file_put_contents($tmp, 'abc');

        Http::fake([
            'https://upload.test/upload/v1beta/files?key=k' => Http::response([], 200, [
                'x-goog-upload-url' => 'https://upload.test/resumable/4',
            ]),
            'https://upload.test/resumable/4' => Http::response([
                'file' => ['uri' => 'https://generativelanguage.googleapis.com/v1beta/files/f2'],
            ], 200),
            'https://gemini.test/models/m1:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'ok']]],
                ]],
            ], 200),
        ]);

        $h = new GeminiAiFunctionHarness;
        try {
            $res = $h->genWithFile('hi', $tmp, 'DATA', 'text/plain', [['role' => 'user', 'text' => 'u1']], 'sys');
            expect($res)->toBe('ok');
        } finally {
            @unlink($tmp);
        }
    });
});
