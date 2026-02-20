<?php
declare(strict_types=1);

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $path = self::normalizePath($path);

        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(string $method, string $path): bool
    {
        $method = strtoupper($method);
        $path = self::normalizePath($path);

        $handlerSet = $this->routes[$method] ?? [];

        foreach ($handlerSet as $route => $handler) {
            $params = $this->match($route, $path);

            if ($params === null) {
                continue;
            }

            call_user_func_array($handler, $params);

            return true;
        }

        return false;
    }

    public function pathExistsForAnotherMethod(string $path, string $method): bool
    {
        $path = self::normalizePath($path);
        $method = strtoupper($method);

        foreach ($this->routes as $routeMethod => $handlerSet) {
            if ($routeMethod === $method) {
                continue;
            }

            foreach (array_keys($handlerSet) as $route) {
                if ($this->match($route, $path) !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function normalizePath(string $path): string
    {
        $clean = '/' . trim($path, '/');

        return $clean === '//' ? '/' : $clean;
    }

    /** @return array<int, string>|null */
    private function match(string $route, string $path): ?array
    {
        [$regex, $parameterNames] = $this->compileRoute($route);

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];

        foreach ($parameterNames as $parameterName) {
            $params[] = (string) ($matches[$parameterName] ?? '');
        }

        return $params;
    }

    /** @return array{0:string,1:array<int,string>} */
    private function compileRoute(string $route): array
    {
        $route = self::normalizePath($route);

        if ($route === '/') {
            return ['#^/$#', []];
        }

        $parts = explode('/', trim($route, '/'));
        $regexParts = [];
        $parameterNames = [];

        foreach ($parts as $part) {
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $part, $match) === 1) {
                $parameterNames[] = $match[1];
                $regexParts[] = '(?P<' . $match[1] . '>[^/]+)';
                continue;
            }

            $regexParts[] = preg_quote($part, '#');
        }

        $regex = '#^/' . implode('/', $regexParts) . '$#';

        return [$regex, $parameterNames];
    }
}
