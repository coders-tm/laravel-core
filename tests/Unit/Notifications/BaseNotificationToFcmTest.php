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

    public function test_to_fcm_includes_push_type()
    {
        $notification = new BaseNotification('Subject', 'Message');
        $notification->pushType = 'user:booking-canceled';
        $notification->pushData = ['route' => '/classes/booked'];

        /** @var CloudMessage $message */
        $message = $notification->toFcm(null);
        $data = json_decode(json_encode($message), true);

        $this->assertEquals('user:booking-canceled', $data['data']['type']);
        $this->assertEquals('/classes/booked', $data['data']['route']);
    }

    public function test_to_fcm_push_type_takes_precedence_over_push_data_type()
    {
        $notification = new BaseNotification('Subject', 'Message');
        $notification->pushType = 'user:booking-canceled';
        $notification->pushData = ['type' => 'stale-type'];

        /** @var CloudMessage $message */
        $message = $notification->toFcm(null);
        $data = json_decode(json_encode($message), true);

        $this->assertEquals('user:booking-canceled', $data['data']['type']);
    }

    public function test_to_fcm_falls_back_to_push_data_type()
    {
        $notification = new BaseNotification('Subject', 'Message');
        $notification->pushData = ['type' => 'user:subscription-expired'];

        /** @var CloudMessage $message */
        $message = $notification->toFcm(null);
        $data = json_decode(json_encode($message), true);

        $this->assertEquals('user:subscription-expired', $data['data']['type']);
    }

    public function test_to_fcm_omits_type_when_unset()
    {
        $notification = new BaseNotification('Subject', 'Message');
        $notification->pushData = ['route' => '/billing'];

        /** @var CloudMessage $message */
        $message = $notification->toFcm(null);
        $data = json_decode(json_encode($message), true);

        $this->assertArrayNotHasKey('type', $data['data']);
    }
}
