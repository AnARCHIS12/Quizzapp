<?php

declare(strict_types=1);

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Services\WebSocketServer;

require dirname(__DIR__) . '/vendor/autoload.php';

// Disable error display in console / response (log securely to file/output)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('UTC');

// Load environment variables if .env exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // Remove enclosing quotes
            $val = trim($val, '"\'');
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
            putenv("{$key}={$val}");
        }
    }
}

$configuredPort = $_ENV['WS_INTERNAL_PORT'] ?? getenv('WS_INTERNAL_PORT');
$configuredPort = $configuredPort === false ? ($_ENV['WS_PORT'] ?? getenv('WS_PORT')) : $configuredPort;
$configuredPort = $configuredPort === false ? null : $configuredPort;
$port = $configuredPort !== null && $configuredPort !== '' ? (int) $configuredPort : 8080;

echo "=============================================\n";
echo " QUIZZAPP WEBSOCKET GAME DAEMON STARTED      \n";
echo "=============================================\n";
echo " Listening on port: {$port}                  \n";
echo " Database Target: " . ($_ENV['DB_NAME'] ?? 'quizzapp') . "\n";
echo " Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo " Running server loop...\n";

try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new WebSocketServer()
            )
        ),
        $port
    );

    $server->run();
} catch (\Exception $e) {
    fwrite(STDERR, "FATAL EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
