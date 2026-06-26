<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen\Fluent;

use ReflectionClass;
use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceFrom;
use Respect\Fluent\Attributes\AssuranceModifier;
use Respect\Fluent\Attributes\AssuranceParameter;
use Respect\Fluent\Attributes\AssuranceSubject;
use Respect\Fluent\Attributes\AssuranceSubjectMode;

use function array_map;
use function ctype_digit;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function ltrim;
use function str_contains;
use function trim;

/**
 * Derives the IDE-readable narrowing PHPDoc for a generated method from a node's
 * #[Assurance] / #[AssuranceSubject] / #[AssuranceParameter] attributes.
 */
final readonly class AssuranceTypeMapper
{
    private const array BUILTINS = [
        'int',
        'float',
        'string',
        'bool',
        'array',
        'object',
        'callable',
        'iterable',
        'mixed',
        'null',
        'void',
        'false',
        'true',
        'resource',
        'scalar',
        'numeric-string',
        'non-empty-string',
        'class-string',
        'positive-int',
        'negative-int',
    ];

    public function __construct(
        private string $chainType = 'Chain',
        private string $templateParam = 'TSure',
    ) {
    }

    /**
     * @param ReflectionClass<object> $rule
     * @param ReflectionClass<object>|null $prefix the composing prefix when building a prefixed method
     */
    public function for(ReflectionClass $rule, bool $static, ReflectionClass|null $prefix = null): NarrowingDoc
    {
        if (!$static) {
            $element = $prefix === null ? $this->elementDoc($rule) : null;
            if ($element !== null) {
                return $element;
            }

            return $this->ret($prefix !== null ? 'mixed' : $this->templateParam);
        }

        if ($prefix !== null) {
            return $this->forPrefixed($rule, $prefix);
        }

        return $this->forBase($rule);
    }

    /**
     * The element-extraction form for an each()/all()-style rule (from: Elements), or null.
     *
     * @param ReflectionClass<object> $rule
     */
    private function elementDoc(ReflectionClass $rule): NarrowingDoc|null
    {
        if ($this->assuranceOf($rule)?->from !== AssuranceFrom::Elements) {
            return null;
        }

        return new NarrowingDoc([
            '@template T',
            '@param ' . $this->chainType . '<T> $' . $this->sourceParameterName($rule),
            '@return ' . $this->chainType . '<iterable<T>>',
        ], suppressConstructorDoc: true);
    }

    /** @param ReflectionClass<object> $rule */
    private function forBase(ReflectionClass $rule): NarrowingDoc
    {
        $assurance = $this->assuranceOf($rule);
        if ($assurance === null) {
            return $this->ret('mixed');
        }

        $subject = $this->subjectOf($rule);
        if ($subject?->mode === AssuranceSubjectMode::Wrap) {
            return $this->ret('mixed');
        }

        // The argument carrying the type info: #[AssuranceParameter] selects it (any
        // position), defaulting to the first. `from` decides how it maps to the type.
        $sourceParam = $this->sourceParameterName($rule);

        if ($assurance->from === AssuranceFrom::TypeString) {
            // The argument is a class-string; the input narrows to an instance of it.
            return new NarrowingDoc([
                '@template T of object',
                '@param class-string<T> $' . $sourceParam,
                '@return ' . $this->chainType . '<T>',
            ], suppressConstructorDoc: true);
        }

        if ($assurance->from === AssuranceFrom::Value) {
            // The argument's own type is the narrowed type.
            return new NarrowingDoc([
                '@template T',
                '@param T $' . $sourceParam,
                '@return ' . $this->chainType . '<T>',
            ], suppressConstructorDoc: true);
        }

        $element = $this->elementDoc($rule);
        if ($element !== null) {
            return $element;
        }

        if (
            $assurance->from === AssuranceFrom::Member
            || $assurance->compose !== null
            || $assurance->modifier !== null
        ) {
            return $this->ret('mixed');
        }

        if ($assurance->type !== null) {
            return $this->ret($this->typeString($assurance->type));
        }

        return $this->ret('mixed');
    }

    /**
     * @param ReflectionClass<object> $rule
     * @param ReflectionClass<object> $prefix
     */
    private function forPrefixed(ReflectionClass $rule, ReflectionClass $prefix): NarrowingDoc
    {
        $subject = $this->subjectOf($prefix);
        if ($subject?->mode === AssuranceSubjectMode::Elements) {
            $inner = $this->concreteTypeOf($rule);

            return $this->ret($inner !== null ? 'iterable<' . $inner . '>' : 'iterable');
        }

        if ($subject?->mode === AssuranceSubjectMode::Wrap) {
            $prefixAssurance = $this->assuranceOf($prefix);
            if ($prefixAssurance?->modifier === AssuranceModifier::Exclude) {
                return $this->ret('mixed');
            }

            $bypass = $prefixAssurance?->type;
            $inner = $this->concreteTypeOf($rule);
            if ($inner !== null && $bypass !== null) {
                return $this->ret($this->union($inner, $this->typeString($bypass)));
            }

            return $this->ret('mixed');
        }

        if ($subject?->mode === AssuranceSubjectMode::Container) {
            $type = $this->assuranceOf($prefix)?->type;

            return $type !== null ? $this->ret($this->typeString($type)) : $this->ret('mixed');
        }

        return $this->ret('mixed');
    }

    /**
     * The plain concrete type of a rule, or null when it is not a pure type rule.
     *
     * @param ReflectionClass<object> $rule
     */
    private function concreteTypeOf(ReflectionClass $rule): string|null
    {
        $assurance = $this->assuranceOf($rule);
        if ($assurance?->type === null) {
            return null;
        }

        if (
            $assurance->from !== null
            || $assurance->compose !== null
            || $assurance->modifier !== null
            || $this->subjectOf($rule) !== null
            || $this->assuranceParameterName($rule) !== null
        ) {
            return null;
        }

        return $this->typeString($assurance->type);
    }

    private function ret(string $inner): NarrowingDoc
    {
        return new NarrowingDoc(['@return ' . $this->chainType . '<' . $inner . '>']);
    }

    /**
     * Join one or more pipe-separated type strings into a single union, preserving order
     * and dropping duplicate members.
     */
    private function union(string ...$types): string
    {
        $parts = [];
        foreach ($types as $type) {
            foreach (explode('|', $type) as $part) {
                $part = trim($part);
                if ($part === '' || in_array($part, $parts, true)) {
                    continue;
                }

                $parts[] = $part;
            }
        }

        return implode('|', $parts);
    }

    /** @param ReflectionClass<object> $rule */
    private function assuranceOf(ReflectionClass $rule): Assurance|null
    {
        $attributes = $rule->getAttributes(Assurance::class);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    /** @param ReflectionClass<object> $rule */
    private function subjectOf(ReflectionClass $rule): AssuranceSubject|null
    {
        $attributes = $rule->getAttributes(AssuranceSubject::class);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    /** @param ReflectionClass<object> $rule */
    private function assuranceParameterName(ReflectionClass $rule): string|null
    {
        foreach ($rule->getConstructor()?->getParameters() ?? [] as $param) {
            if ($param->getAttributes(AssuranceParameter::class) !== []) {
                return $param->getName();
            }
        }

        return null;
    }

    /** @param ReflectionClass<object> $rule */
    private function firstParameterName(ReflectionClass $rule): string
    {
        $parameters = $rule->getConstructor()?->getParameters() ?? [];

        return $parameters === [] ? 'input' : $parameters[0]->getName();
    }

    /**
     * The argument that carries the assurance type info: the #[AssuranceParameter]-marked
     * one, or the first parameter when none is marked.
     *
     * @param ReflectionClass<object> $rule
     */
    private function sourceParameterName(ReflectionClass $rule): string
    {
        return $this->assuranceParameterName($rule) ?? $this->firstParameterName($rule);
    }

    /** @param string|list<string> $type */
    private function typeString(string|array $type): string
    {
        $parts = is_array($type) ? $type : explode('|', $type);

        return implode('|', array_map($this->qualify(...), $parts));
    }

    private function qualify(string $segment): string
    {
        $segment = trim($segment);

        if ($segment === '' || $segment[0] === "'" || $segment[0] === '-' || ctype_digit($segment[0])) {
            return $segment;
        }

        if (str_contains($segment, '\\')) {
            return '\\' . ltrim($segment, '\\');
        }

        if (in_array($segment, self::BUILTINS, true)) {
            return $segment;
        }

        return '\\' . $segment;
    }
}
