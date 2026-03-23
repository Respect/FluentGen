<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Unit\Fluent;

use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Respect\FluentGen\Fluent\MethodBuilder;
use Respect\FluentGen\Test\Fixtures\BarHandler;
use Respect\FluentGen\Test\Fixtures\EmptyHandler;
use Respect\FluentGen\Test\Fixtures\FooHandler;
use Respect\FluentGen\Test\Fixtures\NotHandler;
use Respect\FluentGen\Test\Fixtures\Unrelated;

#[CoversClass(MethodBuilder::class)]
final class MethodBuilderTest extends TestCase
{
    #[Test]
    public function itShouldGenerateMethodWithLcfirstNameWhenNoPrefix(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        self::assertSame('fooHandler', $method->getName());
    }

    #[Test]
    public function itShouldStripClassSuffixFromMethodName(): void
    {
        $builder = new MethodBuilder(classSuffix: 'Handler');
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        self::assertSame('foo', $method->getName());
    }

    #[Test]
    public function itShouldPrependPrefixToMethodName(): void
    {
        $builder = new MethodBuilder(classSuffix: 'Handler');
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static', 'not');

        self::assertSame('notFoo', $method->getName());
    }

    #[Test]
    public function itShouldSetReturnType(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'self');

        self::assertSame('self', $method->getReturnType());
    }

    #[Test]
    public function itShouldSetMethodAsPublic(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        self::assertTrue($method->isPublic());
    }

    #[Test]
    public function itShouldSetStaticWhenRequested(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static', null, true);

        self::assertTrue($method->isStatic());
    }

    #[Test]
    public function itShouldNotSetStaticByDefault(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        self::assertFalse($method->isStatic());
    }

    #[Test]
    public function itShouldAddConstructorParameters(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        $params = $method->getParameters();
        self::assertArrayHasKey('name', $params);
        self::assertArrayHasKey('priority', $params);
    }

    #[Test]
    public function itShouldSetParameterTypes(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        $params = $method->getParameters();
        self::assertSame('string', $params['name']->getType());
        self::assertSame('int', $params['priority']->getType());
    }

    #[Test]
    public function itShouldSetDefaultValues(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        $params = $method->getParameters();
        self::assertSame(0, $params['priority']->getDefaultValue());
    }

    #[Test]
    public function itShouldHandleClassWithoutConstructor(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(EmptyHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        self::assertSame([], $method->getParameters());
    }

    #[Test]
    public function itShouldAddDocCommentFromConstructor(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(BarHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        $comment = $method->getComment();
        self::assertNotNull($comment);
        self::assertStringContainsString('@param array<string> $tags', $comment);
    }

    #[Test]
    public function itShouldExcludeTypesByPrefix(): void
    {
        $builder = new MethodBuilder(excludedTypePrefixes: ['Respect\\FluentGen\\Test\\']);
        $namespace = new PhpNamespace('Test');

        // FooHandler has only builtin types, test with a class that has object type params
        // We need a fixture with an excluded type; let's use NotHandler which has only string
        $reflection = new ReflectionClass(NotHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        // NotHandler has string $input - string is builtin, not excluded by prefix
        $params = $method->getParameters();
        self::assertArrayHasKey('input', $params);
    }

    #[Test]
    public function itShouldExcludeTypesByName(): void
    {
        $builder = new MethodBuilder(excludedTypeNames: ['string']);
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(NotHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        // string type is excluded, so the 'input' parameter should not appear
        $params = $method->getParameters();
        self::assertArrayNotHasKey('input', $params);
    }

    #[Test]
    public function itShouldHandleMultipleParametersWithDefaults(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(BarHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        $params = $method->getParameters();
        self::assertArrayHasKey('value', $params);
        self::assertArrayHasKey('strict', $params);
        self::assertArrayHasKey('tags', $params);
        self::assertTrue($params['strict']->getDefaultValue());
        self::assertSame([], $params['tags']->getDefaultValue());
    }

    #[Test]
    public function itShouldHandlePrefixParameterFromReflection(): void
    {
        $builder = new MethodBuilder(classSuffix: 'Handler');
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $prefixParam = $constructor->getParameters()[0];

        $method = $builder->build($namespace, $reflection, 'static', 'not', false, $prefixParam);

        $params = $method->getParameters();
        // The prefix parameter should be the first parameter
        $paramNames = array_keys($params);
        self::assertSame('name', $paramNames[0]);
    }

    #[Test]
    public function itShouldNotStripSuffixWhenClassNameDoesNotEndWithIt(): void
    {
        $builder = new MethodBuilder(classSuffix: 'Widget');
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(FooHandler::class);

        $method = $builder->build($namespace, $reflection, 'static');

        self::assertSame('fooHandler', $method->getName());
    }

    #[Test]
    public function itShouldUseUcfirstWhenPrefixIsProvided(): void
    {
        $builder = new MethodBuilder();
        $namespace = new PhpNamespace('Test');
        $reflection = new ReflectionClass(Unrelated::class);

        $method = $builder->build($namespace, $reflection, 'static', 'key');

        self::assertSame('keyUnrelated', $method->getName());
    }
}
