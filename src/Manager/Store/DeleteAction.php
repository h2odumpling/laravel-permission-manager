<?php

namespace H2o\PermissionManager\Manager\Store;


use H2o\PermissionManager\Helper\Helper;

class DeleteAction extends BaseAction
{
    public $action = 'DELETE';

    public function run($origin)
    {
        return false;
    }

    public function check($scopes, $change, $origin)
    {
        if ($change) {
            if ($change->multiple) {
                return $this->hasScopes($change->data, $scopes);
            }
            if (isset($change->data['scopes']) && Helper::scope_exists($change->data['scopes'], $scopes)) {
                return true;
            }
        }
        if ($origin) {
            if ($origin->multiple) {
                return $this->hasScopes($origin->data, $scopes);
            }
            return Helper::scope_exists($origin->data['scopes'], $scopes);
        }
        return false;
    }

    public function merge($change, $origin)
    {
        if ($change && $change->multiple) {
            [$item, $index] = $change->find($this->data['scopes']);

            $change->date = $this->date;
            return $change->unset($index);
        }

        if ($origin) {
            return $this;
        }

        return false;
    }

    public function flip($origin)
    {
        return [
            'action' => 'CREATE',
            'date' => $origin->date,
            'data' => $this->getOriginData($origin)
        ];
    }

    protected function hasScopes($items, $scopes)
    {
        foreach ($items as $item) {
            if (Helper::scope_exists($item['scopes'], $scopes)) {
                return true;
            }
        }
        return false;
    }
}
