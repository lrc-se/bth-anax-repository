<?php

namespace LRC\Repository;

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

abstract class DbTestCase3 extends TestCase
{
    use TestCaseTrait;


    static private $pdo = null;
    
    private $conn = null;


    final public function getConnection()
    {
        if (is_null($this->conn)) {
            $this->conn = $this->createDefaultDBConnection(self::$pdo, 'test3');
        }

        return $this->conn;
    }
    
    public static function setUpBeforeClass()
    {
        self::$pdo = new \PDO('sqlite:' . ANAX_APP_PATH . '/db/test3.sqlite');
        self::$pdo->query(file_get_contents(ANAX_APP_PATH . '/db/user.sql'));
        self::$pdo->query(file_get_contents(ANAX_APP_PATH . '/db/question.sql'));
        self::$pdo->query(file_get_contents(ANAX_APP_PATH . '/db/answer.sql'));
    }
}
