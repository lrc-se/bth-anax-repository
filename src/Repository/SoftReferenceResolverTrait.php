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
     * @param string                    $attr       Name of foreign key attribute.
     * @param SoftRepositoryInterface   $repository Repository to query.
     *
     * @return mixed                                Model instance if found, null otherwise.
     */
    public function getReferenceSoft($attr, $repository)
    {
        if (isset($this->$attr)) {
            return ($repository->findSoft('id', $this->$attr) ?: null);
        }
        return null;
    }
}