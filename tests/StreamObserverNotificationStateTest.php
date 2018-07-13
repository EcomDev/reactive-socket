<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class StreamObserverNotificationStateTest extends TestCase
{
    /** @test */
    public function emptyStatesAreEqual()
    {
        $this->assertEquals(
            StreamObserverNotificationState::createEmpty(),
            StreamObserverNotificationState::createEmpty()
        );
    }

    /** @test */
    public function connectedNotificationDiffersFromEmptyState()
    {
        $this->assertNotEquals(
            StreamObserverNotificationState::createEmpty(),
            StreamObserverNotificationState::createEmpty()
                ->withConnectedNotification(new NullStreamStub())
        );
    }

    /** @test */
    public function connectedNotificationForTheSameStreamEqual()
    {
        $stream = new NullStreamStub();

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withConnectedNotification($stream),
            StreamObserverNotificationState::createEmpty()
                ->withConnectedNotification($stream)
        );
    }

    /** @test */
    public function connectedNotificationForDifferentStreamsDoNotEqual()
    {
        $this->assertNotEquals(
            StreamObserverNotificationState::createEmpty()
                ->withConnectedNotification(new NullStreamStub()),
            StreamObserverNotificationState::createEmpty()
                ->withConnectedNotification(new NullStreamStub())
        );
    }

    /** @test */
    public function disconnectedNotificationForTheSameStreamAndDataEqual()
    {
        $stream = new NullStreamStub();

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withDisconnectedNotification($stream, []),
            StreamObserverNotificationState::createEmpty()
                ->withDisconnectedNotification($stream, [])
        );
    }

    /** @test */
    public function disconnectedNotificationsForSameStreamButDifferentDataAreNotEqual()
    {
        $stream = new NullStreamStub();

        $this->assertNotEquals(
            StreamObserverNotificationState::createEmpty()
                ->withDisconnectedNotification($stream, [123]),
            StreamObserverNotificationState::createEmpty()
                ->withDisconnectedNotification($stream, [])
        );
    }

    /** @test */
    public function writableNotificationsDoesNotEqualEmptyState()
    {
        $this->assertNotEquals(
            StreamObserverNotificationState::createEmpty(),
            StreamObserverNotificationState::createEmpty()
                ->withWritableNotification(new NullStreamStub())
        );
    }

    /** @test */
    public function writableNotificationsForSameStreamEqual()
    {
        $stream = new NullStreamStub();

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withWritableNotification($stream),
            StreamObserverNotificationState::createEmpty()
                ->withWritableNotification($stream)
        );
    }



    /** @test */
    public function multipleWritableNotificationsReportedAsDifferentStateFromSingle()
    {
        $stream = new NullStreamStub();

        $this->assertNotEquals(
            StreamObserverNotificationState::createEmpty()
                ->withWritableNotification($stream),
            StreamObserverNotificationState::createEmpty()
                ->withWritableNotification($stream)
                ->withWritableNotification($stream)
        );
    }


    /** @test */
    public function readableNotificationsDoesNotEqualEmptyState()
    {
        $this->assertNotEquals(
            StreamObserverNotificationState::createEmpty(),
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification(new NullStreamStub(), '')
        );
    }

    /** @test */
    public function readableNotificationsForSameDataAndStreamEqual()
    {
        $stream = new NullStreamStub();

        $this->assertEquals(
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($stream, 'Data#1'),
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($stream, 'Data#1')
        );
    }

    /** @test */
    public function readableNotificationsForSameStreamButDifferentDataAreNotEqual()
    {
        $stream = new NullStreamStub();

        $this->assertNotEquals(
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($stream, 'Data#1'),
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($stream, 'Data#2')
        );
    }

    /** @test */
    public function multipleReadableNotificationsReportedAsDifferentStateFromSingle()
    {
        $stream = new NullStreamStub();

        $this->assertNotEquals(
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($stream, ''),
            StreamObserverNotificationState::createEmpty()
                ->withReadableNotification($stream, '')
                ->withReadableNotification($stream, '')
        );
    }

    /** @test */
    public function allowsToOutputItselfAsString()
    {
        $this->assertStringMatchesFormat(
            json_encode(
                [
                    ['connected', '%x'],
                    ['writable', '%x'],
                    ['writable', '%x'],
                    ['readable', '%x', '1'],
                    ['readable', '%x', '2'],
                    ['readable', '%x', '3'],
                    ['disconnected', '%x', ['unsent', 'data']]
                ],
                JSON_PRETTY_PRINT
            ),
            (string)StreamObserverNotificationState::createEmpty()
                ->withConnectedNotification(new NullStreamStub())
                ->withWritableNotification(new NullStreamStub())
                ->withWritableNotification(new NullStreamStub())
                ->withReadableNotification(new NullStreamStub(), '1')
                ->withReadableNotification(new NullStreamStub(), '2')
                ->withReadableNotification(new NullStreamStub(), '3')
                ->withDisconnectedNotification(new NullStreamStub(), ['unsent', 'data'])
        );
    }
}
