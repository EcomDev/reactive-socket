<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class InMemoryStreamBufferTest extends TestCase
{
    /** @var InMemoryStreamBufferFactory */
    private $factory;

    /** @var boolean */
    private $disconnected = false;

    protected function setUp()
    {
        $this->factory = new InMemoryStreamBufferFactory();
    }

    /** @test */
    public function doesNotAttachStreamToEventEmitter()
    {
        $eventEmitter = new FakeEventEmitter();
        $stream = new NullStreamStub();

        $streamBuffer = $this->factory->create();

        $streamBuffer->attachResourceToEmitter($stream, $eventEmitter);

        $this->assertFalse($eventEmitter->hasStream($stream));
    }

    /** @test */
    public function doesNotDetachStreamFromEventEmitter()
    {
        $eventEmitter = new FakeEventEmitter();
        $stream = new NullStreamStub();

        $streamBuffer = $this->factory->create();

        $streamBuffer->detachResourceFromEmitter($stream, $eventEmitter);

        $this->assertFalse($eventEmitter->hasStream($stream));
    }

    /** @test */
    public function attachesStreamWithCustomResourcePassedToBuffer()
    {
        $eventEmitter = new FakeEventEmitter();
        $stream = new NullStreamStub();

        $streamBuffer = $this->factory->withCustomResource('Resource #1')->create();
        $streamBuffer->attachResourceToEmitter($stream, $eventEmitter);

        $this->assertTrue($eventEmitter->hasStreamWithResource($stream, 'Resource #1'));
    }

    /** @test */
    public function detachesStreamWithCustomResourcePassedToBuffer()
    {
        $eventEmitter = new FakeEventEmitter();
        $stream = new NullStreamStub();

        $streamBuffer = $this->factory->withCustomResource('Resource #2')->create();
        $streamBuffer->attachResourceToEmitter($stream, $eventEmitter);
        $streamBuffer->detachResourceFromEmitter($stream, $eventEmitter);

        $this->assertFalse($eventEmitter->hasStreamWithResource($stream, 'Resource #2'));
    }

    /** @test */
    public function writesDataIntoOwnBufferAndReadsFromIt()
    {
        $streamBuffer = $this->factory->create();

        $streamBuffer->write('Some data');
        $streamBuffer->writeToRemote();

        $streamBuffer->readFromRemote();
        $this->assertEquals('Some data', $streamBuffer->read());
    }

    /** @test */
    public function writesDataOnlyOnWriteToRemoteCall()
    {
        $streamBuffer = $this->factory->create();

        $streamBuffer->write('Not read data');

        $streamBuffer->readFromRemote();
        $this->assertEquals('', $streamBuffer->read());
    }

    /** @test */
    public function readsDataOnlyWhenReadFromRemoteInvoked()
    {
        $streamBuffer = $this->factory->create();

        $streamBuffer->write('Not read data');
        $streamBuffer->writeToRemote();

        $this->assertEquals('', $streamBuffer->read());
    }

    /** @test */
    public function writesMultipleInSingleWriteOperation()
    {
        $streamBuffer = $this->factory->create();

        $streamBuffer->write('Data#1');
        $streamBuffer->write('Data#2');
        $streamBuffer->write('Data#3');
        $streamBuffer->writeToRemote();
        $streamBuffer->readFromRemote();

        $this->assertEquals('Data#1Data#2Data#3', $streamBuffer->read());
    }

    /** @test */
    public function readsMultipleRemoteWritesInTheSingleReadFromRemote()
    {
        $streamBuffer = $this->factory->create();

        $streamBuffer->write('Data#1');
        $streamBuffer->write('Data#2');
        $streamBuffer->writeToRemote();
        $streamBuffer->write('Data#3');
        $streamBuffer->write('Data#4');
        $streamBuffer->writeToRemote();

        $streamBuffer->readFromRemote();

        $this->assertEquals('Data#1Data#2Data#3Data#4', $streamBuffer->read());
    }

    /** @test */
    public function readsMultipleRemoteWritesInMultipleReadsFromRemote()
    {
        $streamBuffer = $this->factory->create();

        $streamBuffer->write('Data#1');
        $streamBuffer->write('Data#2');
        $streamBuffer->writeToRemote();
        $streamBuffer->readFromRemote();
        $streamBuffer->write('Data#3');
        $streamBuffer->write('Data#4');
        $streamBuffer->writeToRemote();
        $streamBuffer->readFromRemote();

        $this->assertEquals('Data#1Data#2Data#3Data#4', $streamBuffer->read());
    }

    /** @test */
    public function everyReadOperationFlushesStream()
    {
        $streamBuffer = $this->factory->create();

        $streamBuffer->write('Data#1');
        $streamBuffer->writeToRemote();
        $streamBuffer->readFromRemote();
        $streamBuffer->read();

        $streamBuffer->write('Data#2');
        $streamBuffer->writeToRemote();
        $streamBuffer->readFromRemote();

        $this->assertEquals('Data#2', $streamBuffer->read());
    }

    /** @test */
    public function throwsExceptionWhenWriteOutsideOfBufferSize()
    {
        $streamBuffer = $this->factory
            ->withWriteBuffer(26)
            ->create();

        $streamBuffer->write('Data that fits into buffer');

        $this->expectException(WriteLimitReachedException::class);

        $streamBuffer->write('Limit reached');
    }

    /** @test */
    public function reportsNotFullStreamWhenReadBufferIsNotFull()
    {
        $streamBuffer = $this->factory
            ->withChunkSize(26)
            ->create();

        $streamBuffer->write('Data that fits into buffer');
        $streamBuffer->writeToRemote();

        $this->assertFalse($streamBuffer->isRemoteFull());
    }

    /** @test */
    public function reportFullStreamWhenReadBufferIsNotFull()
    {
        $streamBuffer = $this->factory
            ->withChunkSize(26)
            ->create();

        $streamBuffer->write('Data that fits into buffer');
        $streamBuffer->writeToRemote();
        $streamBuffer->write('Data not in stream as buffer is full');
        $streamBuffer->writeToRemote();

        $this->assertTrue($streamBuffer->isRemoteFull());
    }

    /** @test */
    public function resetsFullRemoteAfterDataWasDrained()
    {
        $streamBuffer = $this->factory
            ->withChunkSize(24)
            ->create();

        $streamBuffer->write('Data fits into buffer #1');
        $streamBuffer->writeToRemote();
        $streamBuffer->readFromRemote();
        $streamBuffer->write('Data fits into buffer #2');
        $streamBuffer->writeToRemote();

        $this->assertFalse($streamBuffer->isRemoteFull());
    }


    /** @test */
    public function reportsFullRemoteStreamWhenItIsClosed()
    {
        $streamBuffer = $this->factory->create();
        $streamBuffer->close();

        $this->assertTrue($streamBuffer->isRemoteFull());
    }

    /** @test */
    public function readsDataFromStreamUntilReadBufferIsReached()
    {
        $streamBuffer = $this->factory->withReadBuffer(6)
            ->create();

        $readData = [];

        $streamBuffer->write('Data#1Data#2Data#3');
        $streamBuffer->writeToRemote();

        $streamBuffer->readFromRemote();
        $readData[] = $streamBuffer->read();
        $streamBuffer->readFromRemote();
        $readData[] = $streamBuffer->read();
        $streamBuffer->readFromRemote();
        $readData[] = $streamBuffer->read();

        $this->assertEquals(
            ['Data#1', 'Data#2', 'Data#3'],
            $readData
        );
    }

    /** @test */
    public function flushesReadBufferAfterEachRead()
    {
        $streamBuffer = $this->factory->create();

        $streamBuffer->write('Some data');
        $streamBuffer->writeToRemote();
        $streamBuffer->readFromRemote();
        $streamBuffer->read();

        $this->assertEquals(
            '',
            $streamBuffer->read()
        );
    }

    /** @test */
    public function prohibitsWritesToClosedStream()
    {
        $streamBuffer = $this->factory->create();
        $streamBuffer->close();

        $this->expectException(WriteLimitReachedException::class);
        $streamBuffer->write('Some data that should rise exception');
    }

    /** @test */
    public function allowsFinishReadingDataFromStreamAfterClosed()
    {
        $streamBuffer = $this->factory->create();
        $streamBuffer->write('Some data in buffer before closing');
        $streamBuffer->writeToRemote();
        $streamBuffer->close();

        $streamBuffer->readFromRemote();
        $this->assertEquals('Some data in buffer before closing', $streamBuffer->read());
    }

    /** @test */
    public function afterClosingStreamRemoteWritesAreNotPerformed()
    {
        $streamBuffer = $this->factory->create();
        $streamBuffer->write('This data cannot be read');
        $streamBuffer->close();
        $streamBuffer->writeToRemote();

        $streamBuffer->readFromRemote();
        $this->assertEquals('', $streamBuffer->read());
    }

    /** @test */
    public function returnsAllNotWrittenDataOnClosedStream()
    {
        $streamBuffer = $this->factory->create();
        $streamBuffer->write('Written Data#1');
        $streamBuffer->writeToRemote();
        $streamBuffer->write('Un-written Data#1');
        $streamBuffer->write('Un-written Data#2');
        $streamBuffer->close();

        $streamBuffer->notifyConnectionClosedOrBroken(function ($data) {
            $this->assertEquals(
                ['Un-written Data#1', 'Un-written Data#2'],
                $data
            );
        });
    }

    /** @test */
    public function doesNotNotifyAboutDisconnectWhenStreamIsOpen()
    {
        $streamBuffer = $this->factory->create();
        $streamBuffer->write('Data#1');
        $streamBuffer->write('Data#2');
        $streamBuffer->write('Data#3');

        $streamBuffer->notifyConnectionClosedOrBroken($this->disconnectNotification());

        $this->assertFalse($this->disconnected);
    }

    private function disconnectNotification(): callable
    {
        return function () {
            $this->disconnected = true;
        };
    }
}
