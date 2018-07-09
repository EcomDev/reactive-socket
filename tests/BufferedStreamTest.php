<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class BufferedStreamTest extends TestCase
{
    /** @var BufferedStream */
    private $stream;

    /** @var InMemoryStreamBuffer */
    private $streamBuffer;

    protected function setUp()
    {
        $this->streamBuffer = (new InMemoryStreamBufferFactory())
            ->withReadBuffer(48)
            ->withChunkSize(24)
            ->withWriteBuffer(24)
            ->withCustomResource('Stream #1')
            ->create();

        $this->stream = (new BufferedStreamFactory())
            ->createFromBuffer($this->streamBuffer);
    }

    /** @test */
    public function attachesUnderlyingBufferToEventEmitter()
    {
        $emitter = new FakeEventEmitter();

        $this->stream->attach($emitter);

        $this->assertTrue($emitter->hasStreamWithResource($this->stream, 'Stream #1'));
    }

    /** @test */
    public function notifiesObserverWithReadableNotifications()
    {
        $streamObserver = $this->createObserver();

        $this->streamBuffer->write('Read data in buffer #001');
        $this->streamBuffer->writeToRemote();
        $this->stream->notifyReadable($streamObserver);

        $this->streamBuffer->write('Read data in buffer #002');
        $this->streamBuffer->writeToRemote();
        $this->stream->notifyReadable($streamObserver);

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($this->stream, 'Read data in buffer #001')
                ->withReadableNotification($this->stream, 'Read data in buffer #002'),
            $streamObserver->fetchNotifications()
        );
    }

    /** @test */
    public function notifiesObserverWithWritableNotifications()
    {
        $streamObserver = $this->createObserver();

        $this->stream->notifyWritable($streamObserver);

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withWritableNotification($this->stream),
            $streamObserver->fetchNotifications()
        );
    }

    /** @test */
    public function writesDataIntoRemoteWriteNotification()
    {
        $this->stream->notifyWritable($this->createObserverWithWrite('Some data in buffer #001'));

        $this->streamBuffer->readFromRemote();

        $this->assertEquals(
            'Some data in buffer #001',
            $this->streamBuffer->read()
        );
    }

    /** @test */
    public function pausesWritingWhenRemoteIsFull()
    {
        $this->overflowPipeBuffer('Some data in buffer #001');

        $this->stream->notifyWritable($this->createObserverWithWrite('Some data in buffer #002'));
        $this->stream->notifyWritable($this->createObserverWithWrite('Some data in buffer #003'));

        $this->streamBuffer->readFromRemote();

        $this->assertEquals('Some data in buffer #001', $this->streamBuffer->read());
    }

    /** @test */
    public function resumesWritingWhenRemoteIsDrained()
    {
        $this->overflowPipeBuffer('Some data in buffer #001');

        $this->streamBuffer->readFromRemote();
        $this->stream->notifyWritable($this->createObserverWithWrite('Some data in buffer #002'));
        $this->streamBuffer->readFromRemote();

        $this->assertEquals('Some data in buffer #001Some data in buffer #002', $this->streamBuffer->read());
    }

    /** @test */
    public function attachesToMultipleEventEmitters()
    {
        $emitterOne = new FakeEventEmitter();
        $emitterTwo = new FakeEventEmitter();

        $this->stream->attach($emitterOne);
        $this->stream->attach($emitterTwo);

        $this->assertEquals(
            [
                true,
                true
            ],
            [
                $emitterOne->hasStreamWithResource($this->stream, 'Stream #1'),
                $emitterTwo->hasStreamWithResource($this->stream, 'Stream #1')
            ]
        );
    }

    /** @test */
    public function detachesFromEventEmitterWhenConnectionIsBrokenOnWrite()
    {
        $emitter = new FakeEventEmitter();

        $this->stream->attach($emitter);

        $this->streamBuffer->close();

        $this->stream->notifyWritable($this->createObserver());

        $this->assertFalse($emitter->hasStreamWithResource($this->stream, 'Stream #1'));
    }

    /** @test */
    public function detachesFromEventEmitterWhenConnectionIsBrokenOnRead()
    {
        $emitter = new FakeEventEmitter();

        $this->stream->attach($emitter);

        $this->streamBuffer->close();

        $this->stream->notifyReadable($this->createObserver());

        $this->assertFalse($emitter->hasStreamWithResource($this->stream, 'Stream #1'));
    }

    /** @test */
    public function doesNotDetachFromEventEmitterWhenConnectionIsOpen()
    {
        $emitter = new FakeEventEmitter();

        $this->stream->attach($emitter);
        $this->stream->notifyReadable($this->createObserver());
        $this->stream->notifyWritable($this->createObserver());

        $this->assertTrue($emitter->hasStreamWithResource($this->stream, 'Stream #1'));
    }

    /** @test */
    public function detachesFromAllAddedEventEmitterWhenConnectionIsBroken()
    {
        $emitterOne = new FakeEventEmitter();
        $emitterTwo = new FakeEventEmitter();

        $this->stream->attach($emitterOne);
        $this->stream->attach($emitterTwo);

        $this->streamBuffer->close();

        $this->stream->notifyReadable($this->createObserver());
        $this->stream->notifyWritable($this->createObserver());

        $this->assertFalse($emitterOne->hasStreamWithResource($this->stream, 'Stream #1'));
        $this->assertFalse($emitterTwo->hasStreamWithResource($this->stream, 'Stream #1'));
    }

    /** @test */
    public function notifiesOfUnsentDataFromBufferWhenClosed()
    {
        $this->streamBuffer->write('Data #1');
        $this->streamBuffer->write('Data #2');
        $this->streamBuffer->close();
        $this->stream->notifyReadable($this->createObserver());

        $observer = $this->createObserver();

        $this->stream->notifyDisconnected($observer);

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withDisconnectedNotification($this->stream, ['Data #1', 'Data #2']),
            $observer->fetchNotifications()
        );
    }


    private function createObserverWithWrite($data): FakeStreamObserver
    {
        return $this->createObserver()->withWrite($data);
    }


    private function overflowPipeBuffer(string $data): void
    {
        $this->streamBuffer->write($data);
        $this->streamBuffer->writeToRemote();
        $this->streamBuffer->writeToRemote();
    }

    private function createObserver(): FakeStreamObserver
    {
        return new FakeStreamObserver();
    }
}
