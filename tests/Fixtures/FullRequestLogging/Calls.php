<?php

namespace Fixtures\FullRequestLogging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

function logRequest(LoggerInterface $logger, Request $request, SymfonyRequest $symfony): void
{
    $logger->info('received', ['body' => $request->all()]);
    $logger->warning('received', ['request' => $request]);
    $logger->log('info', 'received', ['raw' => $request->getContent()]);
    $logger->info(message: 'received', context: ['payload' => $_POST]);
    $logger->info('received', ['nested' => ['payload' => $_REQUEST]]);
    $logger->info('received', ['request' => $symfony]);
    Log::info('received', ['payload' => $request->all()]);
    $logger->info('received', ['id' => $request->input('id')]);
    $logger->info('received', ['subset' => $request->only(['id'])]);
    $logger->info('received', ['deferred' => static fn () => $request->all()]);
    $logger->info('received', ['redacted' => redact($request->all())]);
    $logger->info('received', ['key' => $_POST['id']]);
    $logger->info(...);
}

function redact(array $payload): array { return []; }

class Other
{
    public function info(string $message, array $context): void {}
    public function all(): array { return []; }
}

function unrelated(LoggerInterface $logger, Other $other, Request $request): void
{
    $other->info('received', ['body' => $request->all()]);
    $logger->info('received', ['body' => $other->all()]);
}

function selectedInput(LoggerInterface $logger, Request $request): void
{
    $logger->info('received', ['fields' => $request->all(['id'])]);
    $logger->info('received', ['id' => $request->json('id')]);
}
