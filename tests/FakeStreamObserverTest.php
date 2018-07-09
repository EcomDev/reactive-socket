<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class FakeStreamObserverTest extends TestCase
{
    /** @var FakeStreamObserver */
    private $observer;

    protected function setUp()
    {
        $this->observer = new FakeStreamObserver();
    }

    /** @test */
    public function emptyWhenNothingIsNotified()
    {
        $this->assertEquals(
            StreamObserverNotificationState::createEmpty(),
            $this->observer->fetchNotifications()
        );
    }


    /** @test */
    public function registersNotificationsOfConnectedStream()
    {
        $stream = new NullStreamStub();
        $this->observer->handleConnected($stream);

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withConnectedNotification($stream),
            $this->observer->fetchNotifications()
        );
    }

    /** @test */
    public function registersDisconnectedStream()
    {
        $stream = new NullStreamStub();
        $this->observer->handleDisconnected($stream);

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withDisconnectedNotification($stream, []),
            $this->observer->fetchNotifications()
        );
    }

    /** @test */
    public function registersDataForDisconnectedStream()
    {
        $stream = new NullStreamStub();
        $this->observer->handleDisconnected($stream, '1', '2', '3');

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withDisconnectedNotification($stream, ['1', '2', '3']),
            $this->observer->fetchNotifications()
        );
    }


    /** @test */
    public function writesDataToStreamAssignedWhenWritten()
    {
        $observer = $this->observer->withWrite('Some data #1')
            ->withWrite('Some data #2');

        $buffer = (new InMemoryStreamBufferFactory())->create();

        $observer->handleWritable(new NullStreamStub(), $buffer);

        $buffer->writeToRemote();
        $buffer->readFromRemote();

        $this->assertEquals('Some data #1Some data #2', $buffer->read());
    }

    /** @test */
    public function reportsWritableInvocation()
    {
        $stream = new NullStreamStub();

        $this->observer->handleWritable($stream, new NullStreamClientStub());

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()->withWritableNotification($stream),
            $this->observer->fetchNotifications()
        );
    }

    /** @test */
    public function reportsReadableHasBeenInvoked()
    {
        $stream = new NullStreamStub();

        $this->observer->handleReadable($stream, new NullStreamClientStub());

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($stream, ''),
            $this->observer->fetchNotifications()
        );
    }

    /** @test */
    public function reportsReadableHasBeenInvokedAndRecordsReadStreamData()
    {
        $stream = new NullStreamStub();

        $this->observer->handleReadable($stream, $this->createStreamClientWithDataToRead('Data#1'));

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($stream, 'Data#1'),
            $this->observer->fetchNotifications()
        );
    }

    /** @test */
    public function propertyReportsNotificationSequence()
    {
        $streamOne = new NullStreamStub();
        $streamTwo = new NullStreamStub();

        $this->observer->handleConnected($streamOne);
        $this->observer->handleConnected($streamTwo);
        $this->observer->handleWritable($streamTwo, new NullStreamClientStub());
        $this->observer->handleReadable($streamOne, $this->createStreamClientWithDataToRead('Stream #1'));
        $this->observer->handleWritable($streamOne, new NullStreamClientStub());
        $this->observer->handleReadable($streamTwo, $this->createStreamClientWithDataToRead('Stream #2'));
        $this->observer->handleDisconnected($streamOne, 'some', 'data');
        $this->observer->handleWritable($streamTwo, new NullStreamClientStub());

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withConnectedNotification($streamOne)
                ->withConnectedNotification($streamTwo)
                ->withWritableNotification($streamTwo)
                ->withReadableNotification($streamOne, 'Stream #1')
                ->withWritableNotification($streamOne)
                ->withReadableNotification($streamTwo, 'Stream #2')
                ->withDisconnectedNotification($streamOne, ['some', 'data'])
                ->withWritableNotification($streamTwo),
            $this->observer->fetchNotifications()
        );
    }


    private function createStreamClientWithDataToRead($data): StreamClient
    {
        $streamClient = (new InMemoryStreamBufferFactory())->create();
        $streamClient->write($data);
        $streamClient->writeToRemote();
        $streamClient->readFromRemote();
        return $streamClient;
    }
}
