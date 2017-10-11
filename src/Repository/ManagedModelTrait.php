<?php

namespace LRC\Repository;

/**
 * Foreign-key reference resolution for repository models using repository manager.
 */
trait ManagedModelTrait
{
    /**
     * @var RepositoryManager   Repository manager.
     */
    protected $_manager;
    
    /**
     * @var array   Registered references.
     */
    protected $_references;
    
    
    /**
     * Inject a reference to repository manager.
     *
     * @param RepositoryManager $manager    Repository manager.
     *
     * @return self
     */
    public function setManager($manager)
    {
        $this->_manager = $manager;
        return $this;
    }
    
    
    /**
     * Return registered foreign model references.
     *
     * @return array    Array of references.
     */
    public function getReferences()
    {
        return ($this->_references ?: []);
    }
    
    
    /**
     * Register foreign model references.
     *
     * @param array $references     Array of references (name => config).
     *
     * @return self
     */
    public function setReferences($references)
    {
        $this->_references = [];
        foreach ($references as $name => $ref) {
            if (!array_key_exists('key', $ref)) {
                $ref['key'] = 'id';
            }
            $this->_references[$name] = $ref;
        }
        return $this;
    }
    
    
    /**
     * Retrieve a reference by name.
     *
     * @param string    $name       Reference name.
     *
     * @return mixed                Model instance if found, null otherwise.
     *
     * @throws RepositoryException  If this model or the referenced model is not handled by a managed repository.
     */
    public function getReference($name)
    {
        if (!isset($this->_manager)) {
            throw new RepositoryException('Model is not handled by a managed repository');
        }
        if (array_key_exists($name, $this->_references)) {
            $ref = $this->_references[$name];
            $repo = $this->_manager->getByClass($ref['model']);
            if (!$repo) {
                throw new RepositoryException('Referenced model is not handled by a managed repository');
            }
            return ($repo->find($ref['key'], $this->{$ref['attribute']}) ?: null);
        }
        return null;
    }
    
    
    /**
     * Retrieve a reference by direct attribute access.
     *
     * @param string            $attr   Attribute name.
     *
     * @throws RepositoryException      If the requested attribute does not resolve to a registered magic reference.
     */
    public function __get($attr)
    {
        foreach ($this->_references as $name => $ref) {
            if (!empty($ref['magic']) && $attr === $name) {
                return $this->getReference($name);
            }
        }
        throw new RepositoryException("The attribute '$attr' does not resolve to a registered magic reference");
    }
}
