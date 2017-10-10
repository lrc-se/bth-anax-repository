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
     * @var array|boolean   Array of references to include in fetch operations (set to true to fetch all or false to fetch none).
     */
    protected $fetchRefs = false;
    
    
    /**
     * @var boolean     Whether to take soft-deletion into account when fetching references.
     */
    protected $softRefs = false;
    
    
    /**
     * Register a manager for the repository.
     *
     * @param RepositoryManager $manager    Repository manager.
     *
     * @return self
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
        return $this;
    }
    
    
    /**
     * Change reference fetching behavior.
     *
     * @param array|boolean $refs   Array of references to include in fetch operations (pass true to fetch all or false to fetch none).
     * @param boolean       $soft   Whether to take soft-deletion into account when fetching references.
     *
     * @return self
     */
    public function fetchReferences($refs = true, $soft = false)
    {
        $this->fetchRefs = $refs;
        $this->softRefs = $soft;
        return $this;
    }
}
