<?php

namespace StandardsFixture\Lighthouse;

use Nuwave\Lighthouse\Validation\Validator;

class CreateValidator extends Validator // @expect lighthouse.validatorFinal
{
    public function rules(): array { return []; }
}

final class WrongValidator // @expect lighthouse.validatorBase
{
    public function rules(): array { return []; }
}

final class UpdateValidator extends Validator
{
    public function rules(): array { return []; }
}

abstract class ValidatorBase extends Validator {}

abstract class WrongValidatorBase
{
    abstract public function rules(): array;
}

final class MissingHandler // @expect lighthouse.resolverInvokable
{
    public function handle(): void {}
}

class HandlerBase
{
    public function __invoke(): void {}
}

final class InheritedHandler extends HandlerBase {}

class ChangedSubscription {} // @expect lighthouse.subscriptionFinal

final class CreatedSubscription {}
