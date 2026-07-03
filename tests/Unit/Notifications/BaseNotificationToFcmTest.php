<?php

namespace Tests\Unit\Notifications;

use Coderstm\Notifications\BaseNotification;
use Kreait\Firebase\Messaging\CloudMessage;
use Tests\TestCase;

class BaseNotificationToFcmTest extends TestCase
{
    public function test_to_fcm_with_null_topic()
    {
        $notification = new BaseNotification('Subject', 'Message');
        $notification->pushSubject = 'Push Subject';
        $notification->pushMessage = 'Push Message';
        $notification->pushTopic = null;

        /** @var CloudMessage $message */
        $message = $notification->toFcm(null);
        $data = json_decode(json_encode($message), true);

        $this->assertArrayHasKey('notification', $data);
        $this->assertEquals('Push Subject', $data['notification']['title']);
        $this->assertEquals('Push Message', $data['notification']['body']);
        $this->assertArrayNotHasKey('topic', $data);
    }

    public function test_to_fcm_with_custom_topic()
    {
        $notification = new BaseNotification('Subject', 'Message');
        $notification->pushSubject = 'Push Subject';
        $notification->pushMessage = 'Push Message';
        $notification->pushTopic = 'custom-topic';

        /** @var CloudMessage $message */
        $message = $notification->toFcm(null);
        $data = json_decode(json_encode($message), true);

        $this->assertArrayHasKey('notification', $data);
        $this->assertEquals('Push Subject', $data['notification']['title']);
        $this->assertEquals('Push Message', $data['notification']['body']);
        $this->assertArrayHasKey('topic', $data);
        $this->assertEquals('custom-topic', $data['topic']);
    }

    public function test_to_fcm_with_push_data()
    {
        $notification = new BaseNotification('Subject', 'Message');
        $notification->pushData = ['key' => 'value', 'empty' => null];

        /** @var CloudMessage $message */
        $message = $notification->toFcm(null);
        $data = json_decode(json_encode($message), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertEquals(['key' => 'value'], $data['data']);
    }
}
