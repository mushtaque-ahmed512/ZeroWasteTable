<?php
/**
 * Minimal router: "/donations/{id}/edit" style patterns, per-route middleware.
 */
class Router
{
    private array $routes = [];

    public function get(string $p, array $h, array $mw = []): void  { $this->add('GET', $p, $h, $mw); }
    public function post(string $p, array $h, array $mw = []): void { $this->add('POST', $p, $h, $mw); }

    private function add(string $method, string $pattern, array $handler, array $mw): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', trim($pattern, '/')) . '$#';
        $this->routes[] = [$method, $regex, $handler, $mw];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = trim($path, '/');
        foreach ($this->routes as [$m, $regex, $handler, $mw]) {
            if ($m !== $method || !preg_match($regex, $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            if ($method === 'POST') {
                array_unshift($mw, 'csrf');
            }
            foreach ($mw as $spec) {
                [$name, $args] = array_pad(explode(':', $spec, 2), 2, '');
                $class = ucfirst($name) . 'Middleware';
                (new $class())->handle($args === '' ? [] : explode(',', $args));
            }

            [$class, $action] = $handler;
            (new $class())->$action(...array_values($params));
            return;
        }

        (new class extends Controller {
            public function notFound(): void { $this->abort(404, 'The page you are looking for does not exist.'); }
        })->notFound();
    }
}
