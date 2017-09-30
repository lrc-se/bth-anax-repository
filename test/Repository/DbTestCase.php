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
            if (is_null(self::$pdo)) {
                self::$pdo = new \PDO('sqlite:' . ANAX_APP_PATH . '/db/test.sqlite');
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, 'test');
        }

        return $this->conn;
    }
}
