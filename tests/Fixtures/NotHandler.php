<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures;

use Respect\Fluent\Attributes\Composable;

#[Composable('not')]
final class NotHandler implements Handler
{
    public function __construct(
        public readonly string $input,
    ) {
    }
}
