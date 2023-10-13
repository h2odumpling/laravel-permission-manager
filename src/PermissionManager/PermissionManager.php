<?php

namespace H2o\PermissionManager\PermissionManager;

use Illuminate\Support\Arr;
use H2o\PermissionManager\Helper\Helper;

class PermissionManager
{
    protected $server;

    protected $logger;

    protected $config;

    protected $stores;

    protected $scopes;

    public function __construct($server = null)
    {
        $this->config = config('h2o-permission');

        $this->server = $server ?: $this->config('server');

        $this->logger = new LoggerRepository($this->config('migration_path'));

        $this->initStores();
    }

    public function config($name = null, $default = null)
    {
        return Arr::get($this->config, $name, $default);
    }

    public function all()
    {
        return $this->format(RoutePermission::all());
    }

    public function localStore()
    {
        return $this->store($this->stores['local']);
    }

    public function sync($time = null)
    {
        $counter = 0;
        foreach ($this->stores as $store) {
            $counter += $this->store($store)->sync($time);
        }
        return $counter;
    }

    public function rollback($time = null)
    {
        $counter = 0;
        foreach ($this->stores as $store) {
            $counter += $this->store($store)->rollback($time);
        }
        return $counter;
    }

    public function record()
    {
        $recorder = $this->logger->read(INF);

        $changes = $recorder->diff($this->all());

        $this->logger->write($changes);
        return count($changes);
    }

    public function setFilePath($path)
    {
        $this->logger->setPath($path);
        return $this;
    }

    public function serverScopeName()
    {
        if ($this->server) {
            return '__' . $this->server;
        }
        return null;
    }

    protected function initStores()
    {
        $this->stores = [];
        if (!empty($remote = $this->config('remote'))) {
            if (is_array($remote)) {
                foreach ($remote as $key => $value) {
                    $scopes = ($key === '*' || is_integer($key)) ? '*' : explode('|', $key);
                    $this->stores[] = ['scopes' => $scopes, 'url' => $value];
                }
            } else {
                $this->stores[] = ['scopes' => '*', 'url' => $remote];
            }
        }
        if ($local = $this->config('local')) {
            $this->stores['local'] = ['scopes' => $local == '*' ? $local : explode('|', $local)];
        } elseif (!$remote) {
            $this->stores['local'] = ['scopes' => '*'];
        }

        $this->scopes = [];
        foreach ($this->stores as $store) {
            if ($store['scopes'] === '*') {
                $this->scopes = '*';
                break;
            } else {
                $this->scopes = array_merge($this->scopes, $store['scopes']);
            }
        }

        if ($this->scopes !== '*') {
            $this->scopes = array_unique($this->scopes);
        }
    }

    protected function filter($item)
    {
        if (!empty($item['content']['rbac_ignore'])) {
            return false;
        }
        return Helper::scope_exists($item['scopes'], $this->scopes);
    }

    protected function store($config)
    {
        $scopes = [$this->serverScopeName(), $config['scopes']];
        if (empty($config['url'])) {
            return new LocalRepository($this->logger, $scopes);
        }
        return new RemoteRepository($this->logger, $scopes, $config['url'], $this->server);
    }

    protected function format(array $items)
    {
        $data = [];
        foreach ($items as $item) {
            if (!$this->filter($item)) {
                continue;
            }
            if (isset($data[$item['name']])) {
                $data[$item['name']] = $this->mergeRouteConfig($data[$item['name']], $item);
            } else {
                $data[$item['name']] = $item;
            }
        }
        return $data;
    }

    protected function mergeRouteConfig($config, $item)
    {
        if (isset($config['scopes'])) {
            if ($config['scopes'] === $item['scopes']) {
                return $this->mergeConfigContent($config, $item);
            }
            return [$config, $item];
        }
        foreach ($config as $index => $value) {
            if ($value['scopes'] === $item['scopes']) {
                $config[$index] = $this->mergeConfigContent($value, $item);
                return $config;
            }
        }
        $config[] = $item;
        return $config;
    }

    protected function mergeConfigContent($config, $item)
    {
        if (empty($config['content'][0])) {
            $config['content'] = [$config['content']];
        }
        foreach ($config['content'] as $index => $value) {
            if ($value['url'] > $item['content']['url']) {
                array_splice($config['content'], $index, 0, [$item['content']]);
                return $config;
            }
        }
        $config['content'][] = $item['content'];
        return $config;
    }

    protected function parseRouteScope($scope)
    {
        $parts = explode('.', $scope, 2);
        if (empty($parts[1])) {
            return [$parts[0]];
        }
        $scopes = [];
        foreach (explode(',', $parts[1]) as $item) {
            $scopes[] = $item && $item !== 'default' ? "{$parts[0]}.$item" : $parts[0];
        }
        return $scopes;
    }
}
