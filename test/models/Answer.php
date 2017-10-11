<?php

namespace LRC\Repository;

/**
 * Answer model class with managed soft-deletion-aware references.
 */
class Answer implements SoftManagedModelInterface
{
    use SoftManagedModelTrait;
    
    
    public $id;
    public $questionId;
    public $userId;
    public $text;
    public $published;
    public $deleted;
    
    
    public function __construct()
    {
        $this->setReferences([
            'question' => [
                'attribute' => 'questionId',
                'model' => Question::class
            ],
            'user' => [
                'attribute' => 'userId',
                'model' => User::class
            ]
        ]);
    }
}
