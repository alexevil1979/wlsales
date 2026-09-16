<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{methods: list<string>, pattern: string, handler: callable|array{0:class-string,1:string}, middleware: list<string>}> */
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add(['GET'], $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add(['POST'], $path, $handler, $middleware);
    }

    /** @param list<string> $methods */
    public function add(array $methods, string $path, callable|array $handler, array $middleware = []): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }
            $params = [];
            foreach ($matches as $k => $v) {
                if (!is_int($k)) {
                    $params[$k] = $v;
                }
            }

            foreach ($route['middleware'] as $mw) {
                $this->runMiddleware($mw);
            }

            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->$action(...array_values($params));
                return;
            }
            $handler(...array_values($params));
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Страница не найдена'], 'site');
    }

    private function runMiddleware(string $name): void
    {
        match ($name) {
            'auth' => Auth::requireLogin(),
            'admin' => Auth::requireAdmin(),
            'guest' => Auth::requireGuest(),
            default => null,
        };
    }
}
