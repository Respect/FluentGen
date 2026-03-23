<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures;

final class FooHandler implements Handler
{
    public function __construct(
        public readonly string $name,
        public readonly int $priority = 0,
    ) {
    }
}
