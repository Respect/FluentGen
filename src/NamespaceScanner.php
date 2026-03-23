<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen;

use DirectoryIterator;
use ReflectionClass;

use function in_array;
use function is_a;
use function ksort;

final readonly class NamespaceScanner
{
    /** @param array<string> $excludedClassNames */
    public function __construct(
        private string|null $nodeType = null,
        private array $excludedClassNames = [],
    ) {
    }

    /** @return array<string, ReflectionClass<object>> */
    public function scan(
        string $directory,
        string $namespace,
    ): array {
        $nodes = [];

        foreach (new DirectoryIterator($directory) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            /** @var class-string $className */
            $className = $namespace . '\\' . $file->getBasename('.php');
            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            if ($this->nodeType !== null && !is_a($className, $this->nodeType, true)) {
                continue;
            }

            if (in_array($reflection->getShortName(), $this->excludedClassNames, true)) {
                continue;
            }

            $nodes[$reflection->getShortName()] = $reflection;
        }

        ksort($nodes);

        return $nodes;
    }
}
