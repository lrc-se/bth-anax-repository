<?php

/**
 * Test configuration.
 */


/**
 * Define essential Anax paths.
 */
define('ANAX_INSTALL_PATH', __DIR__);
define('ANAX_APP_PATH', ANAX_INSTALL_PATH);


/**
 * Include autoloader.
 */
require ANAX_INSTALL_PATH . '/../vendor/autoload.php';


/**
 * Include other files to test, for example mock files.
 */
require_once 'models/Book.php';
require_once 'models/Book2.php';
require_once 'models/Review.php';
require_once 'models/Review2.php';
require_once 'models/Review3.php';
require_once 'models/Review4.php';
