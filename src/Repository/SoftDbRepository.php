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
     */
    public function __construct($db, $table, $modelClass, $deleted)
    {
        parent::__construct($db, $table, $modelClass);
        $this->deleted = $deleted;
    }
    
    
    /**
     * Find and return first entry by key, ignoring soft-deleted entries.
     *
     * @param string $column    Key column name.
     * @param mixed  $value     Key value.
     *
     * @return mixed            Model instance.
     */
    public function findSoft($column, $value)
    {
        return $this->getFirstSoft("$column = ?", [$value]);
    }
    
    
    /**
     * Retrieve first entry ignoring soft-deleted ones, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * 
     * @return mixed                Model instance.
     */
    public function getFirstSoft($conditions = null, $values = [])
    {
        return $this->executeQuery(null, $conditions, $values, null, true)
            ->fetchClass($this->modelClass);
    }
    
    
    /**
     * Retrieve all entries ignoring soft-deleted ones, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * 
     * @return array                Array of all matching entries.
     */
    public function getAllSoft($conditions = null, $values = [])
    {
        return $this->executeQuery(null, $conditions, $values, null, true)
            ->fetchAllClass($this->modelClass);
    }
    
    
    /**
     * Soft delete entry.
     *
     * @param mixed $model  Model instance.
     */
    public function deleteSoft($model)
    {
        $this->db->connect()
            ->update($this->table, [$this->deleted])
            ->where('id = ?')
            ->execute([date('Y-m-d H:i:s'), $model->id]);
    }
    
    
    /**
     * Restore soft-deleted entry.
     *
     * @param mixed $model  Model instance.
     */
    public function restoreSoft($model)
    {
        $this->db->connect()
            ->update($this->table, [$this->deleted])
            ->where('id = ?')
            ->execute([null, $model->id]);
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
        $res = $this->executeQuery('COUNT(id) AS num', $conditions, $values, null, true)
            ->fetch();
        return (isset($res->num) ? (int)$res->num : 0);
    }
    
    
    /**
     * Execute query for selection methods.
     * 
     * @param string $select                        Selection criteria.
     * @param string $conditions                    Where conditions.
     * @param array  $values                        Array of where condition values to bind.
     * @param string $order                         Order by conditions.
     * 
     * @return \Anax\Database\DatabaseQueryBuilder  Database service instance with executed internal query.
     */
    protected function executeQuery($select = null, $conditions = null, $values = [], $order = null)
    {
        $delCond = $this->deleted . ' IS NULL';
        $conditions = (is_null($conditions) ? $delCond : "($conditions) AND $delCond");
        return parent::executeQuery($select, $conditions, $values, $order);
    }
}
