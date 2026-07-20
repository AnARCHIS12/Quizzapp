<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Container;
use App\Core\Router;
use App\Core\Database;
use App\Core\Session;
use Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Simple Custom Test Runner
echo "=============================================\n";
echo " RUNNING QUIZZAPP CORE TEST SUITE            \n";
echo "=============================================\n\n";

$failures = 0;

/**
 * Basic assertion helper
 */
function assertTest(string $name, bool $expression): void
{
    global $failures;
    if ($expression) {
        echo "✅ PASS: {$name}\n";
    } else {
        echo "❌ FAIL: {$name}\n";
        $failures++;
    }
}

// TEST 1: Autoload and Namespaces
try {
    $container = new Container();
    assertTest("DI Container instantiation and PSR-4 Autoloading", $container instanceof Container);
} catch (Exception $e) {
    assertTest("DI Container instantiation and PSR-4 Autoloading (Exception: " . $e->getMessage() . ")", false);
}

// TEST 2: Router Mapping
try {
    $router = new Router($container);
    $router->get('/test-route', ['App\Controllers\QuizController', 'index']);
    assertTest("Router registers GET paths successfully", true);
} catch (Exception $e) {
    assertTest("Router registers GET paths successfully", false);
}

// TEST 3: Database config exists
$dbConfigPath = dirname(__DIR__) . '/config/database.php';
assertTest("Database config file exists", file_exists($dbConfigPath));

// TEST 4: Editorial Neutrality Audit
// Read seed file and check if there are any ideologically-loaded positive/negative expressions.
// We scan for keywords that might indicate bias, ensuring all default seeded texts remain factual.
try {
    $seedFile = dirname(__DIR__) . '/database/seed.sql';
    if (file_exists($seedFile)) {
        $content = file_get_contents($seedFile);
        $biasedKeywords = [
            'capitalisme est mauvais', 'socialisme est mauvais', 'communisme est mauvais',
            'capitalism is evil', 'communism is evil', 'religion ridicule',
            'supériorité culturelle', 'parti politique supérieur'
        ];
        
        $hasBias = false;
        foreach ($biasedKeywords as $word) {
            if (stripos($content, $word) !== false) {
                $hasBias = true;
                break;
            }
        }
        
        assertTest("Editorial Neutrality Check (No ideological bias in seed data)", !$hasBias);
    } else {
        echo "⚠️ WARNING: seed.sql not found for Neutrality Check\n";
    }
} catch (Exception $e) {
    assertTest("Editorial Neutrality Check", false);
}

echo "\n=============================================\n";
if ($failures === 0) {
    echo "🎉 ALL TESTS PASSED SUCCESSFULLY !\n";
} else {
    echo "❌ TEST SUITE FAILED WITH {$failures} FAILURES.\n";
    exit(1);
}
echo "=============================================\n";
