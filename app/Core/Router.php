<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

/**
 * Core Router supporting regex dynamic routes, request verbs, and middlewares
 */
class Router
{
    private array $routes = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add route with GET method
     */
    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Add route with POST method
     */
    public function post(string $path, array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Register a route
     */
    private function addRoute(string $method, string $path, array $handler, array $middlewares): void
    {
        // Convert dynamic parameters like /quiz/{id} into regex
        $regexPath = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $regexPath = '#^' . $regexPath . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'regex' => $regexPath,
            'controller' => $handler[0],
            'action' => $handler[1],
            'middlewares' => $middlewares
        ];
    }

    /**
     * Dispatch the current request
     */
    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Normalize trailing slashes
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $effectiveMethod = $method === 'HEAD' ? 'GET' : $method;

        // Overwrite method if _method post parameter is set (for PUT/DELETE forms)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $effectiveMethod = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $effectiveMethod && preg_match($route['regex'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Execute Middlewares
                foreach ($route['middlewares'] as $middlewareClass) {
                    $middleware = $this->container->get($middlewareClass);
                    $passed = $middleware->handle($params);
                    if (!$passed) {
                        return; // Stopped by middleware
                    }
                }

                // Resolve controller and run action
                $controller = $this->container->get($route['controller']);
                $action = $route['action'];

                if (!method_exists($controller, $action)) {
                    throw new Exception("Action {$action} not found in controller {$route['controller']}.");
                }

                // Call the controller action with named parameters
                call_user_func_array([$controller, $action], [$params]);
                return;
            }
        }

        // Route not found
        $this->sendNotFound();
    }

    /**
     * Send 404 Response
     */
    private function sendNotFound(): void
    {
        http_response_code(404);
        if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Endpoint not found']);
        } else {
            // Render basic 404 view or output directly
            echo "<h1>404 - Page Non Trouvée</h1><p>Désolé, la page demandée n'existe pas.</p><a href='/'>Retour à l'accueil</a>";
        }
    }
}
