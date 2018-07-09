<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class NativeSystemStreamBufferTest extends TestCase
{
    /** @var SocketTesterPool */
    private static $streamPool;

    /** @var SocketTester */
    private $socket;

    /** @var SocketStreamBufferFactory */
    private $factory;

    /** @var boolean */
    private $disconnected = false;

    public static function setUpBeforeClass()
    {
        self::$streamPool = new SocketTesterPool();
    }

    public static function tearDownAfterClass()
    {
        self::$streamPool = null;
    }

    protected function setUp()
    {
        $this->socket = self::$streamPool->acquireSocket();
        $this->factory = new SocketStreamBufferFactory();
    }

    protected function tearDown()
    {
        if ($this->socket) {
            self::$streamPool->releaseSocket($this->socket);
        }
    }

    /** @test */
    public function writesDataToRemoteOnlyWhenAsked()
    {
        $streamBuffer = $this->createStreamBuffer();
        $streamBuffer->write('Hello World');

        $this->socket->readRemoteIntoBuffer();
        $streamBuffer->writeToRemote();

        $this->socket->readRemoteIntoBuffer();

        $this->socket->assertRemoteReadBuffer(['', 'Hello World']);
    }

    /** @test */
    public function readsDataFromRemoteOnlyWhenAsked()
    {
        $streamBuffer = $this->createStreamBuffer();
        $this->socket->writeToRemote('Some data');
        $readData = [];
        $readData[] = $streamBuffer->read();
        $streamBuffer->readFromRemote();
        $readData[] = $streamBuffer->read();

        $this->assertEquals(
            [
                '',
                'Some data'
            ],
            $readData
        );
    }

    /** @test */
    public function combinesMultipleReadDataFromRemoteIntoSingleValue()
    {
        $streamBuffer = $this->createStreamBuffer();
        $this->socket->writeToRemote('First data|');
        $streamBuffer->readFromRemote();
        $this->socket->writeToRemote('Second data|');
        $streamBuffer->readFromRemote();

        $this->assertEquals('First data|Second data|', $streamBuffer->read());
    }

    /** @test */
    public function flushesReadBufferAfterRead()
    {
        $streamBuffer = $this->createStreamBuffer();
        $this->socket->writeToRemote('Data in buffer');
        $streamBuffer->readFromRemote();
        $streamBuffer->read();

        $this->assertEquals('', $streamBuffer->read());
    }

    /** @test */
    public function doesNotReadAfterReadBufferHasBeenFilled()
    {
        $streamBuffer = $this->createStreamBufferWithReadBufferSize(18);

        $this->socket->writeToRemote('Data within buffer');
        $this->socket->writeToRemote('Data out side of read buffer');

        $streamBuffer->readFromRemote();
        $streamBuffer->readFromRemote();

        $this->assertEquals('Data within buffer', $streamBuffer->read());
    }

    /** @test */
    public function readsDataFromRemoteWhenBufferIsFlushed()
    {
        $streamBuffer = $this->createStreamBufferWithReadBufferSize(12);

        $this->socket->writeToRemote('Data#1');
        $this->socket->writeToRemote('Data#2');
        $streamBuffer->readFromRemote();
        $streamBuffer->read();

        $this->socket->writeToRemote('Data#3');
        $this->socket->writeToRemote('Data#4');
        $streamBuffer->readFromRemote();

        $this->assertEquals('Data#3Data#4', $streamBuffer->read());
    }

    /** @test */
    public function prohibitsWritingDataWhenWriteBufferIsFull()
    {
        $streamBuffer = $this->createStreamBufferWithWriteBufferSize(12);

        $streamBuffer->write('Data#1');
        $streamBuffer->write('Data#2');

        $this->expectException(WriteLimitReachedException::class);

        $streamBuffer->write('Data#3');
    }


    /** @test */
    public function writesCompleteBufferInSingleWriteToRemoteCall()
    {
        $streamBuffer = $this->createStreamBuffer();

        $streamBuffer->write('Data#1');
        $streamBuffer->write('Data#2');
        $streamBuffer->writeToRemote();
        $this->socket->readRemoteIntoBuffer();

        $streamBuffer->write('Data#3');
        $streamBuffer->write('Data#4');
        $streamBuffer->writeToRemote();
        $this->socket->readRemoteIntoBuffer();

        $this->socket->assertRemoteReadBuffer(['Data#1Data#2', 'Data#3Data#4']);
    }

    /** @test */
    public function allowsWritingToBufferWhenBufferHasBeenWrittenToRemote()
    {
        $streamBuffer = $this->createStreamBufferWithWriteBufferSize(14);

        $streamBuffer->write('Data#1');
        $streamBuffer->write('Data#2');
        $streamBuffer->writeToRemote();
        $streamBuffer->write('Data#3');
        $streamBuffer->writeToRemote();

        $this->socket->assertRemoteContent('Data#1Data#2Data#3');
    }


    /** @test */
    public function batchesWritesWhenBufferIsBiggerThanAmountWrittenToRemote()
    {
        $osWriteLimit = $this->socket->detectWriteLimit();

        $streamBuffer = $this->createStreamBufferWithWriteBufferSize(
            $osWriteLimit * 2
        );

        $streamBuffer->write(str_repeat('a', $osWriteLimit * 2));
        $streamBuffer->writeToRemote();
        $this->socket->readRemoteIntoBuffer();
        $streamBuffer->writeToRemote();
        $this->socket->readRemoteIntoBuffer();
        $this->socket->assertRemoteReadBuffer([
            str_repeat('a', $osWriteLimit),
            str_repeat('a', $osWriteLimit)
        ]);
    }

    /** @test */
    public function reportsRemoteAsNotFullWhenJustEnoughBytesWrittenButNotOverflowing()
    {
        $osWriteLimit = $this->socket->detectWriteLimit();

        $streamBuffer = $this->createStreamBufferWithWriteBufferSize($osWriteLimit * 2);

        $streamBuffer->write(str_repeat('a', $osWriteLimit));
        $streamBuffer->writeToRemote();

        $this->assertFalse($streamBuffer->isRemoteFull());
    }

    /** @test */
    public function reportsRemoteAsFullWhenPipeIsFull()
    {
        $osWriteLimit = $this->socket->detectWriteLimit();

        $streamBuffer = $this->createStreamBufferWithWriteBufferSize(
            $osWriteLimit * 2
        );

        $streamBuffer->write(str_repeat('a', $osWriteLimit + 1));
        $streamBuffer->writeToRemote();

        $this->assertTrue($streamBuffer->isRemoteFull());
    }

    /** @test */
    public function reportsRemoteNotFullWhenDataWasDrainedOnRemote()
    {
        $osWriteLimit = $this->socket->detectWriteLimit();

        $streamBuffer = $this->createStreamBufferWithWriteBufferSize(
            $osWriteLimit * 2
        );

        $streamBuffer->write(str_repeat('a', $osWriteLimit + 1));
        $streamBuffer->writeToRemote();
        $this->socket->readRemoteIntoBuffer();
        $streamBuffer->writeToRemote();

        $this->assertFalse($streamBuffer->isRemoteFull());
    }

    /** @test */
    public function reportsRemoteAsFullWhenPipeIsClosed()
    {
        $streamBuffer = $this->createStreamBuffer();
        $streamBuffer->close();

        $this->assertTrue($streamBuffer->isRemoteFull());
    }

    /** @test */
    public function writeOnBrokenConnectionIsProhibited()
    {
        $streamBuffer = $this->createStreamBuffer();
        $this->socket->closeRemote();

        $streamBuffer->write('Some data on closed connection');
        $streamBuffer->writeToRemote();

        $this->socket = null;

        $this->expectException(WriteLimitReachedException::class);

        $streamBuffer->write('Some data that should rise exception');
    }

    /** @test */
    public function readOnBrokenRemoteConnectionAlwaysReturnsEmptyData()
    {
        $streamBuffer = $this->createStreamBuffer();
        $this->socket->closeRemote();
        $streamBuffer->readFromRemote();

        $this->socket = null;

        $this->assertEquals('', $streamBuffer->read());
    }

    /** @test */
    public function readOnBrokenLocalConnectionAlwaysReturnsEmptyData()
    {
        $streamBuffer = $this->createStreamBuffer();
        $this->socket->closeLocal();
        $streamBuffer->readFromRemote();

        $this->socket = null;

        $this->assertEquals('', $streamBuffer->read());
    }

    /** @test */
    public function returnsNotWrittenDataOnDisconnect()
    {
        $streamBuffer = $this->createStreamBuffer();

        $streamBuffer->write('Data that was delivered');
        $streamBuffer->writeToRemote();
        $streamBuffer->write('Data that was not delivered #1');
        $streamBuffer->write('Data that was not delivered #2');

        $this->socket->closeRemote();
        $streamBuffer->writeToRemote();

        $this->socket = null;

        $streamBuffer->notifyConnectionClosedOrBroken(function ($data) {
            $this->assertEquals(
                [
                    'Data that was not delivered #1',
                    'Data that was not delivered #2',
                ],
                $data
            );
        });
    }

    /** @test */
    public function notifiesOnDisconnect()
    {
        $streamBuffer = $this->createStreamBuffer();
        $streamBuffer->write('Some data');

        $this->socket->closeRemote();
        $streamBuffer->writeToRemote();

        $this->socket = null;

        $streamBuffer->notifyConnectionClosedOrBroken($this->disconnectNotification());

        $this->assertTrue($this->disconnected);
    }

    /** @test */
    public function returnsPartiallyNotWrittenDataOnDisconnect()
    {
        $writeSize = $this->socket->detectWriteLimit() * 2;

        $streamBuffer = $this->createStreamBufferWithWriteBufferSize($writeSize);

        $streamBuffer->write(str_repeat('0', $writeSize));
        $streamBuffer->writeToRemote();
        $this->socket->closeRemote();
        $streamBuffer->writeToRemote();

        $this->socket = null;

        $streamBuffer->notifyConnectionClosedOrBroken(function ($data) use ($writeSize) {
            $this->assertEquals(
                [str_repeat('0', $writeSize)],
                $data
            );
        });
    }

    /** @test */
    public function notifiesOfDisconnectOnlyWhenConnectionIsBrokenConnection()
    {
        $streamBuffer = $this->createStreamBuffer();

        $streamBuffer->write('Data written #1');
        $streamBuffer->write('Data written #2');

        $streamBuffer->notifyConnectionClosedOrBroken($this->disconnectNotification());

        $this->assertFalse($this->disconnected);
    }

    /** @test */
    public function finishesWritesOnGracefulShutdown()
    {
        $streamBuffer = $this->createStreamBuffer();
        $streamBuffer->write('Data written #1');
        $streamBuffer->write('Data written #2');

        $streamBuffer->close();
        $streamBuffer->writeToRemote();

        $this->socket->assertRemoteContent('Data written #1Data written #2');
    }

    /** @test */
    public function refusesNewWritesAfterGracefulShutdown()
    {
        $streamBuffer = $this->createStreamBuffer();
        $streamBuffer->close();

        $this->expectException(WriteLimitReachedException::class);
        $streamBuffer->write('Some data');
    }

    /** @test */
    public function attachesUnderlyingSocketIntoEventEmitter()
    {
        $streamBuffer = $this->createStreamBuffer();

        $eventEmitter = new FakeEventEmitter();
        $stream = new NullStreamStub();

        $streamBuffer->attachResourceToEmitter($stream, $eventEmitter);

        $this->assertTrue($eventEmitter->hasStreamWithResource($stream, $this->socket->revealSocket()));
    }

    /** @test */
    public function detachesUnderlyingSocketIntoEventEmitter()
    {
        $streamBuffer = $this->createStreamBuffer();

        $eventEmitter = new FakeEventEmitter();
        $stream = new NullStreamStub();

        $streamBuffer->attachResourceToEmitter($stream, $eventEmitter);

        $streamBuffer->detachResourceFromEmitter($stream, $eventEmitter);

        $this->assertFalse($eventEmitter->hasStreamWithResource($stream, $this->socket->revealSocket()));
    }

    private function createStreamBufferWithReadBufferSize(int $readBufferSize): StreamBuffer
    {
        return $this->createStreamBufferThroughTester(
            $this->factory->withReadBuffer($readBufferSize)
        );
    }

    private function createStreamBufferWithWriteBufferSize(int $writeBufferSize): StreamBuffer
    {
        return $this->createStreamBufferThroughTester(
            $this->factory->withWriteBuffer($writeBufferSize)
        );
    }

    private function createStreamBuffer(): StreamBuffer
    {
        return $this->createStreamBufferThroughTester($this->factory);
    }

    private function createStreamBufferThroughTester(SocketStreamBufferFactory $factory): StreamBuffer
    {
        return $this->socket->createSystemBuffer([$factory, 'createFromSocket']);
    }

    private function disconnectNotification(): callable
    {
        return function () {
            $this->disconnected = true;
        };
    }
}
