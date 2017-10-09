<?php

namespace LRC\Repository;

/**
 * Interface for managed repository models.
 */
interface ManagedModelInterface
{
    /**
     * Inject a reference to repository manager.
     *
     * @param RepositoryManager $manager    Repository manager.
     *
     * @return self
     */
    public function setManager($manager);
    
    
    /**
     * Register foreign model references.
     *
     * @param array $references     Array of references (name => config).
     *
     * @return self
     */
    public function setReferences($references);
    
    
    /**
     * Retrieve a reference by name.
     *
     * @param string    $name       Reference name.
     *
     * @return mixed                Model instance if found, null otherwise.
     *
     * @throws RepositoryException  If this model or the referenced model is not handled by a managed repository.
     */
    public function getReference($name);
}
