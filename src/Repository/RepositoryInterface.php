<?php

namespace LRC\Repository;

/**
 * Repository interface for data access.
 */
interface RepositoryInterface
{
    /**
     * Return the class of the model handled by the repository.
     */
    public function getModelClass();
    
    
    /**
     * Find and return first entry by key.
     *
     * @param string|null   $column Key column name (pass null to use registered primary key).
     * @param mixed         $value  Key value.
     *
     * @return mixed                Model instance.
     */
    public function find($column, $value);
    
    
    /**
     * Retrieve first entry, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * 
     * @return mixed                Model instance.
     */
    public function getFirst($conditions = null, $values = []);
    
    
    /**
     * Retrieve all entries, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * 
     * @return array                Array of all matching entries.
     */
    public function getAll($conditions = null, $values = []);
    
    
    /**
     * Save entry by inserting if ID is missing and updating if ID exists.
     * 
     * @param mixed $model  Model instance.
     *
     * @return void
     */
    public function save($model);
    
    
    /**
     * Delete entry.
     *
     * @param mixed $model  Model instance.
     */
    public function delete($model);
    
    
    /**
     * Count entries, optionally filtered by search criteria.
     *
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * 
     * @return int                  Number of entries.
     */
    public function count($conditions = null, $values = []);
}
