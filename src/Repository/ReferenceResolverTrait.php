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
     * @param RepositoryInterface   $repository Repository to query.
     * @param string                $attr       Name of foreign key attribute.
     * @param string                $key        Name of primary key attribute in referenced table.
     *
     * @return mixed                            Model instance if found, null otherwise.
     */
    public function getReference($repository, $attr, $key = 'id')
    {
        if (isset($this->$attr)) {
            return ($repository->find($key, $this->$attr) ?: null);
        }
        return null;
    }
}
