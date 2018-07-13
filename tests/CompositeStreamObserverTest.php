<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class CompositeStreamObserverTest extends TestCase
{
    /** @var CompositeStreamObserver */
    private $observer;

    /** @var FakeStreamObserver[] */
    private $observers;

    protected function setUp()
    {
        $this->observers = [
            new FakeStreamObserver(),
            new FakeStreamObserver(),
            new FakeStreamObserver()
        ];

        $this->observer = CompositeStreamObserver::createFromObservers(...$this->observers);
    }

    /** @test */
    public function notifiesAllObserversOfNewConnection()
    {
        $stream = new NullStreamStub();

        $this->observer->handleConnected($stream);

        $this->assertObserversState(
            StreamObserverNotificationState::createEmpty()
                ->withConnectedNotification($stream)
        );
    }

    /** @test */
    public function notifiesAllObserversOfWritableStream()
    {
        $stream = new NullStreamStub();

        $this->observer->handleWritable($stream, new NullStreamClientStub());

        $this->assertObserversState(
            StreamObserverNotificationState::createEmpty()
                ->withWritableNotification($stream)
        );
    }

    /** @test */
    public function notifiesAllObserversOfReadableStream()
    {
        $stream = new NullStreamStub();

        $this->observer->handleReadable($stream, new NullStreamClientStub());

        $this->assertObserversState(
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($stream, '')
        );
    }

    /** @test */
    public function notifiesAllObserversOfDisconnectedStream()
    {
        $stream = new NullStreamStub();

        $this->observer->handleDisconnected($stream, 'Data #1', 'Data #2');

        $this->assertObserversState(
            StreamObserverNotificationState::createEmpty()
                ->withDisconnectedNotification($stream, ['Data #1', 'Data #2'])
        );
    }

    private function assertObserversState(StreamObserverNotificationState $expectedNotifications)
    {
        array_walk($this->observers, function (FakeStreamObserver $observer) use ($expectedNotifications) {
            $this->assertEquals($expectedNotifications, $observer->fetchNotifications());
        });
    }
}
