<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$key = config('services.gemini.key');
if (! is_string($key) || trim($key) === '') {
    fwrite(STDERR, 'GEMINI_API_KEY is not configured.'.PHP_EOL);
    exit(1);
}

echo 'Gemini API key is configured (value hidden).'.PHP_EOL;

$candidates = ['gemini-3.8-flash', 'gemini-3.5-flash', 'gemini-2.5-flash-lite', 'gemini-flash-latest'];
foreach ($candidates as $mod) {
    try {
        $res = Http::timeout(8)->withHeaders([
            'x-goog-api-key' => $key,
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$mod}:generateContent", [
            'contents' => [
                ['parts' => [['text' => 'Hi, reply with just "ok"']]],
            ],
        ]);
        echo "Model {$mod}: HTTP ".$res->status().' -> '.substr($res->body(), 0, 120).PHP_EOL;
    } catch (Throwable $e) {
        echo "Model {$mod}: Exception: ".$e->getMessage().PHP_EOL;
    }
}
