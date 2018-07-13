<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SocketTesterPoolTest extends TestCase
{
    /**
     * @var SocketTesterPool
     */
    private $socketTesterPool;

    /** @var resource[][] */
    private $poolOfSockets;

    protected function setUp()
    {
        $this->poolOfSockets = [
            stream_socket_pair(
                STREAM_PF_UNIX,
                STREAM_SOCK_STREAM,
                STREAM_IPPROTO_IP
            ),
            stream_socket_pair(
                STREAM_PF_UNIX,
                STREAM_SOCK_STREAM,
                STREAM_IPPROTO_IP
            )
        ];
        $this->socketTesterPool = new SocketTesterPool($this->poolOfSockets);
    }

    /** @test */
    public function usesPoolOfSocketsProvidedOnConstruct()
    {
        $this->assertEquals(
            [
                new SocketTester(...$this->poolOfSockets[1]),
                new SocketTester(...$this->poolOfSockets[0])
            ],
            [
                $this->socketTesterPool->acquireSocket(),
                $this->socketTesterPool->acquireSocket()
            ]
        );
    }

    /** @test */
    public function addsNewSocketsToPoolIfThereIsNotEnoughOfThem()
    {
        $previousSockets = [
            $this->socketTesterPool->acquireSocket(),
            $this->socketTesterPool->acquireSocket()
        ];


        $this->assertNotContains(
            $this->socketTesterPool->acquireSocket(),
            $previousSockets
        );
    }

    /** @test */
    public function reusesStreamAfterRelease()
    {
        $socket = $this->socketTesterPool->acquireSocket();
        $this->socketTesterPool->releaseSocket($socket);

        $this->assertEquals(
            new SocketTester(...$this->poolOfSockets[0]),
            $this->socketTesterPool->acquireSocket()
        );
    }

    /** @test */
    public function keepsTrackOfAcquiredStreamsForRelease()
    {
        $socket = $this->socketTesterPool->acquireSocket();
        $this->socketTesterPool->releaseSocket($socket);
        $this->socketTesterPool->releaseSocket($socket);

        $this->assertNotEquals(
            [$socket, $socket],
            [$this->socketTesterPool->acquireSocket(), $this->socketTesterPool->acquireSocket()]
        );
    }

    /** @test */
    public function releasesAnyPreviouslyAcquiredStream()
    {
        $firstReleased = $this->socketTesterPool->acquireSocket();
        $secondReleased = $this->socketTesterPool->acquireSocket();

        $this->socketTesterPool->releaseSocket($firstReleased);
        $this->socketTesterPool->releaseSocket($secondReleased);

        $this->assertEquals(
            [$firstReleased, $secondReleased],
            [$this->socketTesterPool->acquireSocket(), $this->socketTesterPool->acquireSocket()]
        );
    }

    /** @test */
    public function restoresStreamUnderTestBlockingStatus()
    {
        $tester = $this->socketTesterPool->acquireSocket();
        stream_set_blocking($this->poolOfSockets[1][1], false);

        $this->socketTesterPool->releaseSocket($tester);

        $this->assertArraySubset(['blocked' => true], stream_get_meta_data($this->poolOfSockets[1][1]));
    }
}
