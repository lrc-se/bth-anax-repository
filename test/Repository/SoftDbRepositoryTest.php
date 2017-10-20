<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase2.php';

/**
 * Test cases for class SoftDbRepository.
 */
class SoftDbRepositoryTest extends DbTestCase2
{
    private function getRepository()
    {
        // custom attribute names
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
        
        // all
        $this->assertEquals(
            $this->getConnection()->getRowCount('book', 'deleted IS NULL'),
            $books->countSoft()
        );
        
        // with condition (no bound value)
        $this->assertEquals(
            $this->getConnection()->getRowCount('book', 'published IS NOT NULL AND deleted IS NULL'),
            $books->countSoft('published IS NOT NULL')
        );
        
        // with condition (bound value)
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
        
        // non-deleted (explicit primary key)
        $book = $books->findSoft('bookId', 4);
        $this->assertInstanceOf(Book2::class, $book);
        $this->assertEquals(4, $book->bookId);
        $this->assertEquals('The Klingon Dictionary', $book->title);
        $this->assertEquals('Marc Okrand', $book->author);
        $this->assertEquals(1992, $book->published);
        
        // non-deleted (automatic primary key)
        $book2 = $books->findSoft(null, 4);
        $this->assertEquals($book, $book2);
        
        // deleted
        $book = $books->findSoft('bookId', 1);
        $this->assertFalse($book);
    }
    
    
    /**
     * Test getFirstSoft method.
     */
    public function testGetFirstSoft()
    {
        $books = $this->getRepository();
        
        // non-deleted (unqualified)
        $book = $books->getFirstSoft();
        $this->assertInstanceOf(Book2::class, $book);
        $this->assertEquals(2, $book->bookId);
        $this->assertEquals('The Two Towers', $book->title);
        $this->assertEquals('J.R.R. Tolkien', $book->author);
        $this->assertEquals(1954, $book->published);
        
        // non-deleted (with condition)
        $book = $books->getFirstSoft('published < ?', [1955]);
        $this->assertInstanceOf(Book2::class, $book);
        $this->assertEquals(2, $book->bookId);
        $this->assertEquals('The Two Towers', $book->title);
        $this->assertEquals('J.R.R. Tolkien', $book->author);
        $this->assertEquals(1954, $book->published);
        
        // non-deleted (with ordering)
        $book = $books->getFirstSoft(null, [], ['order' => 'bookId DESC']);
        $this->assertInstanceOf(Book2::class, $book);
        $this->assertEquals(4, $book->bookId);
        $this->assertEquals('The Klingon Dictionary', $book->title);
        $this->assertEquals('Marc Okrand', $book->author);
        $this->assertEquals(1992, $book->published);
        
        // deleted
        $book = $books->getFirstSoft('author = ?', ['Sun Tzu']);
        $this->assertFalse($book);
    }
    
    
    /**
     * Test getAllSoft method.
     */
    public function testGetAllSoft()
    {
        $books = $this->getRepository();
        
        // unqualified
        $allBooks = $books->getAllSoft();
        $this->assertEquals($this->getConnection()->getRowCount('book', 'deleted IS NULL'), count($allBooks));
        $table = $this->getConnection()->createQueryTable('book', 'SELECT * FROM book WHERE deleted IS NULL');
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book2::class, $book);
            $this->assertEquals($table->getRow($idx++), get_object_vars($book));
        }
        
        // with condition
        $allBooks = $books->getAllSoft("title LIKE 'The %'");
        $table = $this->getConnection()->createQueryTable('book-test', "SELECT * FROM book WHERE title LIKE 'The %' AND deleted IS NULL");
        $this->assertEquals($table->getRowCount(), count($allBooks));
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book2::class, $book);
            $this->assertEquals($table->getRow($idx++), get_object_vars($book));
        }
        
        // with ordering
        $allBooks = $books->getAllSoft(null, [], ['order' => 'title']);
        $table = $this->getConnection()->createQueryTable('book-test', 'SELECT * FROM book WHERE deleted IS NULL ORDER BY title');
        $this->assertEquals($table->getRowCount(), count($allBooks));
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book2::class, $book);
            $this->assertEquals($table->getRow($idx++), get_object_vars($book));
        }
    }
    
    
    /**
     * Test deleteSoft method.
     */
    public function testDeleteSoft()
    {
        $books = $this->getRepository();
        $num = $this->getConnection()->getRowCount('book');
        $num2 = $this->getConnection()->getRowCount('book', 'deleted IS NULL');
        $book = $books->findSoft('bookId', 3);
        $books->deleteSoft($book);
        $this->assertNotNull($book->deleted);
        $this->assertEquals($this->getConnection()->getRowCount('book'), $num);
        $this->assertEquals($this->getConnection()->getRowCount('book', 'deleted IS NULL'), $num2 - 1);
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
        $num2 = $this->getConnection()->getRowCount('book', 'deleted IS NULL');
        $book = $books->find('bookId', 5);
        $books->restoreSoft($book);
        $this->assertNull($book->deleted);
        $this->assertEquals($this->getConnection()->getRowCount('book'), $num);
        $this->assertEquals($this->getConnection()->getRowCount('book', 'deleted IS NULL'), $num2 + 1);
        $newBook = $books->findSoft('bookId', $book->bookId);
        $this->assertEquals($book, $newBook);
    }
}
