<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Use a string path so Laravel never gets false when storage/framework/views
    | does not exist yet (e.g. fresh Docker volume). Directory is created by
    | entrypoint; realpath() would return false and break the view compiler.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        storage_path('framework/views')
    ),

];
