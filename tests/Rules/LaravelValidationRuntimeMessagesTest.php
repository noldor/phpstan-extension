<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use Closure;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;
use Illuminate\Auth\Access\Gate;
use Illuminate\Validation\Factory;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\Translator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

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

    public function testNumericBuildersAddAnIndependentIntegerFailureAndFalseProducesAnEmptyMessage(): void
    {
        $loader = new ArrayLoader();
        $loader->addMessages('en', 'validation', ['integer' => 'DEFAULT integer']);
        $factory = new Factory(new Translator($loader, 'en'));

        foreach ([Rule::numeric()->digits(3), Rule::numeric()->digitsBetween(2, 3), Rule::numeric()->exactly(3)] as $rule) {
            $validator = $factory->make(['count' => 12.5], ['count' => [$rule]], ['count.numeric' => 'Number.', 'count.digits' => 'Digits.', 'count.digits_between' => 'Digits.', 'count.size' => 'Size.']);
            $this->assertContains('DEFAULT integer', $validator->errors()->get('count'));
        }
        $false = $factory->make([], ['name' => ['required']], ['name.required' => false]);
        $this->assertSame([''], $false->errors()->get('name'));
    }

    public function testNumericComparisonStateResetsBeforeTheNextBuiltinRule(): void
    {
        $loader = new ArrayLoader();
        $loader->addMessages('en', 'validation', ['max' => ['numeric' => 'DEFAULT numeric max'], 'min' => ['numeric' => 'DEFAULT numeric min']]);
        $factory = new Factory(new Translator($loader, 'en'));
        $validator = $factory->make(
            ['seed' => 1, 'name' => '123456', 'after' => '123456', 'file' => new File(__FILE__), 'items' => [1]],
            ['seed' => ['gt:0'], 'name' => ['bail', 'string', 'max:5', 'gt:10'], 'after' => ['bail', 'string', 'gt:0', 'max:5'], 'file' => ['bail', 'file', 'max:0', 'gt:10'], 'items' => ['array', 'gt:0', 'min:2']],
            ['name.max' => ['string' => 'String max.'], 'after.max' => ['string' => 'String max.'], 'file.max' => ['file' => 'File max.'], 'items.min' => ['array' => 'Array min.'], 'items.gt' => 'Greater.'],
        );
        $this->assertSame(['String max.'], $validator->errors()->get('name'));
        $this->assertSame(['String max.'], $validator->errors()->get('after'));
        $this->assertSame(['File max.'], $validator->errors()->get('file'));
        $this->assertSame(['Greater.', 'Array min.'], $validator->errors()->get('items'));
    }

    public function testNativeObjectShortAliasesDependOnTheMessageTypeAndClassKeysOverrideNestedFailures(): void
    {
        $this->withNativeFactory(function (Factory $factory): void {
            $short = $factory->make(['status' => 'unknown'], ['status' => [Rule::enum(RuntimeMessageEnum::class)]], ['status.enum' => 'SHORT enum']);
            $this->assertSame(['SHORT enum'], $short->errors()->get('status'));
            $file = $factory->make(['status' => new File(__FILE__)], ['status' => [Rule::enum(RuntimeMessageEnum::class)]], ['status.enum' => 'SHORT enum']);
            $this->assertSame(['DEFAULT enum'], $file->errors()->get('status'));

            foreach ([Rule::can('update'), Rule::enum(RuntimeMessageEnum::class), Rule::anyOf([['integer'], ['email']]), Rule::file()->max(0), \Illuminate\Validation\Rules\File::image(), Rule::email()->rules(['min:3']), Password::min(8)->letters()] as $rule) {
                $validator = $factory->make(['value' => new File(__FILE__)], ['value' => [$rule]], ['value.' . $rule::class => 'CLASS override']);
                $this->assertSame(['CLASS override'], $validator->errors()->get('value'), $rule::class);
            }
        });
    }

    public function testBailedNativeFilesExposeOnlyFileSizeFailuresAfterSuccessfulValidation(): void
    {
        $this->withNativeFactory(function (Factory $factory): void {
            foreach (['max:0' => 'max', 'gt:1000000' => 'gt'] as $constraint => $rule) {
                $rules = ['upload' => ['bail', Rule::file(), $constraint]];
                $messages = ['upload.' . \Illuminate\Validation\Rules\File::class => 'CLASS invalid', 'upload.' . $rule => ['file' => 'CUSTOM size file']];
                $file = $factory->make(['upload' => new File(__FILE__)], $rules, $messages);
                $this->assertSame(['CUSTOM size file'], $file->errors()->get('upload'));
                $text = $factory->make(['upload' => 'text'], $rules, $messages);
                $this->assertSame(['CLASS invalid'], $text->errors()->get('upload'));
            }
        });
    }

    /**
     * @param Closure(Factory): void $assert
     */
    private function withNativeFactory(Closure $assert): void
    {
        $loader = new ArrayLoader();
        $loader->addMessages('en', 'validation', ['enum' => 'DEFAULT enum']);
        $factory = new Factory(new Translator($loader, 'en'));
        $previousContainer = Container::getInstance();
        $container = new Application();
        $container->instance('validator', $factory);
        $container->instance(GateContract::class, new Gate($container, static fn (): null => null));
        $factory->setContainer($container);
        $previous = Facade::getFacadeApplication();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);

        try {
            $assert($factory);
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($previous);
            Container::setInstance($previousContainer);
        }
    }
}

enum RuntimeMessageEnum: string
{
    case Valid = 'valid';
}
