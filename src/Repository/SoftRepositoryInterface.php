<?php

namespace LRC\Repository;

/**
 * Repository interface for data access, with soft-deletion capabilities.
 */
interface SoftRepositoryInterface extends RepositoryInterface
{
    /**
     * Return the name of the attribute used to mark soft deletion.
     */
    public function getDeletedAttribute();
    
    
    /**
     * Find and return first entry by key, ignoring soft-deleted entries.
     *
     * @param string|null   $column Key column name (pass null to use registered primary key).
     * @param mixed         $value  Key value.
     *
     * @return mixed                Model instance.
     */
    public function findSoft($column, $value);
    
        
    /**
     * Retrieve first entry ignoring soft-deleted ones, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * @param array  $options       Query options.
     * 
     * @return mixed                Model instance.
     */
    public function getFirstSoft($conditions = null, $values = [], $options = []);
    
    
    /**
     * Retrieve all entries ignoring soft-deleted ones, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * @param array  $options       Query options.
     * 
     * @return array                Array of all matching entries.
     */
    public function getAllSoft($conditions = null, $values = [], $options = []);
    
    
    /**
     * Soft delete entry.
     *
     * @param mixed $model  Model instance.
     */
    public function deleteSoft($model);
    
    
    /**
     * Restore soft-deleted entry.
     *
     * @param mixed $model  Model instance.
     */
    public function restoreSoft($model);


    /**
     * Count entries ignoring soft-deleted ones, optionally filtered by search criteria.
     *
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * 
     * @return int                  Number of entries.
     */
    public function countSoft($conditions = null, $values = []);
}
