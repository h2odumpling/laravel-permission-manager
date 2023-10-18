<?php

return [

    'migration_path' => 'database/permission',

    'middlewares' => [
        'permission'
    ],
    
    'active_env' => [
        'prod',
        'pre',
        'test',
    ],
];
