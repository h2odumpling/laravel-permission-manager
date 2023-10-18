<?php

namespace H2o\PermissionManager\Manager\Store;


use Illuminate\Support\Arr;
use H2o\PermissionManager\Helper\Helper;

class UpdateAction extends BaseAction
{
    public $action = 'UPDATE';

    public function run($origin)
    {
        if ($origin->multiple) {
            [$item, $index] = $origin->find($this->data['scopes']);
            $origin->data[$index]['data'] = array_merge($item, $this->data);
        } else {
            $origin->data = array_merge($origin->data, $this->data);
        }
        $origin->date = $this->date;
        return $origin;
    }

    public function check($scopes, $change, $origin)
    {
        if (isset($this->data['scopes'])) {
            return parent::check($scopes, $change, $origin);
        }
        if (isset($change->data['scopes'])) {
            return Helper::scope_exists($change->data['scopes'], $scopes);
        }
        return Helper::scope_exists($origin->data['scopes'], $scopes);
    }

    public function merge($change, $origin)
    {
        if ($change) {
            if ($change->multiple) {
                [$item, $index] = $change->find($this->data['scopes']);
                $change->data[$index] = array_merge($item, $this->data);
            } else {
                $change->date = array_merge($change->data, $this->data);
            }
            $change->date = $this->date;
            return $change;
        }

        if ($origin->multiple) {
            [$item, $index] = $origin->find($this->data['scopes']);

            $item = array_merge($item, $this->data);

            $this->data = $origin->data;
            $this->data[$index] = $item;
        }

        return $this;
    }

    public function flip($origin)
    {
        return [
            'action' => 'UPDATE',
            'date' => $origin->date,
            'data' => $this->getOriginData($origin)
        ];
    }
}
