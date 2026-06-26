<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceFrom;

#[Assurance(from: AssuranceFrom::Member)]
final class MemberHandler
{
    public function __construct(
        public readonly mixed $haystack,
    ) {
    }
}
