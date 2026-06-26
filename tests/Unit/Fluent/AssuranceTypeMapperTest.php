<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Unit\Fluent;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Respect\FluentGen\Fluent\AssuranceTypeMapper;
use Respect\FluentGen\Fluent\NarrowingDoc;
use Respect\FluentGen\Test\Fixtures\Assurance\AllOfHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\AllPrefixHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\AnyOfHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\ComparisonHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\EachHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\ExcludeHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\FileTypeHandler;
use Respect\FluentGen\Test\Fixtures\FooHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\IndexedValueHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\InstanceHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\IntTypeHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\KeyHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\MemberHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\NamedHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\NullOrHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\NullOrPrefixHandler;
use Respect\FluentGen\Test\Fixtures\Assurance\NullTypeHandler;

#[CoversClass(AssuranceTypeMapper::class)]
#[CoversClass(NarrowingDoc::class)]
final class AssuranceTypeMapperTest extends TestCase
{
    /**
     * The static entry method, where #[Assurance] is compiled into the narrowed type.
     *
     * @param class-string $rule
     */
    private function base(string $rule): NarrowingDoc
    {
        return (new AssuranceTypeMapper())->for(new ReflectionClass($rule), true);
    }

    /** @param class-string $rule */
    private function instance(string $rule): NarrowingDoc
    {
        return (new AssuranceTypeMapper())->for(new ReflectionClass($rule), false);
    }

    #[Test]
    public function itShouldEmitConcreteTypeForPlainTypeRule(): void
    {
        $doc = $this->base(IntTypeHandler::class);

        self::assertSame(['@return Chain<int>'], $doc->comments);
        self::assertFalse($doc->suppressConstructorDoc);
    }

    #[Test]
    public function itShouldFullyQualifyClassNamesInUnionTypes(): void
    {
        self::assertSame(
            ['@return Chain<string|\SplFileInfo>'],
            $this->base(FileTypeHandler::class)->comments,
        );
    }

    #[Test]
    public function itShouldEmitArgumentDerivedTemplateForFromValue(): void
    {
        $doc = $this->base(ComparisonHandler::class);

        self::assertSame([
            '@template T',
            '@param T $compareTo',
            '@return Chain<T>',
        ], $doc->comments);
        self::assertTrue($doc->suppressConstructorDoc);
    }

    #[Test]
    public function itShouldEmitClassStringTemplateForTypeStringFrom(): void
    {
        // from: TypeString -> the class-string argument narrows to an instance of it.
        $doc = $this->base(InstanceHandler::class);

        self::assertSame([
            '@template T of object',
            '@param class-string<T> $class',
            '@return Chain<T>',
        ], $doc->comments);
        self::assertTrue($doc->suppressConstructorDoc);
    }

    #[Test]
    public function itShouldIndexTheValueParameterByAssuranceParameter(): void
    {
        // #[AssuranceParameter] selects the argument (here the second, $value), not the first.
        self::assertSame([
            '@template T',
            '@param T $value',
            '@return Chain<T>',
        ], $this->base(IndexedValueHandler::class)->comments);
    }

    #[Test]
    public function itShouldEmitIterableTemplateForElementsArgumentForm(): void
    {
        $doc = $this->base(EachHandler::class);

        self::assertSame([
            '@template T',
            '@param Chain<T> $validator',
            '@return Chain<iterable<T>>',
        ], $doc->comments);
        self::assertTrue($doc->suppressConstructorDoc);
    }

    #[Test]
    public function itShouldResetForMemberDerivedRule(): void
    {
        self::assertSame(['@return Chain<mixed>'], $this->base(MemberHandler::class)->comments);
    }

    #[Test]
    public function itShouldResetForExcludeModifierRule(): void
    {
        self::assertSame(['@return Chain<mixed>'], $this->base(ExcludeHandler::class)->comments);
    }

    #[Test]
    public function itShouldResetUnannotatedStaticEntryMethod(): void
    {
        self::assertSame(['@return Chain<mixed>'], $this->base(FooHandler::class)->comments);
    }

    #[Test]
    public function itShouldPreserveReceiverTypeOnInstanceMethods(): void
    {
        // Instance (chain) methods preserve the first rule's type regardless of their own
        // assurance: the static entry sets the type, later calls only constrain it.
        self::assertSame(['@return Chain<TSure>'], $this->instance(IntTypeHandler::class)->comments);
        self::assertSame(['@return Chain<TSure>'], $this->instance(FooHandler::class)->comments);
    }

    #[Test]
    public function itShouldRefineElementTypeEvenOnInstanceMethods(): void
    {
        // each()/all() add expressible element-type info, so they refine mid-chain too.
        self::assertSame([
            '@template T',
            '@param Chain<T> $validator',
            '@return Chain<iterable<T>>',
        ], $this->instance(EachHandler::class)->comments);
    }

    #[Test]
    public function itShouldComposeIterableForElementsPrefix(): void
    {
        $mapper = new AssuranceTypeMapper();

        $doc = $mapper->for(
            new ReflectionClass(IntTypeHandler::class),
            true,
            new ReflectionClass(AllPrefixHandler::class),
        );

        self::assertSame(['@return Chain<iterable<int>>'], $doc->comments);
    }

    #[Test]
    public function itShouldResetForNonElementsPrefix(): void
    {
        $mapper = new AssuranceTypeMapper();

        $doc = $mapper->for(
            new ReflectionClass(IntTypeHandler::class),
            true,
            new ReflectionClass(ExcludeHandler::class),
        );

        self::assertSame(['@return Chain<mixed>'], $doc->comments);
    }

    #[Test]
    public function itShouldEmitContainerTypeForContainerSubject(): void
    {
        // key/property/length/max/min: the container type is a sound narrowing of the input.
        self::assertSame(
            ['@return Chain<array|\ArrayAccess>'],
            $this->base(KeyHandler::class)->comments,
        );
    }

    #[Test]
    public function itShouldNotNarrowArgumentWrappingForms(): void
    {
        // Wrap arg-form (nullOr) and compose forms (allOf/named/anyOf) would have to retype
        // their Validator parameter to Chain<T>, which breaks raw Validator arguments, so the
        // static entry stays mixed. (Their PREFIX forms narrow safely; see below.)
        self::assertSame(['@return Chain<mixed>'], $this->base(NullOrHandler::class)->comments);
        self::assertSame(['@return Chain<mixed>'], $this->base(AllOfHandler::class)->comments);
        self::assertSame(['@return Chain<mixed>'], $this->base(NamedHandler::class)->comments);
        self::assertSame(['@return Chain<mixed>'], $this->base(AnyOfHandler::class)->comments);
    }

    #[Test]
    public function itShouldComposeBypassUnionForWrapPrefix(): void
    {
        $mapper = new AssuranceTypeMapper();

        $doc = $mapper->for(
            new ReflectionClass(IntTypeHandler::class),
            true,
            new ReflectionClass(NullOrPrefixHandler::class),
        );

        self::assertSame(['@return Chain<int|null>'], $doc->comments);
    }

    #[Test]
    public function itShouldDedupeBypassWhenInnerAlreadyAdmitsIt(): void
    {
        // nullOrNullType(): inner 'null' unioned with the 'null' bypass must collapse to
        // Chain<null>, not Chain<null|null>.
        $mapper = new AssuranceTypeMapper();

        $doc = $mapper->for(
            new ReflectionClass(NullTypeHandler::class),
            true,
            new ReflectionClass(NullOrPrefixHandler::class),
        );

        self::assertSame(['@return Chain<null>'], $doc->comments);
    }

    #[Test]
    public function itShouldComposeContainerTypeForContainerPrefix(): void
    {
        // keyIntType() narrows the INPUT to the container type, like base key() does.
        $mapper = new AssuranceTypeMapper();

        $doc = $mapper->for(
            new ReflectionClass(IntTypeHandler::class),
            true,
            new ReflectionClass(KeyHandler::class),
        );

        self::assertSame(['@return Chain<array|\ArrayAccess>'], $doc->comments);
    }

    #[Test]
    public function itShouldPreserveReceiverTypeForNewBucketsOnInstanceMethods(): void
    {
        // First-rule-wins: container/wrap/compose rules narrow only on the static entry;
        // mid-chain they preserve the accumulated TSure (only each()/all() refine).
        self::assertSame(['@return Chain<TSure>'], $this->instance(KeyHandler::class)->comments);
        self::assertSame(['@return Chain<TSure>'], $this->instance(NullOrHandler::class)->comments);
        self::assertSame(['@return Chain<TSure>'], $this->instance(AllOfHandler::class)->comments);
        self::assertSame(['@return Chain<TSure>'], $this->instance(AnyOfHandler::class)->comments);
    }

    #[Test]
    public function itShouldHonourCustomChainAndTemplateNames(): void
    {
        $mapper = new AssuranceTypeMapper('Validator', 'TValue');

        self::assertSame(
            ['@return Validator<int>'],
            $mapper->for(new ReflectionClass(IntTypeHandler::class), true)->comments,
        );
        self::assertSame(
            ['@return Validator<TValue>'],
            $mapper->for(new ReflectionClass(FooHandler::class), false)->comments,
        );
    }
}
