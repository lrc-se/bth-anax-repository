<?php

namespace LRC\Repository;

/**
 * Review model class with soft-deletion-aware reference resolver.
 */
class Review2
{
    use SoftReferenceResolverTrait;
    
    
    public $revId;
    public $bookId;
    public $score;
    public $text;
}
