Anax Repository
===============

[![Latest Stable Version](https://poser.pugx.org/lrc-se/anax-repository/v/stable)](https://packagist.org/packages/lrc-se/anax-repository)
[![Travis CI Build Status](https://travis-ci.org/lrc-se/bth-anax-repository.svg?branch=master)](https://travis-ci.org/lrc-se/bth-anax-repository)
[![Scrutinizer Build Status](https://scrutinizer-ci.com/g/lrc-se/bth-anax-repository/badges/build.png?b=master)](https://scrutinizer-ci.com/g/lrc-se/bth-anax-repository/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lrc-se/bth-anax-repository/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lrc-se/bth-anax-repository/?branch=master)
[![Scrutinizer Code Coverage](https://scrutinizer-ci.com/g/lrc-se/bth-anax-repository/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lrc-se/bth-anax-repository/?branch=master)

Repository module for the modular [Anax framework](https://github.com/canax), 
providing model-based data access through a consistent interface. 
As of now the included implementation works with relational databases only.

This module is intended as a replacement for the existing `ActiveRecordModel` 
in [*anax/database*](https://github.com/canax/database), 
offering a better semantic fit for the underlying data source while also being easier to manage and test, 
especially from a dependency injection standpoint.

The module also offers automatic soft-deletion capabilities available on demand.


Requirements
------------

- PHP 5.6+
- anax/database 1.1.0+ (1.1.6+ for included tests)


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

// use criteria and query options
$filteredBook = $books->getFirst('published < 2000', [], ['order' => 'published DESC']);
$match = 'Tolkien';
$filteredBooks = $books->getAll(
    'author LIKE ?',
    ["%{$match}%"],
    [
        'order' => 'title',
        'limit' => 5,
        'offset' => 2
    ]
);
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

*__Note:__ This feature is deprecated in favor of managed repositories explained below.*

Include in model class to get access to foreign key reference resolution. 
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

*__Note:__ This feature is deprecated in favor of managed repositories explained below.*

Include in model class to get access to foreign key reference resolution, 
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


### RepositoryManager

Manages repositories in an application, allowing for fully automatic reference resolution based on model classes. 
The keys in the config array for `createRepository()` are the same as the construction parameters for the repository implementations above, 
save for `db` which is the database service and `type` which specifies which repository class to instantiate; 
currently supported values are `"db"` for `DbRepository` or `"db-soft"` for `SoftDbRepository` (the latter is the default). 
Also note that the model class remains a separate parameter.

__Example:__

```php
$db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db.php');
$manager = new \LRC\Repository\RepositoryManager();

// managed creation
$books = $manager->createRepository(Book::class, [
    'db' => $db,
    'type' => 'db-soft',
    'table' => 'book'
]);

// manual addition
$reviews = new \LRC\Repository\DbRepository($db, 'review', Review::class);
$manager->addRepository($reviews);
```


### ManagedRepository

This base class uses `ManagedRepositoryTrait` to provide fully automatic foreign-key resolution 
together with the repository manager above and the managed models below. 
Immediate retrieval goes one level deep per reference, after which on-demand fetching has to be used. 
`DbRepository` and `SoftDbRepository` inherit from this class and implement all necessary functionality 
to take advantage of the reference system laid out in the next section.


### ManagedModelTrait/SoftManagedModelTrait

Provides fully automatic foreign-key resolution together with `RepositoryManager` and `ManagedRepository` above. 
Use the following parameter format for `setReferences()` to declare foreign references:

    [
        'name' => [         // reference name
            'attribute',    // foreign key attribute
            'model',        // model class of referenced entity
            'key',          // primary key of referenced entity (defaults to 'id')
            'magic'         // whether to make the reference available as a magic model attribute
        ],
        ...
    ]

__Example:__

```php
class Review implements \LRC\Repository\SoftManagedModelInterface
{
    use \LRC\Repository\SoftManagedModelTrait;
    
    public function __construct()
    {
        $this->setReferences([
            'book' => [
                'attribute' => 'bookId',
                'model' => Book::class,
                'magic' => true
            ]
        ]);
    }
    
    /* ... */
}


/* using the managed repositories created above */

// fetch reference on demand
$review = $reviews->find(null, 1);      // default primary key
$book = $review->getReference('book');  // explicit resolution
$book2 = $review->book;                 // magic resolution
var_dump($book);                        // <Book> model instance
var_dump($book2);                       // same as $book

// fetch reference on demand, taking soft-deletion into account
// (magic resolution always ignores soft-deletion)
$book3 = $review->getReferenceSoft('book');

// fetch references together with main entity
// (always creates true public attributes, which are skipped when saving the model back)
$review = $reviews->fetchReferences()->find(null, 1);
$book4 = $review->book;
var_dump($book4);       // same as $book and $book2, stored in <Review> object

// fetch named references together with main entity,
// taking soft-deletion into account for both
$allReviews = $reviews->fetchReferences(['book'], true)->getAllSoft();
foreach ($allReviews as $review) {
    var_dump($review->book);    // <Book> model instance stored in <Review> object,
                                // or null if the reference has been soft-deleted
}
```


Notes
-----

The module [*anax/configure*](https://github.com/canax/configure) is **not** a dependency of this module per se, 
but it **is** required by `DatabaseQueryBuilder` and is therefore included in the `require-dev` section of *composer.json* 
in order for the unit tests to work. Also note that before v1.1.6 of *anax/database* this requirement was for [*anax/common*](https://github.com/canax/common) instead.

Additionally, *anax/database* is not actually required as such, only a corresponding implementation of its `DatabaseQueryBuilder` 
class providing the same public API, but it has been included as a dependency for simplicity's sake.


About
-----

**Type:** School project @[BTH](https://www.bth.se/)  
**License:** MIT  
**Author:** [LRC](mailto:kabc16@student.bth.se)
