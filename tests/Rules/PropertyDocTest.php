<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Noldors\PHPStan\Eloquent\PropertyDoc;

final class PropertyDocTest extends TestCase
{
    public function testExtractsActualPropertyNamesAndUnicodeSourceLines(): void
    {
        $documents = new PropertyDoc();
        $comment = "/** Кириллица в описании.\n * @property callable(int \$argument): string \$label\n * @property-read string \$other\n */";
        $this->assertSame([['name' => 'label', 'line' => 11], ['name' => 'other', 'line' => 12]], $documents->entries($comment, 10));
    }
}
