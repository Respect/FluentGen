<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceFrom;
use Respect\Fluent\Attributes\AssuranceParameter;

#[Assurance(from: AssuranceFrom::Value, exact: true)]
final class IndexedValueHandler
{
    public function __construct(
        public readonly string $label,
        #[AssuranceParameter]
        public readonly mixed $value,
    ) {
    }
}
