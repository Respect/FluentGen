<!--
SPDX-License-Identifier: ISC
SPDX-FileCopyrightText: (c) Respect Project Contributors
SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
-->

# Respect\FluentGen

Generate PHP mixin interfaces from class namespaces, so IDEs can autocomplete
`__call`-based fluent builder chains.

When a builder resolves method calls dynamically, IDEs can't see the available
methods. FluentGen solves this by scanning your classes, reflecting their
constructors, and generating interface files that declare every method with
proper signatures and return types. Your builder class then references the
generated interface via a `@mixin` docblock, and autocompletion works.

FluentGen works with any class namespace that follows a naming convention. If
your classes use the `#[Composable]` attribute from
[Respect/Fluent](https://github.com/Respect/Fluent), FluentGen additionally
generates per-prefix composed interfaces, but that is not required.

## Installation

```bash
composer require --dev respect/fluentgen
```

Requires PHP 8.5+.

## What it generates

Given a namespace full of classes like `AreaFormatter`, `DateFormatter`,
`MaskFormatter`, FluentGen produces two interfaces:

- A **Builder** interface with static methods (`Builder::area()`,
  `Builder::date()`, etc.) for starting chains.
- A **Chain** interface with instance methods (`->area()`, `->date()`, etc.)
  for continuing them.

Each generated method mirrors the constructor signature of the underlying class.
If `MaskFormatter` has `__construct(string $range, string $replacement = '*')`,
the generated `mask()` method has the same parameters. Doc comments on the
constructor are carried over too.

## Setting it up

FluentGen is typically wired into a Symfony Console command that you run during
development. Here's how it looks like:

First, configure what to scan and where to write:

```php
$config = new Config(
    sourceDir: __DIR__ . '/src',
    sourceNamespace: 'App\\Formatters',
    outputDir: __DIR__ . '/src/Mixins',
    outputNamespace: 'App\\Formatters\\Mixins',
);
```

`sourceDir` and `sourceNamespace` tell the scanner where your classes live.
`outputDir` and `outputNamespace` control where the generated interfaces go.

Next, set up scanning. The `NamespaceScanner` reflects every concrete class in
the directory. You can filter by interface and exclude specific classes:

```php
$scanner = new NamespaceScanner(
    nodeType: Formatter::class,
    excludedClassNames: ['FormatterBuilder'],
);
```

Without filters, the scanner picks up every non-abstract class it finds. The
`nodeType` filter restricts to classes implementing a given interface. The
exclusion list removes specific classes by short name, useful for excluding the
builder class itself if it lives in the same namespace.

Then configure the generator. `MixinGenerator` needs to know what interfaces to
produce. Each `InterfaceConfig` describes one:

```php
$generator = new MixinGenerator(
    config: $config,
    scanner: $scanner,
    methodBuilder: new MethodBuilder(classSuffix: 'Formatter'),
    interfaces: [
        new InterfaceConfig(
            suffix: 'Builder',
            returnType: Chain::class,
            static: true,
        ),
        new InterfaceConfig(
            suffix: 'Chain',
            returnType: Chain::class,
            rootExtends: [Formatter::class],
        ),
    ],
);
```

The `suffix` determines the interface name. The `returnType` is what every
generated method returns, typically your Chain interface, enabling fluent
chaining. Set `static: true` for the builder entry point. Use `rootExtends`
when the chain interface should extend your domain interface.

The `MethodBuilder` handles how class names map to method names. The
`classSuffix` option strips a suffix before generating: `AreaFormatter` becomes
`area()`, `DateFormatter` becomes `date()`.

Finally, call `generate()` to get a filename-to-content map:

```php
$files = $generator->generate();

foreach ($files as $filename => $content) {
    file_put_contents($filename, $content);
}
```

Run this as part of your dev tooling: a console command, a Composer script, or
CI check that verifies generated files are up to date.

## Composition support (optional, requires Respect/Fluent)

Some libraries, like Respect/Validation, use prefix composition where
`notEmail()` creates `Not(Email())`. If your classes use the `#[Composable]`
attribute from Respect/Fluent, FluentGen handles this automatically.

Install the optional dependency:

```bash
composer require respect/fluent
```

The `MixinGenerator` discovers composable prefixes and generates per-prefix
interfaces. For example, a `Not` class with `#[Composable(self::class)]` produces a
`NotBuilder` interface containing `notEmail()`, `notString()`, etc., and a root
`Builder` interface that extends all prefix interfaces.

Composition constraints (`without`, `with`, `optIn` on the `Composable`
attribute) are respected during generation. Forbidden combinations are excluded
from the generated interfaces.

For the runtime prefix map, `PrefixConstantsGenerator` produces a constants
class with `COMPOSABLE`, `COMPOSABLE_WITH_ARGUMENT`, and `FORBIDDEN` arrays
that `ComposableMap` uses at resolve time.

## Customization

**MethodBuilder** controls how constructor parameters become method signatures.
Beyond `classSuffix`, it supports `excludedTypePrefixes` and
`excludedTypeNames` to skip parameters whose types come from external packages
you don't want in your public interface.

**FileRenderer** handles the final output, printing the generated namespace
via Nette PHP Generator and applying the `OutputFormatter`. The formatter
preserves existing SPDX license headers, converts tabs to spaces, normalizes
nullable syntax (`?Type` becomes `Type|null`), and collapses single-line doc
comments. Both are used with sensible defaults; you rarely need to customize
them.

**InterfaceConfig** has a few more options for the root interface:
`rootComment` adds a docblock (like `@mixin FormatterBuilder`), `rootUses`
adds use statements, and `rootExtends` makes the interface extend others.
