<?php
return array(
    'debug' => true,
    'deploy_db' => true,
    'rsync_delete' => true,

    'local_host' => 'localhost',
    'local_db' => 'mydb',
    'local_db_host' => 'localhost',
    'local_db_user' => 'dbuser',
    'local_db_password' => 'password',

    'local_directory' => '..',
    'local_exclude' => array(
        '*.git/'
    ),

    //'remote_user' => 'hansbrix';
    'remote_host' => 'localhost',
    'remote_db' => 'remotedb',
    'remote_db_host' => 'remotehost',
    'remote_db_user' => 'remotedbuser',
    'remote_db_password' => 'remotedbpass',

    'remote_directory' => '/var/www/mywebsite',

    'remote_to_keep' => array(
        'assets/'
    ),
);
