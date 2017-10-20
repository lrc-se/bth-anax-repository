<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase.php';

/**
 * Test cases for class DbRepository.
 */
class DbRepositoryTest extends DbTestCase
{
    private function getRepository()
    {
        // default key attribute name
        $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
        return new DbRepository($db, 'book', Book::class);
    }
    
    
    public function getDataSet()
    {
        return new YamlDataSet(ANAX_APP_PATH . '/test.yml');
    }
    
    
    /**
     * Test getters.
     */
    public function testGetters()
    {
        $books = $this->getRepository();
        $this->assertEquals(Book::class, $books->getModelClass());
        $this->assertEquals('book', $books->getCollectionName());
    }
    
    
    /**
     * Test count method.
     */
    public function testCount()
    {
        $books = $this->getRepository();
        
        // all
        $this->assertEquals(
            $this->getConnection()->getRowCount('book'),
            $books->count()
        );
        
        // with condition (no bound value)
        $this->assertEquals(
            $this->getConnection()->getRowCount('book', 'published IS NOT NULL'),
            $books->count('published IS NOT NULL')
        );
        
        // with condition (bound value)
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
        
        // existing
        $book = $books->find('id', 5);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(5, $book->id);
        $this->assertEquals('The Art of War', $book->title);
        $this->assertEquals('Sun Tzu', $book->author);
        $this->assertNull($book->published);
        
        // automatic primary key
        $book2 = $books->find(null, 5);
        $this->assertEquals($book, $book2);
        
        // non-existing
        $book = $books->find('id', 0);
        $this->assertFalse($book);
    }
    
    
    /**
     * Test getFirst method.
     */
    public function testGetFirst()
    {
        $books = $this->getRepository();
        
        // existing (unqualified)
        $book = $books->getFirst();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(1, $book->id);
        $this->assertEquals('The Fellowship of the Ring', $book->title);
        $this->assertEquals('J.R.R. Tolkien', $book->author);
        $this->assertEquals(1954, $book->published);
        
        // existing (with condition)
        $book = $books->getFirst('published > ?', [1954]);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(3, $book->id);
        $this->assertEquals('The Return of the King', $book->title);
        $this->assertEquals('J.R.R. Tolkien', $book->author);
        $this->assertEquals(1955, $book->published);
        
        // existing (with ordering)
        $book = $books->getFirst(null, [], ['order' => 'published DESC']);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(4, $book->id);
        $this->assertEquals('The Klingon Dictionary', $book->title);
        $this->assertEquals('Marc Okrand', $book->author);
        $this->assertEquals(1992, $book->published);
        
        // non-existing
        $book = $books->getFirst('title IS NULL');
        $this->assertFalse($book);
    }
    
    
    /**
     * Test getAll method.
     */
    public function testGetAll()
    {
        $books = $this->getRepository();
        
        // unqualified
        $allBooks = $books->getAll();
        $this->assertEquals($this->getConnection()->getRowCount('book'), count($allBooks));
        $table = $this->getConnection()->createDataset()->getTable('book');
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book::class, $book);
            $this->assertEquals($table->getRow($idx++), get_object_vars($book));
        }
        
        // with condition
        $allBooks = $books->getAll("title LIKE 'The %'");
        $table = $this->getConnection()->createQueryTable('book-test', "SELECT * FROM book WHERE title LIKE 'The %'");
        $this->assertEquals($table->getRowCount(), count($allBooks));
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book::class, $book);
            $this->assertEquals($table->getRow($idx++), get_object_vars($book));
        }
        
        // with ordering
        $allBooks = $books->getAll(null, [], ['order' => 'title']);
        $table = $this->getConnection()->createQueryTable('book-test', 'SELECT * FROM book ORDER BY title');
        $this->assertEquals($table->getRowCount(), count($allBooks));
        $idx = 0;
        foreach ($allBooks as $book) {
            $this->assertInstanceOf(Book::class, $book);
            $this->assertEquals($table->getRow($idx++), get_object_vars($book));
        }
    }
    
    
    /**
     * Test save method.
     */
    public function testSave()
    {
        $books = $this->getRepository();
        
        // insert
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
