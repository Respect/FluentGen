<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceFrom;
use Respect\Fluent\Attributes\AssuranceSubject;
use Respect\Fluent\Attributes\AssuranceSubjectMode;
use Respect\Fluent\Attributes\Composable;

#[Composable(self::class)]
#[Assurance(from: AssuranceFrom::Elements)]
#[AssuranceSubject(AssuranceSubjectMode::Elements)]
final class AllPrefixHandler
{
    public function __construct(
        public readonly object $validator,
    ) {
    }
}
