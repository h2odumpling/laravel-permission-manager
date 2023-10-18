<?php

namespace H2o\PermissionManager\Manager\Store;


use Illuminate\Support\Arr;
use H2o\PermissionManager\Helper\Helper;

abstract class BaseAction
{
    public $action;

    public $name;

    public $date;

    public $data;

    public $multiple = false;

    public function __construct($name, $date, $data)
    {
        $this->name = $name;
        $this->date = $date;
        $this->data = $data;
    }

    /**
     * @param Permission $origin
     * @return mixed
     */
    abstract public function run($origin);

    /**
     * @param $scopes
     * @param self $change
     * @param Permission $origin
     * @return mixed
     */
    public function check($scopes, $change, $origin)
    {
        return Helper::scope_exists($this->data['scopes'], $scopes);
    }

    /**
     * @param self $change
     * @param Permission $origin
     * @return mixed
     */
    abstract public function merge($change, $origin);

    public function find($scopes)
    {
        foreach ($this->data as $index => $value) {
            if ($value['scopes'] === $scopes) {
                return [$value, $index];
            }
        }
        return [null, false];
    }

    public function unset($index)
    {
        unset($this->data[$index]);

        if (count($this->data) === 1) {
            $this->data = current($this->data);
            $this->multiple = false;
        }

        return $this;
    }

    public function append($data, $date)
    {
        $this->date = $date;

        if ($this->multiple) {
            $this->data[] = $data;
        } else {
            $this->data = [$this->data, $data];
            $this->multiple = true;
        }

        return $this;
    }

    public function remove($scopes, $date)
    {
        $this->date = $date;
        return $this->unset($this->find($scopes)[1]);
    }

    public function toArray()
    {
        return [
            'action' => $this->action,
            'date' => $this->date,
            'data' => $this->getData()
        ];
    }

    protected function mergeData($items)
    {
        $data = ['name' => $this->name, 'scopes' => [], 'content' => []];

        foreach ($items as $item) {
            $data['type'] = $item['type'];
            $data['scopes'] = array_merge($data['scopes'], $item['scopes']);

            if (isset($item['content'][0])) {
                $data['content'] = array_merge($data['content'], $item['content']);
            } else {
                $data['content'][] = $item['content'];
            }
        }

        $data['scopes'] = array_unique($data['scopes']);
        usort($data['scopes'], [Helper::class, 'scope_cmp']);

        return $data;
    }

    protected function getData()
    {
        return $this->multiple ? $this->mergeData($this->data) : $this->data;
    }

    protected function getOriginData($origin)
    {
        return $origin->multiple ? $this->mergeData(Arr::pluck($origin->data, 'data')) : $origin->data;
    }
}
