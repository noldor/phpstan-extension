<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Factory;
use Illuminate\Translation\Translator;
use Illuminate\Translation\ArrayLoader;
use Symfony\Component\HttpFoundation\File\File;

final class LaravelValidationRuntimeMessagesTest extends TestCase
{
    public function testLaravelSelectsOnlyTheRelevantSizeBranchAndFallsBackFromNull(): void
    {
        $loader = new ArrayLoader();
        $loader->addMessages('en', 'validation', ['required' => 'DEFAULT required', 'string' => 'DEFAULT string', 'max' => ['string' => 'DEFAULT string max', 'file' => 'DEFAULT file max']]);
        $factory = new Factory(new Translator($loader, 'en'));
        $wrongBranch = $factory->make(['name' => '123456'], ['name' => ['string', 'max:5']], ['name.string' => 'Text.', 'name.max' => ['numeric' => 'Numeric max']]);
        $this->assertSame(['DEFAULT string max'], $wrongBranch->errors()->get('name'));
        $null = $factory->make([], ['name' => ['required']], ['name.required' => null]);
        $this->assertSame(['DEFAULT required'], $null->errors()->get('name'));
        $file = $factory->make(['name' => new File(__FILE__)], ['name' => ['string', 'max:0']], ['name.max' => ['string' => 'String max']]);
        $this->assertSame(['DEFAULT string', 'DEFAULT file max'], $file->errors()->get('name'));
        $bailed = $factory->make(['name' => new File(__FILE__)], ['name' => ['bail', 'string', 'max:0']], ['name.max' => ['string' => 'String max']]);
        $this->assertSame(['DEFAULT string'], $bailed->errors()->get('name'));
    }
}
