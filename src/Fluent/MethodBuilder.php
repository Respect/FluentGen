<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen\Fluent;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

use function count;
use function implode;
use function in_array;
use function is_object;
use function lcfirst;
use function preg_replace;
use function sort;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;
use function ucfirst;

final readonly class MethodBuilder
{
    /**
     * @param array<string> $excludedTypePrefixes
     * @param array<string> $excludedTypeNames
     */
    public function __construct(
        private string $classSuffix = '',
        private array $excludedTypePrefixes = [],
        private array $excludedTypeNames = [],
    ) {
    }

    public function classToPrefix(string $shortName): string
    {
        if ($this->classSuffix !== '' && str_ends_with($shortName, $this->classSuffix)) {
            $shortName = substr($shortName, 0, -strlen($this->classSuffix));
        }

        return lcfirst($shortName);
    }

    /** @param ReflectionClass<object> $nodeReflection */
    public function build(
        PhpNamespace $namespace,
        ReflectionClass $nodeReflection,
        string $returnType,
        string|null $prefix = null,
        bool $static = false,
        ReflectionParameter|null $prefixParameter = null,
    ): Method {
        $originalName = $nodeReflection->getShortName();
        if ($this->classSuffix !== '' && str_ends_with($originalName, $this->classSuffix)) {
            $originalName = substr($originalName, 0, -strlen($this->classSuffix));
        }

        $name = $prefix ? $prefix . ucfirst($originalName) : lcfirst($originalName);

        $method = new Method($name);
        $method->setPublic()->setReturnType($returnType);

        if ($static) {
            $method->setStatic();
        }

        if ($prefixParameter !== null) {
            $this->addPrefixParameter($method, $prefixParameter);
        }

        $constructor = $nodeReflection->getConstructor();
        if ($constructor === null) {
            return $method;
        }

        $comment = $constructor->getDocComment();
        if ($comment !== false) {
            $cleaned = preg_replace('@(/\*\* *| +\* +| +\*/)@', '', $comment);
            if ($cleaned !== null) {
                $method->addComment($cleaned);
            }
        }

        foreach ($constructor->getParameters() as $reflectionParameter) {
            $this->addParameter($method, $reflectionParameter, $namespace);
        }

        return $method;
    }

    /** @return array<string> */
    private function extractTypeNames(ReflectionType|null $type): array
    {
        $types = [];

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $subType) {
                if (!$subType instanceof ReflectionNamedType) {
                    continue;
                }

                $types[] = $subType->getName();
            }

            sort($types);
        } elseif ($type instanceof ReflectionNamedType) {
            $types[] = $type->getName();
        }

        return $types;
    }

    private function addPrefixParameter(Method $method, ReflectionParameter $reflectionParameter): void
    {
        $types = $this->extractTypeNames($reflectionParameter->getType());

        $method->addParameter($reflectionParameter->getName())->setType(implode('|', $types));
    }

    private function addParameter(
        Method $method,
        ReflectionParameter $reflectionParameter,
        PhpNamespace $namespace,
    ): void {
        if ($reflectionParameter->isVariadic()) {
            $method->setVariadic();
        }

        $type = $reflectionParameter->getType();
        $types = $this->extractTypeNames($type);

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $subType) {
                if (!$subType instanceof ReflectionNamedType || $subType->isBuiltin()) {
                    continue;
                }

                $namespace->addUse($subType->getName());
            }
        } elseif ($type instanceof ReflectionNamedType) {
            if ($this->isExcludedType($type->getName())) {
                return;
            }

            if (!$type->isBuiltin()) {
                $namespace->addUse($type->getName());
            }
        }

        $parameter = $method->addParameter($reflectionParameter->getName());
        $parameter->setType(implode('|', $types));

        if (!$reflectionParameter->isDefaultValueAvailable()) {
            $parameter->setNullable($reflectionParameter->isOptional());
        }

        if (count($types) > 1 || $reflectionParameter->isVariadic()) {
            $parameter->setNullable(false);
        }

        if (!$reflectionParameter->isDefaultValueAvailable()) {
            return;
        }

        $defaultValue = $reflectionParameter->getDefaultValue();
        if (is_object($defaultValue)) {
            $parameter->setDefaultValue(null);
            $parameter->setNullable(true);

            return;
        }

        $parameter->setDefaultValue($defaultValue);
        $parameter->setNullable(false);
    }

    private function isExcludedType(string $typeName): bool
    {
        foreach ($this->excludedTypePrefixes as $excludedPrefix) {
            if (str_starts_with($typeName, $excludedPrefix)) {
                return true;
            }
        }

        return in_array($typeName, $this->excludedTypeNames, true);
    }
}
