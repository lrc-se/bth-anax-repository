<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase.php';

/**
 * Test cases for class DbRepository.
 */
class DbRepositoryTest extends DbTestCase
{
    public function getRepository()
    {
        $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
        return new DbRepository($db, 'book', Book::class);
    }
    
    
    public function getDataSet()
    {
        return new YamlDataSet(ANAX_APP_PATH . '/test.yml');
    }
    
    
    /**
     * Test count method.
     */
    public function testCount()
    {
        $books = $this->getRepository();
        $this->assertEquals(
            $this->getConnection()->getRowCount('book'),
            $books->count()
        );
        $this->assertEquals(
            $this->getConnection()->getRowCount('book', 'published IS NOT NULL'),
            $books->count('published IS NOT NULL')
        );
        $this->assertEquals(
            $this->getConnection()->getRowCount('book', "author = 'J.R.R. Tolkien'"),
            $books->count('author = ?', ['J.R.R. Tolkien'])
        );
    }
    
    
    /**
     * Test find method.
     */
    public function testFind()
    {
        $books = $this->getRepository();
        $book = $books->find('id', 5);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals($book->id, 5);
        $this->assertEquals($book->title, 'The Art of War');
        $this->assertEquals($book->author, 'Sun Tzu');
        $this->assertNull($book->published);
        
        $book = $books->find('id', 0);
        $this->assertFalse($book);
    }
    
    
    /**
     * Test getFirst method.
     */
    public function testGetFirst()
    {
        $books = $this->getRepository();
        $book = $books->getFirst();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals($book->id, 1);
        $this->assertEquals($book->title, 'The Fellowship of the Ring');
        $this->assertEquals($book->author, 'J.R.R. Tolkien');
        $this->assertEquals($book->published, 1954);
        
        $book = $books->getFirst('published > ?', [1954]);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals($book->id, 3);
        $this->assertEquals($book->title, 'The Return of the King');
        $this->assertEquals($book->author, 'J.R.R. Tolkien');
        $this->assertEquals($book->published, 1955);
        
        $book = $books->getFirst('title IS NULL');
        $this->assertFalse($book);
    }
    
    
    /**
     * Test getAll method.
     */
    public function testGetAll()
    {
        $books = $this->getRepository();
        $allBooks = $books->getAll();
        $this->assertEquals(count($allBooks), $this->getConnection()->getRowCount('book'));
        $table = $this->getConnection()->createDataset()->getTable('book');
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book::class, $book);
            $this->assertEquals(get_object_vars($book), $table->getRow($idx++));
        }
        
        $allBooks = $books->getAll("title LIKE 'The %'");
        $table = $this->getConnection()->createQueryTable('book-test', "SELECT * FROM book WHERE title LIKE 'The %'");
        $this->assertEquals(count($allBooks), $table->getRowCount());
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book::class, $book);
            $this->assertEquals(get_object_vars($book), $table->getRow($idx++));
        }
    }
    
    
    /**
     * Test save method.
     */
    public function testSave()
    {
        // insert
        $books = $this->getRepository();
        $num = $books->count();
        $book = new Book();
        $book->title = 'The Bible';
        $book->author = 'God';
        $book->published = 100;
        $books->save($book);
        $this->assertEquals($this->getConnection()->getRowCount('book'), $num + 1);
        $newBook = $books->find('id', $book->id);
        $this->assertEquals($book, $newBook);
        
        // update
        $newBook->title = 'The Babble';
        $newBook->author = 'Dog';
        $newBook->published = null;
        $books->save($newBook);
        $this->assertEquals($this->getConnection()->getRowCount('book'), $num + 1);
        $this->assertEquals($book->id, $newBook->id);
        $newestBook = $books->find('id', $newBook->id);
        $this->assertEquals($newBook, $newestBook);
    }
    
    
    /**
     * Test delete method.
     */
    public function testDelete()
    {
        $books = $this->getRepository();
        $num = $books->count();
        $book = $books->find('id', 3);
        $books->delete($book);
        $this->assertEquals($this->getConnection()->getRowCount('book'), $num - 1);
        $oldBook = $books->find('id', $book->id);
        $this->assertFalse($oldBook);
    }
}
