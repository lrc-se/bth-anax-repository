<?php

namespace LRC\Repository;

/**
 * Review model class with managed soft-deletion-aware references.
 */
class Review4 implements SoftManagedModelInterface
{
    use SoftManagedModelTrait;
    
    
    public $revId;
    public $bookId;
    public $score;
    public $text;
    
    
    public function __construct()
    {
        $this->setReferences([
            'book' => [
                'attribute' => 'bookId',
                'model' => Book2::class,
                'key' => 'bookId'
            ]
        ]);
    }
}
