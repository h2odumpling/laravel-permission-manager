<?php


namespace H2o\PermissionManager\PermissionManager;

class LocalRepository extends StoreAbstract
{
    public function getLastUpdateTime()
    {
        return (new (config('permission.models.permission')))->query($this->serverScopeName())->max('updated_at');
    }

    public function saveChanges($changes)
    {
        if (empty($changes)) {
            return 0;
        }
        return \DB::transaction(function () use ($changes) {
            foreach ($changes as $name => $change) {
                if (!empty($change['data'])) {
                    $change['data'] = array_map(function ($data) {
                        return is_array($data) ? json_encode($data) : $data;
                    }, $change['data']);
                    $change['data']['updated_at'] = $change['date'];
                }

                $table_name = config('permission.table_names.permissions');

                switch ($change['action']) {
                    case 'CREATE':
                        $change['data']['created_at'] = $change['date'];
                        \DB::table($table_name)->insert($change['data']);
                        break;
                    case 'UPDATE':
                        \DB::table($table_name)->where('name', $name)->update($change['data']);
                        break;
                    default:
                        \DB::table($table_name)->where('name', $name)->delete();
                        break;
                }
            }
            return count($changes);
        });
    }
}
