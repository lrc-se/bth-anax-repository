<?php

namespace LRC\Repository;

/**
 * Review model class with managed references.
 */
class Review3 implements ManagedModelInterface
{
    use ManagedModelTrait;
    
    
    public $id;
    public $bookId;
    public $score;
    public $text;
    
    
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
}
