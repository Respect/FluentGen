<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen;

final readonly class Config
{
    public function __construct(
        public string $sourceDir,
        public string $sourceNamespace,
        public string $outputDir,
        public string $outputNamespace,
    ) {
    }
}
