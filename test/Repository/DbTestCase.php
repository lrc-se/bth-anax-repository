<?php

namespace LRC\Repository;

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

abstract class DbTestCase extends TestCase
{
    use TestCaseTrait;


    static private $pdo = null;
    
    private $conn = null;


    final public function getConnection()
    {
        if (is_null($this->conn)) {
            $this->conn = $this->createDefaultDBConnection(self::$pdo, 'test');
        }

        return $this->conn;
    }
    
    static public function setUpBeforeClass()
    {
        self::$pdo = new \PDO('sqlite:' . ANAX_APP_PATH . '/db/test.sqlite');
        self::$pdo->query(file_get_contents(ANAX_APP_PATH . '/db/book.sql'));
        self::$pdo->query(file_get_contents(ANAX_APP_PATH . '/db/review.sql'));
    }
}
