<?php

namespace H2o\PermissionManager\PermissionManager\Store;


class CreateAction extends BaseAction
{
    public $action = 'CREATE';

    public function run($origin)
    {
        return new Permission($this->data, $this->date);
    }

    public function merge($change, $origin)
    {
        if (!$change) {
            return $this;
        }
        if ($change->action === 'DELETE' && $origin !== $this->date) {
            return new UpdateAction($this->name, $this->date, $this->date);
        }
        return null;
    }

    public function flip($origin)
    {
        return ['action' => 'DELETE', 'date' => $this->date];
    }
}
