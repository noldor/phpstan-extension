<?php

namespace Fixtures\DisabledTls;

use GuzzleHttp\Client;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

function clients(Client $client, PendingRequest $http, bool $verify): void
{
    new Client(['verify' => false]);
    $client->request('GET', '/', ['verify' => false]);
    $client->get('/', ['verify' => false]);
    $client->request(options: ['verify' => false], method: 'GET', uri: '/');
    $http->withoutVerifying();
    $http->withOptions(['verify' => false]);
    Http::withoutVerifying();
    Http::withOptions(['verify' => false]);
    new Client(['verify' => '/etc/private-ca.pem']);
    $client->request('GET', '/', ['verify' => true]);
    $client->request('GET', '/', ['verify' => $verify]);
    $http->withoutVerifying(...);
    Http::withoutVerifying(...);
}

function native(\CurlHandle $handle, bool $verify): void
{
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt(handle: $handle, option: CURLOPT_SSL_VERIFYHOST, value: 0);
    curl_setopt_array($handle, [CURLOPT_SSL_VERIFYPEER => false]);
    stream_context_create(['ssl' => ['verify_peer' => false]]);
    stream_context_set_option(stream_context_create(), 'ssl', 'verify_peer_name', false);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $verify);
    stream_context_create(['ssl' => ['cafile' => '/etc/private-ca.pem']]);
    $call = curl_setopt(...);
}

class Unrelated
{
    public function withoutVerifying(): void {}
    public function withOptions(array $options): void {}
}

function unrelated(Unrelated $client): void
{
    $client->withoutVerifying();
    $client->withOptions(['verify' => false]);
}

function unusualCaPath(Client $client): void
{
    $client->get('/', ['verify' => '0']);
}
