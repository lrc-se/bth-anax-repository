<?php

namespace LRC\Repository;

/**
 * Trait for managed repositories.
 */
trait ManagedRepositoryTrait
{
    /**
     * @var RepositoryManager   Repository manager.
     */
    protected $manager;
    
    
    /**
     * Register a manager for the repository.
     *
     * @param RepositoryManager $manager    Repository manager.
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }
}
