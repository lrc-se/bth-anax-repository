<?php

namespace LRC\Repository;

/**
 * Question model class with managed references.
 */
class Question implements ManagedModelInterface
{
    use ManagedModelTrait;
    
    
    public $id;
    public $userId;
    public $title;
    public $text;
    public $published;
    public $deleted;
    
    
    public function __construct()
    {
        $this->setReferences([
            'user' => [
                'attribute' => 'userId',
                'model' => User::class,
                'magic' => true
            ]
        ]);
    }
}
