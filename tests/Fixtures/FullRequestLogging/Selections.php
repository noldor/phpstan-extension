<?php

namespace Fixtures\FullRequestLogging;

use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

function selections(LoggerInterface $logger, Request $request): void
{
    $logger->info('event', ['body' => $request->all([])]);
    $logger->info('event', ['body' => $request->all(false)]);
    $logger->info('event', ['body' => $request->all('')]);
    $logger->info('event', ['body' => $request->all(0)]);
    $logger->info('event', ['body' => $request->all(null)]);
    $logger->info('event', ['body' => $request->json(default: [])]);
    $logger->info('event', ['body' => $request->json(key: null, default: [])]);
    $logger->info('event', ['optional' => null, 'flag' => false, 'empty' => [], 'text' => '']);
    $logger->info('event', ['selected' => $request->all(['orderId'])]);
    $logger->info('event', ['selected' => $request->all('orderId')]);
    $logger->info('event', ['selected' => $request->json(key: 'orderId')]);
    $logger->info('event', ['selected' => $request->json(key: '', default: [])]);
}
