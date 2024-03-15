<?php

namespace Tests\Feature;

use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Notification;
use Tests\AuthTestCase;

class BasicNotificationTest extends AuthTestCase
{
    /**
     * Check if notification is exist
     */
    public function test_notification_exist(): void
    {
        $user = $this->commonSeedTestData();
        Notification::send($user, new TestNotification());

        $this->assertDatabaseHas('notifications', [
            'type' => 'App\Notifications\TestNotification',
        ]);

        $listOfNotification = $user->notifications->toArray();

        $this->assertIsArray($listOfNotification);
        $this->assertNotEmpty($listOfNotification);
    }

    /**
     * Check if notification is marked as read
     */
    public function test_notification_mark_as_read(): void
    {
        $user = $this->commonSeedTestData();
        Notification::send($user, new TestNotification());

        $listOfNotification = $user->notifications->toArray();

        $notification = $listOfNotification[0];
        $this->assertArrayHasKey('id', $notification);
        $this->assertArrayHasKey('read_at', $notification);
        $this->assertNull($notification['read_at']);

        $user->notifications->markAsRead();

        $listOfNotification = $user->notifications->toArray();
        $notification = $listOfNotification[0];
        $this->assertNotNull($notification['read_at']);
    }

    /**
     * Mark notification read by id
     */
    public function test_notification_mark_as_read_by_id(): void
    {
        $user = $this->commonSeedTestData();
        Notification::send($user, new TestNotification());

        $listOfNotification = $user->notifications->toArray();

        $notification = $listOfNotification[0];
        $this->assertArrayHasKey('id', $notification);
        $this->assertArrayHasKey('read_at', $notification);
        $this->assertNull($notification['read_at']);

        $user->notifications->where('id', $notification['id'])->markAsRead();

        $listOfNotification = $user->notifications->toArray();
        $notification = $listOfNotification[0];
        $this->assertNotNull($notification['read_at']);
    }
}
