<?php

namespace Fixtures\DisabledTls;

function curlIntegerOptions(\CurlHandle $handle): void
{
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, '0.0');
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0.5);
    curl_setopt_array($handle, [CURLOPT_SSL_VERIFYPEER => 0.5]);
    curl_setopt_array($handle, [CURLOPT_SSL_VERIFYHOST => '0.0']);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, '2');
    stream_context_create(['ssl' => ['verify_peer' => '0.0']]);
}
