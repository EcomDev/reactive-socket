<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * This exception is thrown when socket has reached limit of data to be written
 *
 * It can be a good indication that data must be queued till next writable event received from a stream
 */
class WriteLimitReachedException extends \RuntimeException
{

}
