<?php

namespace LRC\Repository;

/**
 * Soft-deletion-aware foreign-key reference resolution.
 */
trait SoftReferenceResolverTrait
{
    use ReferenceResolverTrait;
    
    
    /**
     * Retrieve a reference by foreign key, ignoring soft-deleted entries.
     *
     * @param SoftRepositoryInterface   $repository Repository to query.
     * @param string                    $attr       Name of foreign key attribute.
     * @param string                    $key        Name of primary key attribute in referenced table.
     *
     * @return mixed                                Model instance if found, null otherwise.
     */
    public function getReferenceSoft($repository, $attr, $key = 'id')
    {
        if (isset($this->$attr)) {
            return ($repository->findSoft($key, $this->$attr) ?: null);
        }
        return null;
    }
}
