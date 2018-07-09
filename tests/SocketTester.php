<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\Assert;

class SocketTester
{
    /** @var resource */
    private $verifyStream;

    /** @var resource */
    private $socketUnderTest;

    /** @var string[] */
    private $readBuffer = [];

    public function __construct($verifyStream, $socketUnderTest)
    {
        $this->verifyStream = $verifyStream;
        $this->socketUnderTest = $socketUnderTest;
        $this->makeVerifyStreamAsync();
    }

    public function writeToRemote(string $data): void
    {
        fwrite($this->verifyStream, $data);
    }

    public function createSystemBuffer(callable $systemBufferFactory): StreamBuffer
    {
        return $systemBufferFactory($this->socketUnderTest);
    }

    public function assertRemoteContent($expectedValue)
    {
        Assert::assertEquals($expectedValue, stream_get_contents($this->verifyStream, strlen($expectedValue)));
    }

    public function readRemoteIntoBuffer()
    {
        $this->readBuffer[] = fread($this->verifyStream, 10*1024);
    }

    public function release()
    {
        stream_set_blocking($this->socketUnderTest, false);
        $this->drainSocket($this->socketUnderTest);
        $this->drainSocket($this->verifyStream);
        stream_set_blocking($this->socketUnderTest, true);
        $this->readBuffer = [];
    }

    public function __destruct()
    {
        $this->closeRemote();
        $this->closeLocal();
    }

    public function closeRemote(): void
    {
        if (is_resource($this->verifyStream)) {
            fclose($this->verifyStream);
        }
    }

    public function assertRemoteReadBuffer($expectedBuffer)
    {
        Assert::assertEquals($expectedBuffer, $this->readBuffer);
    }

    private function drainSocket($stream): void
    {
        do {
            $readData = fread($stream, 1024);
        } while ($readData !== '');
    }

    private function makeVerifyStreamAsync(): void
    {
        stream_set_blocking($this->verifyStream, false);
    }

    public function detectWriteLimit()
    {
        $dataSize = 100000;
        $writtenSize = @fwrite($this->verifyStream, str_repeat('0', $dataSize));
        fread($this->socketUnderTest, $writtenSize);

        return $writtenSize;
    }

    public function closeLocal()
    {
        if (is_resource($this->socketUnderTest)) {
            fclose($this->socketUnderTest);
        }
    }

    public function revealSocket()
    {
        return $this->socketUnderTest;
    }
}
