<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase2.php';

/**
 * Test cases for managed soft references.
 *
 * @SuppressWarnings(PHPMD.UnusedLocalVariables)
 */
class SoftManagedRepositoryTest extends DbTestCase2
{
    private $manager;
    
    
    private function getRepositories()
    {
        $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db2.php');
        $this->manager = new RepositoryManager();
        
        // managed creation (manual config)
        $books = $this->manager->createRepository(Book2::class, ['db' => $db, 'table' => 'book', 'key' => 'bookId', 'deleted' => 'deleted']);
        
        // manual creation (manual config)
        $reviews = new DbRepository($db, 'review', Review4::class, 'revId');
        $this->manager->addRepository($reviews);
        
        return [$books, $reviews];
    }
    
    
    public function tearDown()
    {
        unset($this->manager);
        parent::tearDown();
    }
    
    
    public function getDataSet()
    {
        return new YamlDataSet(ANAX_APP_PATH . '/test2.yml');
    }
    
    
    /**
     * Test repository manager.
     */
    public function testRepositoryManager()
    {
        list($books, $reviews) = $this->getRepositories();
        
        // repository creation
        $this->assertInstanceOf(SoftDbRepository::class, $books);
        $this->assertInstanceOf(DbRepository::class, $reviews);
        
        // repository retrieval (existing)
        $books2 = $this->manager->getByClass(Book2::class);
        $this->assertEquals($books, $books2);
        
        // repository retrieval (non-existing)
        $reviews2 = $this->manager->getByClass(Review::class);
        $this->assertNull($reviews2);
        
        // repository creation (duplicate)
        $this->expectException(RepositoryException::class);
        $this->manager->addRepository($reviews);
    }
    
    
    /**
     * Test soft-deletion-aware reference resolution.
     */
    public function testSoftReferenceResolution()
    {
        try {
            list($books, $reviews) = $this->getRepositories();
            
            // non-deleted reference
            $review = $reviews->find('revId', 2);
            $book = $review->getReferenceSoft('book');
            $this->assertInstanceOf(Book2::class, $book);
            $this->assertEquals($books->find('bookId', $book->bookId), $book);
            
            // soft-deleted reference
            $review = $reviews->find('revId', 1);
            $this->assertNull($review->getReferenceSoft('book'));
            
            // non-existing reference
            $this->assertNull($review->getReferenceSoft('foo'));
            
            // non-configured magic reference
            $this->expectException(RepositoryException::class);
            $book = $review->book;
        } finally {
            // clean up references to release database lock
            $reviews->setManager(null);
            $books->setManager(null);
            $review->setManager(null);
        }
    }
    
    
    /**
     * Test unavailable reference resolution #1.
     */
    public function testUnavailableReferenceResolution1()
    {
        $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
        $reviews = new DbRepository($db, 'review', Review4::class);
        $review = $reviews->find('id', 2);
        $this->expectException(RepositoryException::class);
        $dummy = $review->getReferenceSoft('book');
    }
    
    
    /**
     * Test unavailable reference resolution #2.
     */
    public function testUnavailableReferenceResolution2()
    {
        try {
            $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
            $this->manager = new RepositoryManager();
            $reviews = $this->manager->createRepository(Review4::class, ['db' => $db, 'type' => 'db', 'table' => 'review']);
            $review = $reviews->find('id', 2);
            $this->expectException(RepositoryException::class);
            $dummy = $review->getReferenceSoft('book');
        } finally {
            $reviews->setManager(null);
            $review->setManager(null);
        }
    }
}
