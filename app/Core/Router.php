<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): self
    {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }

    public function post(string $path, array $handler): self
    {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }

    public function dispatch(string $uri, string $method): void
    {
        // Alap URL path kinyerése (query string nélkül)
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        $method = strtoupper($method);

        // Pontos egyezés keresése
        if (isset($this->routes[$method][$path])) {
            $this->callHandler($this->routes[$method][$path]);
            return;
        }

        // Paraméteres route keresése
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = $this->routeToRegex($route);
            if (preg_match($pattern, $path, $matches)) {
                // Elnevezett paraméterek kinyerése
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->callHandler($handler, $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        if (file_exists(__DIR__ . '/../Views/errors/404.php')) {
            require __DIR__ . '/../Views/errors/404.php';
        } else {
            echo '404 - Az oldal nem található';
        }
    }

    private function routeToRegex(string $route): string
    {
        // {id} -> (?P<id>[0-9]+)
        // {slug} -> (?P<slug>[a-zA-Z0-9_-]+)
        $pattern = preg_replace_callback('/\{(\w+)\}/', function ($m) {
            $name = $m[1];
            if ($name === 'id') {
                return '(?P<id>[0-9]+)';
            }
            return '(?P<' . $name . '>[a-zA-Z0-9_-]+)';
        }, $route);

        return '#^' . $pattern . '$#';
    }

    private function callHandler(array $handler, array $params = []): void
    {
        [$controllerClass, $method] = $handler;

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            http_response_code(500);
            die("Method {$method} not found in " . $controllerClass);
        }

        call_user_func_array([$controller, $method], $params);
    }
}
