<?php

declare(strict_types=1);

namespace ContractFixtures\UseCases;

use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

final class RejectInput
{
    public function handle(ValidationException $error, Validator $validator): void
    {
        throw $error; // @expect architecture.useCaseValidation
    }

    public function fromFactory(): never
    {
        throw ValidationException::withMessages(['name' => 'Missing']); // @expect architecture.useCaseValidation
    }

    public function bags(): void
    {
        new MessageBag(['name' => ['Missing']]); // @expect architecture.useCaseValidation
        new ViewErrorBag(); // @expect architecture.useCaseValidation
        ValidationException::withMessages(['name' => 'Missing']); // @expect architecture.useCaseValidation
        ValidationException::withMessages(...); // @expect architecture.useCaseValidation
    }

    public function executePrepared(Validator $validator): void
    {
        $validator->validate(); // @expect architecture.useCaseValidation
        $validator->validate(...); // @expect architecture.useCaseValidation
    }

    public function rethrowUnion(ValidationException|\DomainException $error): never
    {
        throw $error; // @expect architecture.useCaseValidation
    }
}

final class DomainFailure
{
    public function handle(Validator $validator): void
    {
        $unused = new ValidationException($validator);
        try {
            $this->businessFailure();
        } catch (ValidationException $error) {
            return;
        }
    }

    private function businessFailure(): never
    {
        throw new \DomainException('Business invariant failed');
    }
}
