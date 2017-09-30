<?php
/**
 * Config file for database connection.
 */

return [
    'fetch_mode' => \PDO::FETCH_OBJ,
    'table_prefix' => '',
    'session_key' => 'kabc16-anax-db',

    // True to be very verbose during development
    'verbose' => null,

    // True to be verbose on connection failed
    'debug_connect' => false,
    
    'dsn' => 'sqlite:' . ANAX_APP_PATH . '/db/test2.sqlite',
    'username' => '',
    'password' => ''
];
