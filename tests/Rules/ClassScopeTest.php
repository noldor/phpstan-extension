<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use Traversable;
use ArrayIterator;
use PHPStan\Testing\PHPStanTestCase;
use Noldors\PHPStan\Configuration\ClassScope;

final class ClassScopeTest extends PHPStanTestCase
{
    public function testAnEmptyScopeSelectsNoClasses(): void
    {
        $this->assertFalse(new ClassScope()->contains(self::createReflectionProvider()->getClass(self::class)));
        $this->assertFalse(new ClassScope()->contains(null));
    }

    public function testConfiguredClassIncludesItsDescendants(): void
    {
        $class = self::createReflectionProvider()->getClass(self::class);

        $this->assertTrue(new ClassScope([self::class])->contains($class));
        $this->assertTrue(new ClassScope(['\\' . \mb_strtolower(self::class)])->contains($class));
        $this->assertTrue(new ClassScope([PHPStanTestCase::class])->contains($class));
        $this->assertFalse(new ClassScope([ArrayIterator::class])->contains($class));
    }

    public function testConfiguredInterfaceIncludesImplementations(): void
    {
        $class = self::createReflectionProvider()->getClass(ArrayIterator::class);

        $this->assertTrue(new ClassScope([Traversable::class])->contains($class));
    }

    public function testExclusionsTakePrecedenceOverIncludedClassAndNamespace(): void
    {
        $class = self::createReflectionProvider()->getClass(self::class);

        $this->assertFalse(new ClassScope([self::class], excludedClasses: [PHPStanTestCase::class])->contains($class));
        $this->assertFalse(new ClassScope(namespaces: ['Noldors\\PHPStan'], excludedNamespaces: ['Noldors\\PHPStan\\Tests'])->contains($class));
        $this->assertTrue(new ClassScope([self::class], excludedNamespaces: ['Noldors\\PHPStan\\Test'])->contains($class));
    }

    public function testNamespaceMatchingRespectsSegmentBoundaries(): void
    {
        $class = self::createReflectionProvider()->getClass(self::class);

        $this->assertTrue(new ClassScope(namespaces: ['\\noldors\\phpstan\\tests\\'])->contains($class));
        $this->assertFalse(new ClassScope(namespaces: ['Noldors\\PHPStan\\Test'])->contains($class));
        $this->assertFalse(new ClassScope(namespaces: [''])->contains($class));
    }
}
