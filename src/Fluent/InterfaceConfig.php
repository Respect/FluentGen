<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen\Fluent;

final readonly class InterfaceConfig
{
    /**
     * @param array<string> $rootExtends
     * @param array<string> $rootUses
     */
    public function __construct(
        public string $suffix,
        public string $returnType,
        public bool $static = false,
        public array $rootExtends = [],
        public string|null $rootComment = null,
        public array $rootUses = [],
    ) {
    }
}
