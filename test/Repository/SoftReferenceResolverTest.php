<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase2.php';

/**
 * Test cases for class SoftReferenceResolverTrait.
 */
class SoftReferenceResolverTest extends DbTestCase2
{
    private function getRepositories()
    {
        // custom attribute names
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
        
        // existing attribute (non-deleted reference, custom key attribute name)
        $review = $reviews->find('revId', 2);
        $book = $review->getReferenceSoft($books, 'bookId', 'bookId');
        $this->assertInstanceOf(Book2::class, $book);
        $this->assertEquals($books->find('bookId', $book->bookId), $book);
        
        // existing attribute (deleted reference, custom key attribute name)
        $review = $reviews->find('revId', 1);
        $book = $review->getReferenceSoft($books, 'bookId', 'bookId');
        $this->assertNull($book);
        
        // non-existing attribute (custom key attribute name)
        $this->assertNull($review->getReferenceSoft($books, 'foo', 'bookId'));
    }
}
