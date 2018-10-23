<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class FakeStreamClientTest extends TestCase
{
    /** @var FakeStreamClient */
    private $client;

    protected function setUp()
    {
        $this->client = FakeStreamClient::create(20);
    }

    /** @test */
    public function storesWrittenDataForLaterReading()
    {
        $this->client->write('Data #1');
        $this->client->write('Data #2');

        $this->assertEquals(
            ['Data #1', 'Data #2'],
            [
                $this->client->read(),
                $this->client->read()
            ]
        );
    }
    
    /** @test */
    public function readsEmptyStringWhenNoDataIsWritten()
    {
        $this->assertEquals('', $this->client->read());
    }
    
    /** @test */
    public function throwsExceptionWhenWriteLimitIsReached()
    {
        $this->client->write('Long Data!');
        $this->client->write('Long Data!');

        $this->expectException(WriteLimitReachedException::class);
        $this->client->write('Data that cannot fit into buffer');
    }

    /** @test */
    public function readingFlushesWrittenDataBuffer()
    {
        $this->client->write('Long Data1');
        $this->client->write('Long Data2');

        $this->client->read();
        $this->client->write('Long Data3');

        $this->assertEquals(
            ['Long Data2', 'Long Data3'],
            [$this->client->read(), $this->client->read()]
        );
    }

    /** @test */
    public function closingStreamPreventsStreamFromAcceptingWrites()
    {
        $this->client->close();
        $this->expectException(WriteLimitReachedException::class);
        $this->client->write('Data closed');
    }

    /** @test */
    public function closingStreamAllowsToReadDataWrittenPreviously()
    {
        $this->client->write('Data #1');
        $this->client->close();

        $this->assertEquals('Data #1', $this->client->read());
    }
}
