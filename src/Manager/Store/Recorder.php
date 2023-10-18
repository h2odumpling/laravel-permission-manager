<?php

namespace H2o\PermissionManager\Manager\Store;


class Recorder
{
    /**
     * @var Permission[]
     */
    protected $items = [];

    /**
     * @var BaseAction[]
     */
    protected $records = [];

    /**
     * @var string|string[]
     */
    protected $scopes;

    public function __construct($scopes = null)
    {
        $this->scopes = $scopes;
    }

    public function getItem($name)
    {
        return $this->items[$name] ?? null;
    }

    public function getRecord($name)
    {
        return $this->records[$name] ?? null;
    }

    public function diff($items)
    {
        $changes = [];

        $origin = $this->items;

        foreach ($items as $key => $item) {
            if (isset($this->items[$key])) {
                $changes = array_merge($changes, $this->items[$key]->composer($item));
                unset($origin[$key]);
            } elseif (isset($item['scopes'])) {
                $changes[] = ['action' => 'CREATE', 'name' => $key, 'data' => $item];
            } else {
                $changes[] = ['action' => 'CREATE', 'name' => $key, 'data' => array_shift($item)];
                foreach ($item as $value) {
                    $changes[] = ['action' => 'APPEND', 'name' => $key, 'data' => $value];
                }
            }
        }

        foreach ($origin as $key => $item) {
            $changes[] = ['action' => 'DELETE', 'name' => $key, 'data' => null];
        }
        return $changes;
    }

    /**
     * @param BaseAction $action
     */
    public function call($action)
    {
        if ($this->check($action)) {
            $origin = $this->getItem($action->name);
            if ($item = $action->run($origin)) {
                $this->items[$action->name] = $item;
            } else {
                unset($this->items[$action->name]);
            }
        }
    }

    /**
     * @param BaseAction $action
     */
    public function record($action)
    {
        if ($this->check($action)) {
            $origin = $this->getItem($action->name);
            $record = $this->getRecord($action->name);

            if ($record = $action->merge($record, $origin)) {
                $this->records[$action->name] = $record;
            } elseif ($record === false) {
                unset($this->records[$action->name]);
            }
        }
    }

    public function getChanges()
    {
        return array_map(function ($record) {
            return $record->toArray();
        }, $this->records);
    }

    public function flipChanges()
    {
        return array_map(function ($item) {
            return $item->flip($this->getItem($item->name));
        }, $this->records);
    }

    /**
     * @param BaseAction $action
     * @return bool
     */
    protected function check($action)
    {
        return !$this->scopes || $action->check($this->scopes, $this->getRecord($action->name), $this->getItem($action->name));
    }
}
