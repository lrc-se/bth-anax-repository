Anax Repository
===============

Repository module for the modular [Anax framework](https://github.com/canax), 
providing model-based data access through a consistent interface. 
As of now the included implementation works with relational databases only.

This module is intended as a replacement for the existing `ActiveRecordModel` 
in [*anax/database*](https://github.com/canax/database), 
offering a better semantic fit for the underlying data source while also being easier to manage and test, 
especially from a dependency injection standpoint.

The module also offers automatic soft-deletion capabilities, 
available on demand by way of derived classes.


Requirements
------------

- PHP 5.6+
- anax/database 1.1.0+


Usage
-----

To use this module in an Anax installation, install it with `composer require lrc-se/anax-repository`. 
The module as such requires no configuration, but expects an instance of a properly configured 
`DatabaseQueryBuilder` from *anax/database* to perform the actual database operations.


### DbRepository

Implementation of `RepositoryInterface`. Construction parameters:

    $db,            // Anax database service (query builder)
    $table,         // Name of the database table the repository represents
    $modelClass,    // Name of the model class representing each repository entry
    $key            // Name of the primary key column (optional, defaults to 'id')

__Examples:__

```php
// setup
$db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
$books = new \LRC\Repository\DbRepository($db, 'book', Book::class);

// count entries
echo $books->count();

// retrieve entries
$allBooks = $books->getAll();
$firstBook = $books->getFirst();
$book = $books->find('id', 2);

// update entry
$book->title .= ' (2nd revision)';
$book->published = date('Y');
$books->save($book);

// delete entry
$books->delete($book);
$book2 = $books->find('id', 2);
var_dump($book->id);    // null
var_dump($book2);       // false (standard "no result" return value from PDO)

// re-insert entry
$books->save($book);
var_dump($book->id);    // higher than 2 since the entry was re-inserted, not updated
```

Refer to `RepositoryInterface` for a full description of the main API.


### SoftDbRepository

Implementation of `SoftRepositoryInterface`, adding soft-deletion awareness. Construction parameters:

    $db,            // Anax database service (query builder)
    $table,         // Name of the database table the repository represents
    $modelClass,    // Name of the model class representing each repository entry
    $deleted,       // Name of the attribute used to flag deletion (optional, defaults to 'deleted')
    $key            // Name of the primary key column (optional, defaults to 'id')

__Examples:__

```php
// setup
$db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
$books = new \LRC\Repository\SoftDbRepository($db, 'book', Book::class);

// count non-deleted entries
echo $books->countSoft();

// retrieve non-deleted entries
$allBooks = $books->getAllSoft();
$firstBook = $books->getFirstSoft();
$book = $books->findSoft('id', 3);

// soft-delete entry
$books->deleteSoft($book);
$book2 = $books->findSoft('id', 3);
var_dump($book->id);        // still 3
var_dump($book->deleted);   // timestamp
var_dump($book2);           // false (standard "no result" return value from PDO)

// restore soft-deleted entry
$books->restoreSoft($book);
var_dump($book->deleted);   // null
```

Refer to `SoftRepositoryInterface` for a full description of the extended API.


### ReferenceResolverTrait

Include in model class to get access to automatic foreign key reference resolution. 
Method invocation:

    $model->getReference(
        $repository,        // Repository to query
        $attr,              // Name of the foreign key attribute
        $key                // Name of the referenced attribute (optional, defaults to 'id')
    );

This method returns `null` if no matching referenced entry can be found.

__Example:__

```php
// model class
class Review
{
    use \LRC\Repository\ReferenceResolverTrait;
    
    /* ... */
}


// setup
$db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
$books = new \LRC\Repository\DbRepository($db, 'book', Book::class);
$reviews = new \LRC\Repository\DbRepository($db, 'review', Review::class);

// retrieve referenced entry
$review = $reviews->find('id', 1);
$book = $review->getReference($books, 'bookId');
```


### SoftReferenceResolverTrait

Include in model class to get access to automatic foreign key reference resolution, 
taking soft-deletion into account. Method invocation:

    $model->getReferenceSoft(
        $repository,        // Soft-deletion-aware repository to query
        $attr,              // Name of the foreign key attribute
        $key                // Name of the referenced attribute (optional, defaults to 'id')
    );

This method returns `null` if no matching non-deleted referenced entry can be found.

__Example:__

```php
// model class
class Review
{
    use \LRC\Repository\SoftReferenceResolverTrait;
    
    /* ... */
}


// setup
$db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
$books = new \LRC\Repository\SoftDbRepository($db, 'book', Book::class);
$reviews = new \LRC\Repository\DbRepository($db, 'review', Review::class);

// retrieve non-deleted referenced entry
$review = $reviews->find('id', 1);
$book = $review->getReferenceSoft($books, 'bookId');
```


Notes
-----

The module [*anax/common*](https://github.com/canax/common) is **not** a dependency of this module per se, 
but it **is** required by `DatabaseQueryBuilder` and is therefore included in the `require-dev` section of *composer.json* 
in order for the unit tests to work.

Additionally, *anax/database* is not actually required as such, only a corresponding implementation of its `DatabaseQueryBuilder` 
class providing the same public API, but it has been included as a dependency for simplicity's sake.


About
-----

**Type:** School project @[BTH](https://www.bth.se/)

**License:** MIT

**Author:** [LRC](mailto:kabc16@student.bth.se)
