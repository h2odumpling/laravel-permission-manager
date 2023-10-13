<?php

namespace H2o\PermissionManager\PermissionManager\Store;


use Illuminate\Support\Arr;

class RemoveAction extends BaseAction
{
    public $action = 'REMOVE';

    public function run($origin)
    {
        return $origin->remove($this->data['scopes']);
    }

    public function merge($change, $origin)
    {
        if ($change) {
            return $change->remove($this->data['scopes'], $this->date);
        }

        if ($origin) {
            if ($origin->multiple) {
                $change = new UpdateAction($this->name, $this->data, Arr::pluck($origin->data, 'data'));
                $change->multiple = true;
                return $change->remove($this->data['scopes'], $this->date);
            }
            return new DeleteAction($this->name, $this->date, null);
        }

        return null;
    }
}
