<?php

namespace LRC\Repository;

/**
 * Repository manager.
 */
class RepositoryManager
{
    /**
     * @var array   Registered repositories.
     */
    private $repositories = [];
    
    
    /**
     * Create a new repository and add it to the manager.
     *
     * @param string            $model  Model class.
     * @param array             $config Repository configuration.
     *
     * @return ManagedRepository        The created repository.
     *
     * @throws RepositoryException      If the requested repository type is not implemented.
     */
    public function createRepository($model, $config)
    {
        $defaults = [
            'type' => 'db-soft',
            'key' => 'id',
            'deleted' => 'deleted'
        ];
        $config = array_merge($defaults, $config);
        
        switch ($config['type']) {
            case 'db':
                $repo = new DbRepository($config['db'], $config['table'], $model, $config['key']);
                break;
            case 'db-soft':
                $repo = new SoftDbRepository($config['db'], $config['table'], $model, $config['deleted'], $config['key']);
                break;
            default:
                throw new RepositoryException("Repository type '" . $config['type'] . "' not implemented");
        }
        
        $this->addRepository($repo);
        return $repo;
    }
    
    
    /**
     * Register a repository with the manager.
     *
     * @param ManagedRepository $repository     Manageable repository.
     *
     * @throws RepositoryException              If the manager already contains a repository for the same model class.
     */
    public function addRepository($repository)
    {
        $class = $repository->getModelClass();
        if ($this->getByClass($class)) {
            throw new RepositoryException("The manager already contains a repository for the model class '$class'");
        }
        
        $repository->setManager($this);
        $this->repositories[$class] = $repository;
    }
    
    
    /**
     * Get a registered repository by model class.
     *
     * @param string                $class  Model class.
     *
     * @return ManagedRepository|null       Matching repository, or null if no repository found.
     */
    public function getByClass($class)
    {
        return (array_key_exists($class, $this->repositories) ? $this->repositories[$class] : null);
    }
    
    
    /**
     * Inject manager reference into a model capable of receiving it.
     *
     * @param object $model Model instance.
     */
    public function manageModel($model)
    {
        if ($model instanceof ManagedModelInterface) {
            $model->setManager($this);
        }
    }
}
