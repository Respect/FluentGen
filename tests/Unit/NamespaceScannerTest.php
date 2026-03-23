<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Respect\FluentGen\NamespaceScanner;
use Respect\FluentGen\Test\Fixtures\BarHandler;
use Respect\FluentGen\Test\Fixtures\FooHandler;
use Respect\FluentGen\Test\Fixtures\Handler;
use Respect\FluentGen\Test\Fixtures\NotHandler;
use Respect\FluentGen\Test\Fixtures\Unrelated;

#[CoversClass(NamespaceScanner::class)]
final class NamespaceScannerTest extends TestCase
{
    private const string FIXTURES_DIR = __DIR__ . '/../Fixtures';
    private const string FIXTURES_NS = 'Respect\\FluentGen\\Test\\Fixtures';

    #[Test]
    public function itShouldScanAllConcreteClasses(): void
    {
        $scanner = new NamespaceScanner();
        $result = $scanner->scan(self::FIXTURES_DIR, self::FIXTURES_NS);

        self::assertArrayHasKey('FooHandler', $result);
        self::assertArrayHasKey('BarHandler', $result);
        self::assertArrayHasKey('NotHandler', $result);
        self::assertArrayHasKey('Unrelated', $result);
    }

    #[Test]
    public function itShouldSkipAbstractClasses(): void
    {
        $scanner = new NamespaceScanner();
        $result = $scanner->scan(self::FIXTURES_DIR, self::FIXTURES_NS);

        self::assertArrayNotHasKey('AbstractHandler', $result);
    }

    #[Test]
    public function itShouldSkipInterfaces(): void
    {
        $scanner = new NamespaceScanner();
        $result = $scanner->scan(self::FIXTURES_DIR, self::FIXTURES_NS);

        self::assertArrayNotHasKey('Handler', $result);
    }

    #[Test]
    public function itShouldFilterByNodeType(): void
    {
        $scanner = new NamespaceScanner(nodeType: Handler::class);
        $result = $scanner->scan(self::FIXTURES_DIR, self::FIXTURES_NS);

        self::assertArrayHasKey('FooHandler', $result);
        self::assertArrayHasKey('BarHandler', $result);
        self::assertArrayHasKey('NotHandler', $result);
        self::assertArrayNotHasKey('Unrelated', $result);
    }

    #[Test]
    public function itShouldExcludeClassesByName(): void
    {
        $scanner = new NamespaceScanner(excludedClassNames: ['FooHandler', 'NotHandler']);
        $result = $scanner->scan(self::FIXTURES_DIR, self::FIXTURES_NS);

        self::assertArrayNotHasKey('FooHandler', $result);
        self::assertArrayNotHasKey('NotHandler', $result);
        self::assertArrayHasKey('BarHandler', $result);
        self::assertArrayHasKey('Unrelated', $result);
    }

    #[Test]
    public function itShouldReturnReflectionClassInstances(): void
    {
        $scanner = new NamespaceScanner();
        $result = $scanner->scan(self::FIXTURES_DIR, self::FIXTURES_NS);

        self::assertSame(FooHandler::class, $result['FooHandler']->getName());
        self::assertSame(BarHandler::class, $result['BarHandler']->getName());
    }

    #[Test]
    public function itShouldReturnResultsSortedByName(): void
    {
        $scanner = new NamespaceScanner();
        $result = $scanner->scan(self::FIXTURES_DIR, self::FIXTURES_NS);

        $keys = array_keys($result);
        $sorted = $keys;
        sort($sorted);

        self::assertSame($sorted, $keys);
    }

    #[Test]
    public function itShouldCombineNodeTypeAndExclusions(): void
    {
        $scanner = new NamespaceScanner(
            nodeType: Handler::class,
            excludedClassNames: ['BarHandler'],
        );
        $result = $scanner->scan(self::FIXTURES_DIR, self::FIXTURES_NS);

        self::assertArrayHasKey('FooHandler', $result);
        self::assertArrayHasKey('NotHandler', $result);
        self::assertArrayNotHasKey('BarHandler', $result);
        self::assertArrayNotHasKey('Unrelated', $result);
    }
}
