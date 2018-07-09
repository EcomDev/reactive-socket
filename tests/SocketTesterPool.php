<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Pool of socket testers
 *
 * Automatically re-uses sockets, safe to use with PHPUnit parallel feature
 */
class SocketTesterPool
{
    /**
     * @var SocketTester[]
     */
    private $socketTesterPool = [];

    /**
     * @var SocketTester[]
     */
    private $acquiredTesters = [];

    public function __construct(array $sockets = [])
    {
        $this->initializeSocketPool($sockets);
    }

    /**
     * Acquires socket tester from available socket pool
     *
     * If not enough sockets in pool, it will create new ones
     */
    public function acquireSocket()
    {
        if (!$this->socketTesterPool) {
            $this->socketTesterPool[] = new SocketTester(...stream_socket_pair(
                STREAM_PF_UNIX,
                STREAM_SOCK_STREAM,
                STREAM_IPPROTO_IP
            ));
        }

        $tester = array_pop($this->socketTesterPool);
        $this->acquiredTesters[] = $tester;
        return $tester;
    }

    /**
     * Releases previously acquired socket tester
     */
    public function releaseSocket(SocketTester $socket): void
    {
        if (($index = array_search($socket, $this->acquiredTesters, true)) !== false) {
            unset($this->acquiredTesters[$index]);
            array_unshift($this->socketTesterPool, $socket);
            $socket->release();
        }
    }

    private function initializeSocketPool(array $sockets): void
    {
        $this->socketTesterPool = array_map(
            function ($pair) {
                return new SocketTester(...$pair);
            },
            $sockets
        );
    }
}
