<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class WriteBufferTest extends TestCase
{
    /** @var WriteBuffer */
    private $writeBuffer;

    protected function setUp()
    {
        $this->writeBuffer = new WriteBuffer(55);
    }

    /** @test */
    public function reportsEmptyWhenNoDataAdded()
    {
        $this->assertTrue($this->writeBuffer->isEmpty());
    }

    /** @test */
    public function reportsNotEmptyWhenDataWasAddedToBuffer()
    {
        $this->writeBuffer->append('Data that is short enough');

        $this->assertFalse($this->writeBuffer->isEmpty());
    }

    /** @test */
    public function prohibitsWritingDataOutsideOfBuffer()
    {
        $this->expectException(WriteLimitReachedException::class);

        $this->writeBuffer->append('Data that is long enough and cannot fit into the buffer!');
    }

    /** @test */
    public function allowsWritingExactlyBufferLimit()
    {
        $this->writeBuffer->append('Data that perfectly fits into buffer right to the byte!');

        $this->assertFalse($this->writeBuffer->isEmpty());
    }

    /** @test */
    public function prohibitsWritingToBufferIfLimitIsAlreadyReached()
    {
        $this->writeBuffer->append('String buffer 1');
        $this->writeBuffer->append('String buffer 2');
        $this->writeBuffer->append('String buffer 3');

        $this->expectException(WriteLimitReachedException::class);

        $this->writeBuffer->append('Cannot fit into remaining buffer');
    }

    /** @test */
    public function allowsWritingToBufferIfDataFitsDirectly()
    {
        $this->writeBuffer->append('String buffer 1');
        $this->writeBuffer->append('String buffer 2');
        $this->writeBuffer->append('String buffer 3');
        $this->writeBuffer->append('Data fits!');

        $this->assertFalse($this->writeBuffer->isEmpty());
    }

    /** @test */
    public function returnsEmptyStringWhenBufferIsEmpty()
    {
        $this->assertEquals('', $this->writeBuffer->currentValue());
    }

    /** @test */
    public function returnsAppendedBufferValue()
    {
        $this->writeBuffer->append('Appended value');

        $this->assertEquals('Appended value', $this->writeBuffer->currentValue());
    }

    /** @test */
    public function returnsEmptyStringAfterBufferHasBeenMoved()
    {
        $this->writeBuffer->append('8 bytes!');

        $this->writeBuffer->currentValue();
        $this->writeBuffer->handleWrittenBytes(8);

        $this->assertEquals('', $this->writeBuffer->currentValue());
    }

    /** @test */
    public function returnsTheSameStringWhenBytesWereNotReported()
    {
        $this->writeBuffer->append('Never reported!');
        $this->writeBuffer->currentValue();

        $this->assertEquals('Never reported!', $this->writeBuffer->currentValue());
    }

    /** @test */
    public function returnsLeftOverOfCurrentValueWhenBytesHasBeenWrittenPartially()
    {
        $this->writeBuffer->append('First part! Second Part!');
        $this->writeBuffer->currentValue();
        $this->writeBuffer->handleWrittenBytes(11);

        $this->assertEquals(' Second Part!', $this->writeBuffer->currentValue());
    }

    /** @test */
    public function appendsDataAfterBufferHasBeenReportedAsWritten()
    {
        $writtenData = [];
        $this->writeBuffer->append('Item that perfectly fits into buffer right to the byte1');
        $writtenData[] = $this->writeBuffer->currentValue();
        $this->writeBuffer->handleWrittenBytes(55);

        $this->writeBuffer->append('Item that perfectly fits into buffer right to the byte2');
        $writtenData[] = $this->writeBuffer->currentValue();
        $this->writeBuffer->handleWrittenBytes(55);

        $this->assertEquals(
            [
                'Item that perfectly fits into buffer right to the byte1',
                'Item that perfectly fits into buffer right to the byte2'
            ],
            $writtenData
        );
    }

    /** @test */
    public function returnsEmptyNonWrittenBufferWhenDataIsWritten()
    {
        $this->writeBuffer->append('Item that perfectly fits into buffer right to the byte1');
        $this->writeBuffer->currentValue();
        $this->writeBuffer->handleWrittenBytes(55);

        $this->assertEquals(
            [],
            $this->writeBuffer->flushNotWrittenItems()
        );
    }

    /** @test */
    public function returnsNotWrittenDataAsArray()
    {
        $this->writeBuffer->append('#1 Complete write');
        $this->writeBuffer->append('#2 Partial write');
        $this->writeBuffer->append('#3 Unwritten');

        $this->writeBuffer->currentValue();
        $this->writeBuffer->handleWrittenBytes(17);

        $this->writeBuffer->currentValue();
        $this->writeBuffer->handleWrittenBytes(3);

        $this->assertEquals(
            [
                '#2 Partial write',
                '#3 Unwritten'
            ],
            $this->writeBuffer->flushNotWrittenItems()
        );
    }

    /** @test */
    public function clearsNotWrittenItemsAfterFirstCall()
    {
        $this->writeBuffer->append('Item #1');
        $this->writeBuffer->append('Item #2');
        $this->writeBuffer->flushNotWrittenItems();


        $this->assertEquals([], $this->writeBuffer->flushNotWrittenItems());
    }
}
