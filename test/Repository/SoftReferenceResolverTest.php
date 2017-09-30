<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase2.php';

/**
 * Test cases for class SoftReferenceResolverTrait.
 */
class SoftReferenceResolverTest extends DbTestCase2
{
    public function getRepositories()
    {
        $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db2.php');
        return [
            new SoftDbRepository($db, 'book', Book2::class, 'deleted', 'bookId'),
            new DbRepository($db, 'review', Review2::class, 'revId')
        ];
    }
    
    
    public function getDataSet()
    {
        return new YamlDataSet(ANAX_APP_PATH . '/test2.yml');
    }
    
    
    /**
     * Test getReferenceSoft method.
     */
    public function testGetReferenceSoft()
    {
        list($books, $reviews) = $this->getRepositories();
        $review = $reviews->find('revId', 1);
        $book = $review->getReferenceSoft($books, 'bookId', 'bookId');
        $this->assertNull($book);
        
        $review = $reviews->find('revId', 2);
        $book = $review->getReferenceSoft($books, 'bookId', 'bookId');
        $this->assertInstanceOf(Book2::class, $book);
        $this->assertEquals($book, $books->find('bookId', $book->bookId));
        
        $this->assertNull($review->getReferenceSoft($books, 'foo'));
    }
}
