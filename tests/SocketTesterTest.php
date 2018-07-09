<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SocketTesterTest extends TestCase
{
    /** @var SocketTester */
    private $socketTester;

    /** @var resource */
    private $remoteSocket;

    /** @var resource */
    private $localSocket;

    protected function setUp()
    {
        list($this->remoteSocket, $this->localSocket) = stream_socket_pair(
            STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP
        );

        $this->socketTester = new SocketTester(
            $this->remoteSocket,
            $this->localSocket
        );

        stream_set_blocking($this->localSocket, false);
    }

    /** @test */
    public function writesDataInFirstSocketSoItCanBeReadFromSecondOne()
    {
        $this->socketTester->writeToRemote('I am data written to remote!');

        $this->assertEquals(['I am data written to remote!'], $this->readFromSockets($this->localSocket));
    }

    /** @test */
    public function assertsContentBeingWrittenToRemoteIsNotCorrect()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that two strings are equal.');

        $this->writeToSockets('I am actual data written to remote', $this->localSocket);

        $this->socketTester->assertRemoteContent('I am expected data written to remote');
    }

    /** @test */
    public function passesAssertionTestWhenDataIsCorrect()
    {
        $this->writeToSockets('I am data written to remote', $this->localSocket);

        $this->socketTester->assertRemoteContent('I am data written to remote');
    }

    /** @test */
    public function restoresBlockingStreamStatusWhenGetsReleased()
    {
        stream_set_blocking($this->localSocket, false);

        $this->socketTester->release();

        $this->assertArraySubset(['blocked' => true], stream_get_meta_data($this->localSocket));
    }

    /** @test */
    public function drainsAllStreamsOnRelease()
    {
        $data = str_repeat('Some data on tested socket. ', 200);

        $this->writeToSockets($data, $this->remoteSocket, $this->localSocket);

        $this->socketTester->release();


        $this->assertEquals(
            ['', ''],
            $this->readFromSockets($this->remoteSocket, $this->localSocket)
        );
    }

    /** @test */
    public function closesOpenStreamsAfterPoolsIsDestroyed()
    {
        $this->socketTester = null;

        $this->assertNotContains(
            true,
            [is_resource($this->remoteSocket), is_resource($this->localSocket)]
        );
    }

    /** @test */
    public function readsDataFromBuffer()
    {
        $this->socketTester->readRemoteIntoBuffer();
        $this->writeToSockets('Some value', $this->localSocket);
        $this->socketTester->readRemoteIntoBuffer();

        $this->assertEquals([''], $this->readFromSockets($this->remoteSocket));
    }

    /** @test */
    public function allowsToValidateReadBuffer()
    {
        $this->socketTester->readRemoteIntoBuffer();
        $this->writeToSockets('Actual value', $this->localSocket);
        $this->socketTester->readRemoteIntoBuffer();

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Failed asserting that two arrays are equal.');

        $this->socketTester->assertRemoteReadBuffer(['', 'Expected value']);
    }

    /** @test */
    public function passesReadBufferValidation()
    {
        $this->socketTester->readRemoteIntoBuffer();
        $this->writeToSockets('Value I wrote', $this->localSocket);
        $this->socketTester->readRemoteIntoBuffer();

        $this->socketTester->assertRemoteReadBuffer(['', 'Value I wrote']);
    }

    /** @test */
    public function readBufferClearedOnRelease()
    {
        $this->writeToSockets('Value I wrote', $this->localSocket);
        $this->socketTester->readRemoteIntoBuffer();

        $this->socketTester->release();

        $this->socketTester->assertRemoteReadBuffer([]);
    }

    /** @test */
    public function detectsUnbufferedWriteLimit()
    {
        $this->doNotBufferSockets();

        $writeLimit = $this->socketTester->detectWriteLimit();

        $bytesWritten = @fwrite($this->remoteSocket, str_repeat('a', $writeLimit + 100));

        $this->assertEquals($writeLimit, $bytesWritten);
    }

    /** @test */
    public function allowsClosingRemoteSocketForTesting()
    {
        $this->socketTester->closeRemote();
        $this->assertFalse(is_resource($this->remoteSocket));
    }

    /** @test */
    public function allowsClosingLocalSocketForTesting()
    {
        $this->socketTester->closeLocal();
        $this->assertFalse(is_resource($this->localSocket));
    }

    /** @test */
    public function revealsLocalSocket()
    {
        $this->assertEquals(
            $this->localSocket,
            $this->socketTester->revealSocket()
        );
    }

    private function writeToSockets($data, ...$sockets): void
    {
        foreach ($sockets as $socket) {
            fwrite($socket, $data);
        }
    }

    private function readFromSockets(...$sockets): array
    {
        $data = [];

        foreach ($sockets as $socket) {
            $isBlocked = stream_get_meta_data($socket)['blocked'];

            if ($isBlocked) {
                stream_set_blocking($this->localSocket, false);
            }

            $data[] = stream_get_contents($socket, 1024);

            if ($isBlocked) {
                stream_set_blocking($socket, true);
            }
        }

        return $data;
    }

    private function doNotBufferSockets(): void
    {
        stream_set_write_buffer($this->remoteSocket, 0);
        stream_set_read_buffer($this->localSocket, 0);
    }
}
