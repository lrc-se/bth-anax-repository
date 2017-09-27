<?php

namespace LRC\Repository;

/**
 * Foreign-key reference resolution.
 */
trait ReferenceResolverTrait
{
    /**
     * Retrieve a reference by foreign key.
     *
     * @param string                $attr       Name of foreign key attribute.
     * @param RepositoryInterface   $repository Repository to query.
     *
     * @return mixed                            Model instance if found, null otherwise.
     */
    public function getReference($attr, $repository)
    {
        if (isset($this->$attr)) {
            return ($repository->find('id', $this->$attr) ?: null);
        }
        return null;
    }
}