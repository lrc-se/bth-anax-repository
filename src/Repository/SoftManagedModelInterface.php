<?php

namespace LRC\Repository;

/**
 * Interface for managed soft-deletion-aware repository models.
 */
interface SoftManagedModelInterface extends ManagedModelInterface
{
    /**
     * Retrieve a reference by name, ignoring soft-deleted entries.
     *
     * @param string    $name       Reference name.
     *
     * @return mixed                Model instance if found, null otherwise.
     *
     * @throws RepositoryException  If this model or the referenced model is not handled by a managed repository, or the referenced repository is not soft-deletion aware.
     */
    public function getReferenceSoft($name);
}
