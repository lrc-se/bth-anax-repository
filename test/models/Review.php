<?php

namespace LRC\Repository;

/**
 * Review model class with reference resolver.
 */
class Review
{
    use ReferenceResolverTrait;
    
    
    public $id;
    public $bookId;
    public $score;
    public $text;
}
