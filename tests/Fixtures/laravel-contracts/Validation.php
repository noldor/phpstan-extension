<?php

declare(strict_types=1);

namespace ContractFixtures\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Client\PendingRequest;
use ContractFixtures\Jobs\DispatchableJob;

final class ApplicationRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fail('Public validation failure');
    }

    public function writes(Model $model, Builder $query, Dispatcher $bus, PendingRequest $http): void
    {
        $model->save(); // @expect architecture.validationSideEffect
        $query->update(['name' => 'changed']); // @expect architecture.validationSideEffect
        $bus->dispatch(new DispatchableJob()); // @expect architecture.validationSideEffect
        $http->post('https://example.invalid'); // @expect architecture.validationSideEffect
        file_put_contents('/tmp/never-executed', 'data'); // @expect architecture.validationSideEffect
        dispatch(new DispatchableJob()); // @expect architecture.validationSideEffect
        event(new \stdClass()); // @expect architecture.validationSideEffect
        DispatchableJob::dispatch(); // @expect architecture.validationSideEffect
        $model->save(...); // @expect architecture.validationSideEffect
        file_put_contents(...); // @expect architecture.validationSideEffect
        \Illuminate\Support\Facades\DB::insert('insert into records (id) values (?)', [1]); // @expect architecture.validationSideEffect
        \Illuminate\Support\Facades\DB::table('records')->update(['name' => 'changed']); // @expect architecture.validationSideEffect
        $query->exists();
        $query->count();
        $http->withHeaders(['Accept' => 'application/json']);
        \Illuminate\Support\Facades\Auth::id();
    }

    public function delivers(\Illuminate\Contracts\Mail\Mailer $mailer, \Illuminate\Contracts\Mail\MailQueue $queue, \Illuminate\Mail\PendingMail $pending, \Illuminate\Contracts\Mail\Mailable $message): void
    {
        \ContractFixtures\Models\SoftRecord::destroy([1]); // @expect architecture.validationSideEffect
        \ContractFixtures\Models\SoftRecord::forceDestroy([1]); // @expect architecture.validationSideEffect
        $mailer->send('mail.notice'); // @expect architecture.validationSideEffect
        $mailer->raw('notice', static function (): void {}); // @expect architecture.validationSideEffect
        $queue->queue('mail.notice'); // @expect architecture.validationSideEffect
        $pending->send($message); // @expect architecture.validationSideEffect
        $pending->queue($message); // @expect architecture.validationSideEffect
        $message->send($mailer); // @expect architecture.validationSideEffect
        $mailer->to('reader@example.invalid');
        $pending->to('reader@example.invalid');
    }
}

final class AllowedWriter
{
    public function run(Model $model): void { $model->save(); }
}
