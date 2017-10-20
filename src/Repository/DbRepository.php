<?php

namespace LRC\Repository;

/**
 * Base class for database-backed repositories for data access.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DbRepository extends ManagedRepository implements RepositoryInterface
{
    /**
     * @var \Anax\Database\DatabaseQueryBuilder     Database service.
     */
    protected $db;
    
    /**
     * @var string  Database table name.
     */
    protected $table;
    
    /**
     * @var string  Model class name.
     */
    protected $modelClass;
    
    /**
     * @var string  Primary key column.
     */
    protected $key;
    
    
    /**
     * Constructor.
     *
     * @param \Anax\Database\DatabaseQueryBuilder   $db         Database service.
     * @param string                                $table      Database table name.
     * @param string                                $modelClass Model class name.
     * @param string                                $key        Primary key column.
     */
    public function __construct($db, $table, $modelClass, $key = 'id')
    {
        $this->db = $db;
        $this->table = $table;
        $this->modelClass = $modelClass;
        $this->key = $key;
    }
    
    
    /**
     * Return the name of the database table represented by the repository.
     */
    public function getCollectionName()
    {
        return $this->table;
    }
    
    
    /**
     * Return the class of the model handled by the repository.
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }
    
    
    /**
     * Find and return first entry by key.
     *
     * @param string|null   $column Key column name (pass null to use registered primary key).
     * @param mixed         $value  Key value.
     *
     * @return mixed                Model instance.
     */
    public function find($column, $value)
    {
        return $this->getFirst((is_null($column) ? $this->key : $column) . ' = ?', [$value]);
    }
    
    
    /**
     * Retrieve first entry, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * @param array  $options       Query options.
     * 
     * @return mixed                Model instance.
     */
    public function getFirst($conditions = null, $values = [], $options = [])
    {
        $query = $this->executeQuery(null, $conditions, $values, $options);
        if (!empty($this->fetchRefs)) {
            $res = $query->fetch();
            $model = ($res ? $this->populateModelFromJoin($res) : $res);
        } else {
            $model = $query->fetchClass($this->modelClass);
        }
        if ($model && isset($this->manager)) {
            $this->manager->manageModel($model);
        }
        $this->fetchReferences(false);
        return $model;
    }
    
    
    /**
     * Retrieve all entries, optionally filtered by search criteria.
     * 
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * @param array  $options       Query options.
     * 
     * @return array                Array of all matching entries.
     */
    public function getAll($conditions = null, $values = [], $options = [])
    {
        $query = $this->executeQuery(null, $conditions, $values, $options);
        if (!empty($this->fetchRefs)) {
            $models = [];
            foreach ($query->fetchAll() as $model) {
                $models[] = $this->populateModelFromJoin($model);
            }
        } else {
            $models = $query->fetchAllClass($this->modelClass);
        }
        if (isset($this->manager)) {
            foreach ($models as $model) {
                $this->manager->manageModel($model);
            }
        }
        $this->fetchReferences(false);
        return $models;
    }
    
    
    /**
     * Save entry by inserting if ID is missing and updating if ID exists.
     * 
     * @param mixed $model  Model instance.
     *
     * @return void
     */
    public function save($model)
    {
        if (isset($model->{$this->key})) {
            return $this->update($model);
        }
        
        return $this->create($model);
    }
    
    
    /**
     * Delete entry.
     *
     * @param mixed $model  Model instance.
     */
    public function delete($model)
    {
        $this->db->connect()
            ->deleteFrom($this->table)
            ->where($this->key . ' = ?')
            ->execute([$model->{$this->key}]);
        $model->{$this->key} = null;
    }
    
    
    /**
     * Count entries, optionally filtered by search criteria.
     *
     * @param string $conditions    Where conditions.
     * @param array  $values        Array of condition values to bind.
     * 
     * @return int                  Number of entries.
     */
    public function count($conditions = null, $values = [])
    {
        $res = $this->executeQuery('COUNT(' . $this->key . ') AS num', $conditions, $values)
            ->fetch();
        return (isset($res->num) ? (int)$res->num : 0);
    }
    
    
    /**
     * Execute query for selection methods.
     * 
     * @param   string  $select                     Selection criteria.
     * @param   string  $conditions                 Where conditions.
     * @param   array   $values                     Array of where condition values to bind.
     * @param   array   $options                    Query options.
     * 
     * @return \Anax\Database\DatabaseQueryBuilder  Database service instance with executed internal query.
     */
    protected function executeQuery($select = null, $conditions = null, $values = [], $options = [])
    {
        $query = $this->db->connect();
        if (!empty($this->fetchRefs)) {
            $query = $this->setupJoin($query, $select, $conditions, (isset($options['order']) ? $options['order'] : null));
        } else {
            $query = (!is_null($select) ? $query->select($select) : $query->select());
            $query = $query->from($this->table);
            if (!is_null($conditions)) {
                $query = $query->where($conditions);
            }
            if (isset($options['order'])) {
                $query = $query->orderBy($options['order']);
            }
        }
        return $query->execute($values);
    }
    
    
    
    /**
     * Populate model instance including retrieved references from join query result.
     * 
     * @param object $result    Query result.
     * 
     * @return mixed            Populated model instance.
     */
    protected function populateModelFromJoin($result)
    {
        // extract main model
        $model = new $this->modelClass();
        foreach (array_keys(get_object_vars($model)) as $attr) {
            $model->$attr = $result->$attr;
        }
        
        // extract referenced models
        $refs = $model->getReferences();
        $refs2 = (is_array($this->fetchRefs) ? $this->fetchRefs : array_keys($refs));
        sort($refs2);
        foreach ($refs2 as $idx => $name) {
            $prefix = "REF{$idx}_{$name}__";
            
            // handle null result
            if (is_null($result->{$prefix . $refs[$name]['key']})) {
                $refModel = null;
            } else {
                $refModel = new $refs[$name]['model']();
                foreach (array_keys(get_object_vars($refModel)) as $attr) {
                    $refModel->$attr = $result->{$prefix . $attr};
                }
            }
            
            // inject manager reference
            if ($refModel && $this->manager) {
                $this->manager->manageModel($refModel);
            }
            
            $model->$name = $refModel;
        }
        
        return $model;
    }
    

    /**
     * Set up join query for reference retrieval.
     *
     * @param \Anax\Database\DatabaseQueryBuilder   $query      Database service instance with initialized query.
     * @param string                                $select     Selection criteria.
     * @param string                                $conditions Where conditions.
     * @param string                                $order      Order by clause.
     *
     * @return \Anax\Database\DatabaseQueryBuilder              Database service instance with prepared join query.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function setupJoin($query, $select, $conditions, $order = null)
    {
        // find references
        $model = new $this->modelClass();
        $refs = $model->getReferences();
        if (is_array($this->fetchRefs)) {
            $refs = array_intersect_key($refs, array_flip($this->fetchRefs));
        }
        ksort($refs);
        
        // prefix main model selection
        if (!is_null($select)) {
            $select = $this->prefixModelAttributes($select, $model);
        } else {
            $select = $this->table . '.*';
        }
        
        // set up reference aliases and join conditions
        $select = [$select];
        $join = [];
        $idx = 0;
        foreach ($refs as $name => $ref) {
            // prefix attributes
            $refTable = "REF{$idx}_{$name}";
            $idx++;
            foreach (array_keys(get_object_vars(new $ref['model']())) as $attr) {
                $select[] = "{$refTable}.{$attr} AS '{$refTable}__{$attr}'";
            }
            
            // generate join conditions
            $joinCond = $this->table . '.' . $ref['attribute'] . " = {$refTable}." . $ref['key'];
            $refRepo = $this->manager->getByClass($ref['model']);
            if ($this->softRefs && $refRepo instanceof SoftDbRepository) {
                $joinCond .= " AND $refTable." . $refRepo->getDeletedAttribute() . ' IS NULL';
            }
            $join[] = [$refRepo->getCollectionName() . " AS $refTable", $joinCond];
        }
        
        // generate join query
        $query = $query->select(implode(', ', $select))->from($this->table);
        foreach ($join as $args) {
            $query = $query->leftJoin(...$args);
        }
        
        // prefix where conditions
        if (!is_null($conditions)) {
            $query = $query->where($this->prefixModelAttributes($conditions, $model));
        }
        
        // prefix order by clause
        if (!is_null($order)) {
            $query = $query->orderBy($this->prefixModelAttributes($order, $model));
        }
        
        return $query;
    }
    
    
    /**
     * Prefix model attributes with the associated table name.
     * 
     * @param string $input     Input string.
     * @param object $model     Model instance.
     * 
     * @return string           String with table-prefixed attributes.
     */
    private function prefixModelAttributes($input, $model)
    {
        foreach (array_keys(get_object_vars($model)) as $attr) {
            $input = preg_replace('/\\b' . $attr . '\\b/', $this->table . ".$attr", $input);
        }
        return $input;
    }
    
        
    /**
     * Create new entry.
     * 
     * @param mixed $model  Model instance.
     */
    private function create($model)
    {
        $attrs = $this->getMutableAttributes($model);
        $this->db
            ->connect()
            ->insert($this->table, array_keys($attrs))
            ->execute(array_values($attrs));
        $model->{$this->key} = $this->db->lastInsertId();
    }
    
    
    /**
     * Update entry.
     * 
     * @param mixed $model  Model instance.
     */
    private function update($model)
    {
        $attrs = $this->getMutableAttributes($model);
        $values = array_values($attrs);
        $values[] = $model->{$this->key};
        $this->db
            ->connect()
            ->update($this->table, array_keys($attrs))
            ->where($this->key . ' = ?')
            ->execute($values);
    }
    
    
    /**
     * Get mutable model attributes.
     *
     * @param object $model Model instance.
     *
     * @return array        Array of attributes.
     */
    private function getMutableAttributes($model)
    {
        $attrs = get_object_vars($model);
        unset($attrs[$this->key]);
        
        // remove reference attributes, if any
        if ($model instanceof ManagedModelInterface) {
            foreach (array_keys($model->getReferences()) as $ref) {
                unset($attrs[$ref]);
            }
        }
        
        return $attrs;
    }
}
