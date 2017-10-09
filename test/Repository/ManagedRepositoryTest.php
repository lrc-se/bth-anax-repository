<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase.php';

/**
 * Test cases for managed references.
 *
 * @SuppressWarnings(PHPMD.UnusedLocalVariables)
 */
class ManagedRepositoryTest extends DbTestCase
{
    private $manager;
    
    
    private function getRepositories()
    {
        $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
        $this->manager = new RepositoryManager();
        
        // managed creation (default config)
        $books = $this->manager->createRepository(Book::class, ['db' => $db, 'type' => 'db', 'table' => 'book']);
        
        // manual creation (default config)
        $reviews = new DbRepository($db, 'review', Review3::class);
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
        return new YamlDataSet(ANAX_APP_PATH . '/test.yml');
    }
    
    
    /**
     * Test repository manager.
     */
    public function testRepositoryManager()
    {
        list($books, $reviews) = $this->getRepositories();
        
        // repository creation
        $this->assertInstanceOf(DbRepository::class, $books);
        $this->assertInstanceOf(DbRepository::class, $reviews);
        
        // repository retrieval (existing)
        $books2 = $this->manager->getByClass(Book::class);
        $this->assertEquals($books, $books2);
        
        // repository retrieval (non-existing)
        $reviews2 = $this->manager->getByClass(Review::class);
        $this->assertNull($reviews2);
        
        // repository creation (duplicate)
        $this->expectException(RepositoryException::class);
        $this->manager->addRepository($books2);
    }
    
    
    /**
     * Test repository manager implementation limits.
     */
    public function testRepositoryManagerImplementation()
    {
        $this->getRepositories();
        $this->expectException(RepositoryException::class);
        $this->manager->createRepository(Book::class, ['type' => 'unimplemented']);
    }
    
    
    /**
     * Test reference resolution.
     */
    public function testReferenceResolution()
    {
        try {
            list($books, $reviews) = $this->getRepositories();
            
            // existing references (explicit method call)
            $allReviews = $reviews->getAll();
            $review = $allReviews[0];
            $book = $review->getReference('book');
            $this->assertInstanceOf(Book::class, $book);
            $this->assertEquals($books->find('id', $book->id), $book);
            
            // existing reference (magic property)
            $this->assertEquals($book, $review->book);
            
            // non-existing reference (explicit method call)
            $this->assertNull($review->getReference('foo'));
            
            // non-existing reference (magic property)
            $this->expectException(RepositoryException::class);
            $book = $review->foo;
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
        $reviews = new DbRepository($db, 'review', Review3::class);
        $review = $reviews->find('id', 1);
        $this->expectException(RepositoryException::class);
        $dummy = $review->getReference('book');
    }
    
    
    /**
     * Test unavailable reference resolution #2.
     */
    public function testUnavailableReferenceResolution2()
    {
        try {
            $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
            $this->manager = new RepositoryManager();
            $reviews = $this->manager->createRepository(Review3::class, ['db' => $db, 'type' => 'db', 'table' => 'review']);
            $review = $reviews->find('id', 1);
            $this->expectException(RepositoryException::class);
            $dummy = $review->getReference('book');
        } finally {
            $reviews->setManager(null);
            $review->setManager(null);
        }
    }
}
