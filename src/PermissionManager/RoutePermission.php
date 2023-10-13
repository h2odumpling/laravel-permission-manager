<?php


namespace H2o\PermissionManager\PermissionManager;


class RoutePermission
{
    public static function all()
    {
        return (new static)->getRoutes();
    }

    public function getRoutes()
    {
        $routes = [];

        $type = 'api';

        foreach (app()->router->getRoutes() as $route) {
            $name = $this->getName($route);

            if (!$name || count($parts = explode('@', $name, 2)) !== 2) {
                continue;
            }

            $auth = $parts[0];
            $name = $parts[1];

            $rbac_ignore = $name[0] === '!';

            if ($rbac_ignore) {
                $name = substr($name, 1);
            }

            if ($auth !== '*') {
                $auth = explode('|', $auth);
            }

            $path = $this->getUri($route);
            $method = $this->getMethod($route);

            $routes[] = compact('name', 'type', 'method', 'path', 'auth', 'rbac_ignore');
        }
        return $routes;
    }

    protected function getMethod($route)
    {
        return is_array($route) ? $route['method'] : $route->methods()[0];
    }

    protected function getName($route)
    {
        $action = is_array($route) ? $route['action'] : $route->getAction();

        if (empty($action['as'])) {
            return null;
        }

        if (empty($action['name_prefix'])) {
            return $action['as'];
        }

        if (is_array($action['name_prefix'])) {
            $prefix = implode('.', array_map(function ($prefix) {
                return rtrim($prefix, ' .');
            }, $action['name_prefix']));
        } else {
            $prefix = rtrim($action['name_prefix'], ' .');
        }

        if (strpos($prefix, '@') === strlen($prefix) - 1) {
            return $prefix . $action['as'];
        }

        return $prefix . '.' . $action['as'];
    }

    protected function getUri($route)
    {
        return preg_replace_callback('/\\{\\w+\\}?/', function ($str) {
            return ':' . substr($str[0], 1, -1);
        }, is_array($route) ? $route['uri'] : ('/' . $route->uri()));
    }
}
