<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceModifier;
use Respect\Fluent\Attributes\AssuranceSubject;
use Respect\Fluent\Attributes\AssuranceSubjectMode;

#[Assurance(modifier: AssuranceModifier::Exclude)]
#[AssuranceSubject(AssuranceSubjectMode::Wrap)]
final class ExcludeHandler
{
    public function __construct(
        public readonly object $validator,
    ) {
    }
}
