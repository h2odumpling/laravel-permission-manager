<?php

namespace H2o\PermissionManager\Manager;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Laravel\Lumen\Routing\Router;

class PermissionMaker
{
    protected $logger;

    protected $config;

    protected $type;

    public function __construct($type = 'admin')
    {
        $this->config = config('h2o-permission');

        $this->type = $type;

        $this->logger = new LoggerRepository($this->config('migration_path'));
    }

    public function setType(string $type){
        $this->type = $type;
        return $this;
    }

    public function sync(){
        $recorder = $this->logger->read(null, null, [$this->type]);

        $synced = array_keys($recorder->getChanges());

        $routes = $this->getSyncRoutes($synced);

        if(!is_dir($this->config('migration_path'))) {
            mkdir($this->config('migration_path'), 0777, true);
        }

        $this->logger->write($routes);

        return count($routes);
    }

    protected function getSyncRoutes($synced = [])
    {
        $routeCollection = Route::getRoutes();

        $rows = array();
        foreach ($routeCollection as $route) {

            $name = $this->getNamedRoute($route);

            $scope = substr($name, 0, strpos($name, '.'));

            $middleware = $this->getMiddleware($route->action);

            if(!$this->checkRoute($name, $scope, $middleware, $synced))
                continue;

            $rows[] = [
                'action'    => 'CREATE',
                'name'      => $name,
                'data'      => [
                    'name'       => $name,
                    'comment'    => $this->getAction($route->action),
                    'url'        => $route->uri,
                    'method'     => $route->methods[0],
                    'guard_name' => $scope,
                    'scopes'     => [$scope],
                ]
            ];
        }
        return $rows;
    }

    public function checkRoute($name, $scope, $middleware, $synced){
        if(in_array($name, $synced))
            return false;
        if($scope != $this->type)
            return false;
        if(!array_intersect($this->config('middlewares'), $middleware))
            return false;
        return true;
    }

    protected function getNamedRoute($action)
    {
        return $action->action['as'] ?? str_replace('/', '.', $action->uri);
    }

    protected function getAction(array $action)
    {
        if (!empty($action['uses'])) {
            $data = $action['uses'];
            if (($pos = strpos($data, "@")) !== false) {
                return substr($data, $pos + 1);
            } else {
                return "METHOD NOT FOUND";
            }
        } else {
            return 'Closure';
        }
    }

    protected function getMiddleware(array $action)
    {
        return (isset($action['middleware']))
            ? (is_array($action['middleware']))
                ? $action['middleware']
                : [$action['middleware']]
            : [];
    }

    public function config($name = null, $default = null)
    {
        return Arr::get($this->config, $name, $default);
    }
}
