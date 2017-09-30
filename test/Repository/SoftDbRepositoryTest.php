<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase2.php';

/**
 * Test cases for class SoftDbRepository.
 */
class SoftDbRepositoryTest extends DbTestCase2
{
    public function getRepository()
    {
        $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db2.php');
        return new SoftDbRepository($db, 'book', Book2::class, 'deleted', 'bookId');
    }
    
    
    public function getDataSet()
    {
        return new YamlDataSet(ANAX_APP_PATH . '/test2.yml');
    }
    
    
    /**
     * Test countSoft method.
     */
    public function testCountSoft()
    {
        $books = $this->getRepository();
        $this->assertEquals(
            $this->getConnection()->getRowCount('book', 'deleted IS NULL'),
            $books->countSoft()
        );
        $this->assertEquals(
            $this->getConnection()->getRowCount('book', 'published IS NOT NULL AND deleted IS NULL'),
            $books->countSoft('published IS NOT NULL')
        );
        $this->assertEquals(
            $this->getConnection()->getRowCount('book', "author = 'J.R.R. Tolkien' AND deleted IS NULL"),
            $books->countSoft('author = ?', ['J.R.R. Tolkien'])
        );
    }
    
    
    /**
     * Test findSoft method.
     */
    public function testFindSoft()
    {
        $books = $this->getRepository();
        $book = $books->findSoft('bookId', 4);
        $this->assertInstanceOf(Book2::class, $book);
        $this->assertEquals($book->bookId, 4);
        $this->assertEquals($book->title, 'The Klingon Dictionary');
        $this->assertEquals($book->author, 'Marc Okrand');
        $this->assertEquals($book->published, 1992);
        
        $book = $books->findSoft('bookId', 1);
        $this->assertFalse($book);
    }
    
    
    /**
     * Test getFirstSoft method.
     */
    public function testGetFirstSoft()
    {
        $books = $this->getRepository();
        $book = $books->getFirstSoft();
        $this->assertInstanceOf(Book2::class, $book);
        $this->assertEquals($book->bookId, 2);
        $this->assertEquals($book->title, 'The Two Towers');
        $this->assertEquals($book->author, 'J.R.R. Tolkien');
        $this->assertEquals($book->published, 1954);
        
        $book = $books->getFirstSoft('published < ?', [1955]);
        $this->assertInstanceOf(Book2::class, $book);
        $this->assertEquals($book->bookId, 2);
        $this->assertEquals($book->title, 'The Two Towers');
        $this->assertEquals($book->author, 'J.R.R. Tolkien');
        $this->assertEquals($book->published, 1954);
        
        $book = $books->getFirstSoft('author = ?', ['Sun Tzu']);
        $this->assertFalse($book);
    }
    
    
    /**
     * Test getAllSoft method.
     */
    public function testGetAll()
    {
        $books = $this->getRepository();
        $allBooks = $books->getAllSoft();
        $this->assertEquals(count($allBooks), $this->getConnection()->getRowCount('book', 'deleted IS NULL'));
        $table = $this->getConnection()->createQueryTable('book', 'SELECT * FROM book WHERE deleted IS NULL');
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book2::class, $book);
            $this->assertEquals(get_object_vars($book), $table->getRow($idx++));
        }
        
        $allBooks = $books->getAllSoft("title LIKE 'The %'");
        $table = $this->getConnection()->createQueryTable('book-test', "SELECT * FROM book WHERE title LIKE 'The %' AND deleted IS NULL");
        $this->assertEquals(count($allBooks), $table->getRowCount());
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book2::class, $book);
            $this->assertEquals(get_object_vars($book), $table->getRow($idx++));
        }
    }
    
    
    /**
     * Test deleteSoft method.
     */
    public function testDeleteSoft()
    {
        $books = $this->getRepository();
        $num = $this->getConnection()->getRowCount('book');
        $book = $books->findSoft('bookId', 3);
        $books->deleteSoft($book);
        $this->assertEquals($this->getConnection()->getRowCount('book'), $num);
        $oldBook = $books->findSoft('bookId', $book->bookId);
        $this->assertFalse($oldBook);
    }


    /**
     * Test restoreSoft method.
     */
    public function testRestoreSoft()
    {
        $books = $this->getRepository();
        $num = $this->getConnection()->getRowCount('book');
        $book = $books->findSoft('bookId', 3);
        $books->deleteSoft($book);
        $this->assertNotNull($book->deleted);
        
        $books->restoreSoft($book);
        $this->assertEquals($this->getConnection()->getRowCount('book'), $num);
        $this->assertNull($book->deleted);
    }
}
