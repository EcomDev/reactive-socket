<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class SimpleLoopRunnerTest extends TestCase
{
    private $calls = [];

    /** @test */
    public function executesAllTickCallbacksOnRunOnce()
    {
        $loop = new SimpleLoopRunner();
        $loop->attachTickHandler($this->recordCall('Tick #1'));
        $loop->attachTickHandler($this->recordCall('Tick #2'));
        $loop->attachTickHandler($this->recordCall('Tick #3'));

        $loop->runOnce();

        $this->assertEquals(
            ['Tick #1', 'Tick #2', 'Tick #3'],
            $this->calls
        );
    }

    /** @test */
    public function runsLoopUntilStopped()
    {
        $loop = new SimpleLoopRunner();
        $loop->attachTickHandler($this->recordCall('Tick #1'));
        $loop->attachTickHandler($this->recordCall('Tick #2'));
        $loop->attachTickHandler($this->stopAfter(4, $loop));

        $loop->run(0);

        $this->assertEquals(
            [
                'Tick #1',
                'Tick #2',
                'Tick #1',
                'Tick #2',
                'Tick #1',
                'Tick #2',
                'Tick #1',
                'Tick #2'
            ],
            $this->calls
        );
    }

    /** @test */
    public function eachRunResumesStoppedLoop()
    {
        $loop = new SimpleLoopRunner();
        $loop->attachTickHandler($this->recordCall('Tick #1'));
        $loop->attachTickHandler(function () use ($loop) {
            $loop->stop();
        });

        $loop->run(0);
        $loop->run(0);
        $loop->run(0);

        $this->assertEquals(
            [
                'Tick #1',
                'Tick #1',
                'Tick #1',
            ],
            $this->calls
        );
    }

    /** @test */
    public function addsPauseInMicrosecondsBetweenRuns()
    {
        $loop = new SimpleLoopRunner();
        $loop->attachTickHandler($this->stopAfter(3, $loop));

        $start = microtime(true);
        $loop->run(1000);
        $timeRun = microtime(true) - $start;

        $this->assertThat(
            $timeRun,
            $this->logicalAnd(
                $this->greaterThan(0.003),
                $this->lessThan(0.005)
            )
        );
    }

    private function stopAfter(int $times, SimpleLoopRunner $loop): callable
    {
        $invoked = 0;
        return function () use (&$invoked, $times, $loop) {
            ++$invoked;
            if ($invoked >= $times) {
                $loop->stop();
            }
        };
    }

    private function recordCall(string $text): callable
    {
        return function () use ($text) {
            $this->calls[] = $text;
        };
    }
}
