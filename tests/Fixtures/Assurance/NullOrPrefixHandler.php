<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceSubject;
use Respect\Fluent\Attributes\AssuranceSubjectMode;
use Respect\Fluent\Attributes\Composable;

#[Composable(self::class)]
#[Assurance(type: 'null', exact: true)]
#[AssuranceSubject(AssuranceSubjectMode::Wrap)]
final class NullOrPrefixHandler
{
    public function __construct(
        public readonly Validator $validator,
    ) {
    }
}
