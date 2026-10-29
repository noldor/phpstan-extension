<?php

namespace Fixtures\DisabledTls;

function nativeBooleanOptions(): void
{
    stream_context_create(['ssl' => ['verify_peer' => 0]]);
    stream_context_create(['ssl' => ['verify_peer_name' => '0']]);
    stream_context_set_options(stream_context_create(), ['ssl' => ['verify_peer' => null]]);
    stream_context_set_option(stream_context_create(), ['ssl' => ['verify_peer_name' => 0]]);
    stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    stream_context_create(['ssl' => ['verify_peer' => 1]]);
}
