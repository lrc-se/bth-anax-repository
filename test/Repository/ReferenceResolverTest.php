<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase.php';

/**
 * Test cases for class ReferenceResolverTrait.
 */
class ReferenceResolverTest extends DbTestCase
{
    public function getRepositories()
    {
        $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
        return [
            new DbRepository($db, 'book', Book::class),
            new DbRepository($db, 'review', Review::class)
        ];
    }
    
    
    public function getDataSet()
    {
        return new YamlDataSet(ANAX_APP_PATH . '/test.yml');
    }
    
    
    /**
     * Test getReference method.
     */
    public function testGetReference()
    {
        list($books, $reviews) = $this->getRepositories();
        $review = $reviews->find('id', 1);
        $book = $review->getReference($books, 'bookId');
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals($book, $books->find('id', $book->id));
        
        $this->assertNull($review->getReference($books, 'foo'));
    }
}
