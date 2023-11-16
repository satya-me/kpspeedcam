<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('debugLog')) {
    function debugLog($message, $context = [])
    {
        Log::channel('debug')->debug($message, $context);
    }
}
