<?php

use App\Jobs\WhatsApp\AddConversationTagsJob;
use App\Jobs\WhatsApp\ClaimConversationJob;
use App\Jobs\WhatsApp\CreateTemplateJob;
use App\Jobs\WhatsApp\DeleteTemplateJob;
use App\Jobs\WhatsApp\RemoveConversationTagJob;
use App\Jobs\WhatsApp\ResolveConversationJob;
use App\Jobs\WhatsApp\SendTemplateMessageJob;
use App\Jobs\WhatsApp\UpdateTemplateJob;
use Illuminate\Support\Facades\Log;

describe('WhatsApp Jobs', function () {
    it('runs conversation jobs and emits tags', function () {
        $add = new AddConversationTagsJob('c1', ['vip']);
        expect($add->tags())->toBeArray();
        $add->handle();

        $remove = new RemoveConversationTagJob('c1', 'vip');
        expect($remove->tags())->toBeArray();
        $remove->handle();

        $claim = new ClaimConversationJob('c1', 'agent1');
        expect($claim->tags())->toBeArray();
        $claim->handle();

        $resolve = new ResolveConversationJob('c1');
        expect($resolve->tags())->toBeArray();
        $resolve->handle();
    });

    it('runs template jobs and emits unique ids', function () {
        $create = new CreateTemplateJob([
            'name' => 't1',
            'language' => 'id',
            'category' => 'UTILITY',
            'components' => [['type' => 'BODY', 'text' => 'Hello']],
        ], 'user1');
        expect($create->uniqueId())->toBeString();
        expect($create->tags())->toBeArray();
        $create->handle();

        $update = new UpdateTemplateJob('tpl1', [
            'category' => 'UTILITY',
            'components' => [['type' => 'BODY', 'text' => 'Hello']],
            'message_send_ttl_seconds' => 60,
            'cta_url_link_tracking_opted_out' => true,
        ], 'user1');
        expect($update->uniqueId())->toBeString();
        expect($update->tags())->toBeArray();
        $update->handle();

        $delete = new DeleteTemplateJob('tpl1', 't1', 'user1');
        expect($delete->uniqueId())->toBeString();
        expect($delete->tags())->toBeArray();
        $delete->handle();

        $send = new SendTemplateMessageJob('628123456789', 't1', [['type' => 'BODY']], 'id', 'user1');
        expect($send->tags())->toBeArray();
        $send->handle();
    });

    it('catches and rethrows exception in AddConversationTagsJob', function () {
        Log::shouldReceive('debug')->andThrow(new RuntimeException('log failure'));
        Log::shouldReceive('error')->once();

        (new AddConversationTagsJob('c1', ['vip']))->handle();
    })->throws(RuntimeException::class);

    it('catches and rethrows exception in ClaimConversationJob', function () {
        Log::shouldReceive('debug')->andThrow(new RuntimeException('log failure'));
        Log::shouldReceive('error')->once();

        (new ClaimConversationJob('c1', 'agent1'))->handle();
    })->throws(RuntimeException::class);

    it('catches and rethrows exception in RemoveConversationTagJob', function () {
        Log::shouldReceive('debug')->andThrow(new RuntimeException('log failure'));
        Log::shouldReceive('error')->once();

        (new RemoveConversationTagJob('c1', 'vip'))->handle();
    })->throws(RuntimeException::class);

    it('catches and rethrows exception in ResolveConversationJob', function () {
        Log::shouldReceive('debug')->andThrow(new RuntimeException('log failure'));
        Log::shouldReceive('error')->once();

        (new ResolveConversationJob('c1'))->handle();
    })->throws(RuntimeException::class);

    it('catches and rethrows exception in DeleteTemplateJob', function () {
        Log::shouldReceive('debug')->andThrow(new RuntimeException('log failure'));
        Log::shouldReceive('error')->once();

        (new DeleteTemplateJob('tpl1', 't1', 'user1'))->handle();
    })->throws(RuntimeException::class);
});
