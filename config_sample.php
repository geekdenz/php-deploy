<?php
return array(
    'debug' => true,
    'deploy_db' => true, // include db dump, zip, transfer, unzip and restore on remote
    'rsync_delete' => true, // sync with deleting files

    'local' => array(
        //'user' => 'hansbrix'; // system user, defaults to output of whoami (current user)
        'host' => 'localhost', // local host
        // db details:
        'db' => 'mydb',
        'db_host' => 'localhost',
        'db_user' => 'dbuser',
        'db_password' => 'password',

        'directory' => '..', // director to deploy
        'exclude' => array( // exclude glob patterns
            '*.git/'
        ),
    ),

    'remote' => array(
        //'user' => 'hansbrix'; // defaults to same as local user
        'host' => 'localhost', // host to rsync to
        // db details of remote
        'db' => 'remotedb', // db name
        'db_host' => 'remotehost',
        'db_user' => 'remotedbuser',
        'db_password' => 'remotedbpass',

        'directory' => '/var/www/mywebsite', // directory to copy rsync files to

        'to_keep' => array(
            'assets', // exclude these from delete or better rsync to tmp dir and copy back
            //'images',
        ),
    ),
    'tasks' => array( // shell commands to run after deployment
        "wget 'http://www.example.com/dev/build' --",
    ),
);
