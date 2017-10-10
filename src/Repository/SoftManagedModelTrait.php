<?php

namespace LRC\Repository;

/**
 * Soft-deletion-aware foreign-key reference resolution for repository models using repository manager.
 */
trait SoftManagedModelTrait
{
    use ManagedModelTrait;
    
    
    /**
     * Retrieve a reference by name, ignoring soft-deleted entries.
     *
     * @param string    $name       Reference name.
     *
     * @return mixed                Model instance if found, null otherwise.
     *
     * @throws RepositoryException  If this model or the referenced model is not handled by a managed repository, or the referenced repository is not soft-deletion aware.
     */
    public function getReferenceSoft($name)
    {
        if (!isset($this->_manager)) {
            throw new RepositoryException('Model is not handled by a managed repository');
        }
        if (array_key_exists($name, $this->_references)) {
            $ref = $this->_references[$name];
            $repo = $this->_manager->getByClass($ref['model']);
            if (!$repo || !($repo instanceof SoftRepositoryInterface)) {
                throw new RepositoryException('Referenced model is not handled by a managed soft-deletion-aware repository');
            }
            return ($repo->findSoft($ref['key'], $this->{$ref['attribute']}) ?: null);
        }
        return null;
    }
}
