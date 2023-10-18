<?php

namespace H2o\PermissionManager\Manager\Store;


use Illuminate\Support\Arr;

class AppendAction extends BaseAction
{
    public $action = 'APPEND';

    public function run($origin)
    {
        if ($origin) {
            return $origin->append($this->data, $this->date);
        }
        return new Permission($this->data, $this->date);
    }

    public function merge($change, $origin)
    {
        if ($change) {
            return $change->append($this->data, $this->date);
        }

        if ($origin) {
            if ($origin->multiple) {
                $change = new UpdateAction($this->name, $this->data, Arr::pluck($origin->data, 'data'));
                $change->multiple = true;
            } else {
                $change = new UpdateAction($this->name, $this->data, $origin->data);
            }
            return $change->append($this->data, $this->date);
        }
        return new CreateAction($this->name, $this->data, $this->data);
    }
}
