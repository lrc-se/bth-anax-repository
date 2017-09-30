<?php

/**
 * Test configuration.
 */


/**
 * Define essential Anax paths, end with /
 */
define('ANAX_INSTALL_PATH', realpath(__DIR__ . '/../../anax'));
define('ANAX_APP_PATH', __DIR__);



/**
 * Include autoloader.
 */
require ANAX_INSTALL_PATH . '/vendor/autoload.php';


/**
 * Include other files to test, for example mock files.
 */
require_once 'models/Book.php';
require_once 'models/Book2.php';
require_once 'models/Review.php';
require_once 'models/Review2.php';
