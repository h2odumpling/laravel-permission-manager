<?php

namespace H2o\Permission\PermissionManager\Store;


class Permission
{
    public $date;

    public $data;

    public $multiple = false;

    public function __construct($data, $date)
    {
        $this->data = $data;
        $this->date = $date;
    }

    public function find($scopes)
    {
        foreach ($this->data as $index => $value) {
            if ($value['data']['scopes'] === $scopes) {
                return [$value['data'], $index];
            }
        }
        return [null, false];
    }

    public function append($data, $date)
    {
        $item = compact('data', 'date');
        if ($this->multiple) {
            $this->data[] = $item;
        } else {
            $this->data = [['data' => $this->data, 'date' => $this->date], $item];
            $this->multiple = true;
        }

        if ($date > $this->date) {
            $this->date = $date;
        }

        return $this;
    }

    public function remove($scopes)
    {
        [$item, $index] = $this->find($scopes);

        unset($this->data[$index]);

        if (count($this->data) === 1) {
            $this->multiple = false;
            $item = current($this->data);
            $this->data = $item['data'];
            $this->date = $item['date'];
        } else {
            $this->date = array_reduce($this->data, function ($max, $item) {
                return max($max, $item['date']);
            });
        }

        return $this;
    }

    public function composer($after)
    {
        $dirty = [];
        if (!$this->multiple) {
            if (isset($after['scopes'])) {
                if ($this->data == $after) {
                    return $dirty;
                }
                return [['action' => 'UPDATE', 'name' => $after['name'], 'data' => $this->diff($this->data, $after)]];
            }
            $match = false;
            foreach ($after as $item) {
                if ($item['scopes'] === $this->data['scopes']) {
                    if ($item !== $this->data) {
                        $dirty[] = $this->makeUpdateAction($this->data, $item);
                    }
                    $match = true;
                } else {
                    $dirty[] = ['action' => 'APPEND', 'name' => $item['name'], 'data' => $item];
                }
            }
            if (!$match) {
                $dirty[] = ['action' => 'REMOVE', 'name' => $this->data['name'], 'data' => ['scopes' => $this->data['scopes']]];
            }
            return $dirty;
        }

        if (isset($after['scopes'])) {
            [$item, $index] = $this->find($after['scopes']);

            if ($index === false) {
                return [
                    ['action' => 'DELETE', 'name' => $after['name'], 'data' => null],
                    ['action' => 'CREATE', 'name' => $after['name'], 'data' => $after]
                ];
            }
            if ($item != $after) {
                $dirty[] = $this->makeUpdateAction($item, $after);
            }
            foreach ($this->data as $key => $item) {
                if ($index !== $key) {
                    $dirty[] = ['action' => 'REMOVE', 'name' => $after['name'], 'data' => ['scopes' => $item['scopes']]];
                }
            }
            return $dirty;
        }

        $before = $this->data;

        foreach ($after as $item) {
            [$origin, $index] = $this->find($item['scopes']);

            if ($index === false) {
                $dirty[] = ['action' => 'APPEND', 'name' => $item['name'], 'data' => $item];
            } else {
                if ($item != $origin) {
                    $dirty[] = $this->makeUpdateAction($origin, $item);
                }
                unset($before[$index]);
            }
        }

        foreach ($before as $item) {
            $dirty[] = ['action' => 'REMOVE', 'name' => $item['data']['name'], 'data' => ['scopes' => $item['data']['scopes']]];
        }

        return $dirty;
    }

    public function diff($before, $after)
    {
        return array_udiff_assoc($after, $before, function ($a, $b) {
            return $a == $b ? 0 : 1;
        });
    }

    protected function makeUpdateAction($before, $after)
    {
        $diff = $this->diff($before, $after);
        $diff['scopes'] = $after['scopes'];
        return ['action' => 'UPDATE', 'name' => $after['name'], 'data' => $diff];
    }
}
