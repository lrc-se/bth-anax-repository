<?php

namespace LRC\Repository;

/**
 * Base class for database-backed soft-deletion-aware repositories for data access.
 */
class SoftDbRepository extends DbRepository implements SoftRepositoryInterface
{
    /**
     * @var string  Soft deletion attribute.
     */
    protected $deleted;
    
    
    /**
     * Constructor.
     *
     * @param \Anax\Database\DatabaseQueryBuilder   $db         Database service.
     * @param string                                $table      Database table name.
     * @param string                                $modelClass Model class name.
     * @param string                                $deleted    Soft deletion attribute.
     * @param string                                $key        Primary key column.
     */
    public function __construct($db, $table, $modelClass, $deleted = 'deleted', $key = 'id')
    {
        parent::__construct($db, $table, $modelClass, $key);
        $this->deleted = $deleted;
    }
    
    
    /**
     * Return the name of the attribute used to mark soft deletion.
     */
    public function getDeletedAttribute()
    {
        return $this->deleted;
    }
    
    
    /**
     * Find and return first entry by key, ignoring soft-deleted entries.
     *
     * @param string|null   $column Key column name (pass null to use registered primary key).
     * @param mixed         $value  Key value.
     *
     * @return mixed                Model instance.
     */
    public function findSoft($column, $value)
    {
        return $this->getFirstSoft((is_null($column) ? $this->key : $column) . ' = ?', [$value]);
    }
    
    
    /**
     * Retrieve first entry ignoring soft-deleted ones, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * @param array  $options       Query options.
     * 
     * @return mixed                Model instance.
     */
    public function getFirstSoft($conditions = null, $values = [], $options = [])
    {
        return $this->processSingleResult($this->executeQuerySoft(null, $conditions, $values, $options));
    }
    
    
    /**
     * Retrieve all entries ignoring soft-deleted ones, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * @param array  $options       Query options.
     * 
     * @return array                Array of all matching entries.
     */
    public function getAllSoft($conditions = null, $values = [], $options = [])
    {
        return $this->processMultipleResults($this->executeQuerySoft(null, $conditions, $values, $options));
    }
    
    
    /**
     * Soft delete entry.
     *
     * @param mixed $model  Model instance.
     */
    public function deleteSoft($model)
    {
        $model->deleted = date('Y-m-d H:i:s');
        $this->save($model);
    }
    
    
    /**
     * Restore soft-deleted entry.
     *
     * @param mixed $model  Model instance.
     */
    public function restoreSoft($model)
    {
        $model->deleted = null;
        $this->save($model);
    }
    
    
    /**
     * Count entries ignoring soft-deleted ones, optionally filtered by search criteria.
     *
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * 
     * @return int                  Number of entries.
     */
    public function countSoft($conditions = null, $values = [])
    {
        $res = $this->executeQuerySoft('COUNT(' . $this->key . ') AS num', $conditions, $values)
            ->fetch();
        return (isset($res->num) ? (int)$res->num : 0);
    }
    
    
    /**
     * Execute soft-deletion-aware query for selection methods.
     * 
     * @param   string  $select                     Selection criteria.
     * @param   string  $conditions                 Where conditions.
     * @param   array   $values                     Array of where condition values to bind.
     * @param   array   $options                    Query options.
     * 
     * @return \Anax\Database\DatabaseQueryBuilder  Database service instance with executed internal query.
     */
    protected function executeQuerySoft($select = null, $conditions = null, $values = [], $options = [])
    {
        $delCond = $this->deleted . ' IS NULL';
        $conditions = (is_null($conditions) ? $delCond : "($conditions) AND $delCond");
        return $this->executeQuery($select, $conditions, $values, $options);
    }
}
