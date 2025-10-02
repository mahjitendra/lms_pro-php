<?php

/**
 * Base Model Class
 * LMS Pro - Learning Management System
 */

abstract class Model
{
    protected $database;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id'];
    protected $hidden = [];
    protected $casts = [];
    protected $dates = ['created_at', 'updated_at'];
    protected $timestamps = true;
    protected $softDeletes = false;
    protected $deletedAtColumn = 'deleted_at';
    
    // Relationships
    protected $relationships = [];
    protected $loaded = [];
    
    // Query scopes
    protected $globalScopes = [];
    
    // Events
    protected $events = [];

    public function __construct($database = null)
    {
        $this->database = $database ?: App::getInstance()->getDatabase();
        
        if (!$this->table) {
            $this->table = $this->getTableName();
        }
        
        $this->bootTraits();
        $this->initialize();
    }

    /**
     * Initialize method called after constructor
     */
    protected function initialize()
    {
        // Override in child classes
    }

    /**
     * Boot traits
     */
    protected function bootTraits()
    {
        $traits = class_uses_recursive(get_class($this));
        
        foreach ($traits as $trait) {
            $method = 'boot' . class_basename($trait);
            if (method_exists($this, $method)) {
                $this->$method();
            }
        }
    }

    /**
     * Get table name from class name
     */
    protected function getTableName()
    {
        $className = class_basename(get_class($this));
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className)) . 's';
    }

    /**
     * Create a new query builder instance
     */
    public function newQuery()
    {
        $query = $this->database->table($this->table);
        
        // Apply global scopes
        foreach ($this->globalScopes as $scope) {
            $query = $scope($query);
        }
        
        // Apply soft deletes
        if ($this->softDeletes) {
            $query->whereNull($this->deletedAtColumn);
        }
        
        return $query;
    }

    /**
     * Find record by ID
     */
    public function find($id)
    {
        $result = $this->newQuery()->where($this->primaryKey, $id)->first();
        return $result ? $this->newInstance($result) : null;
    }

    /**
     * Find record by ID or fail
     */
    public function findOrFail($id)
    {
        $result = $this->find($id);
        if (!$result) {
            throw new Exception("Record not found with ID: {$id}");
        }
        return $result;
    }

    /**
     * Find records by IDs
     */
    public function findMany($ids)
    {
        $results = $this->newQuery()->whereIn($this->primaryKey, $ids)->get();
        return array_map([$this, 'newInstance'], $results);
    }

    /**
     * Get first record
     */
    public function first()
    {
        $result = $this->newQuery()->first();
        return $result ? $this->newInstance($result) : null;
    }

    /**
     * Get all records
     */
    public function all()
    {
        $results = $this->newQuery()->get();
        return array_map([$this, 'newInstance'], $results);
    }

    /**
     * Get records with pagination
     */
    public function paginate($perPage = 15, $page = 1)
    {
        $query = $this->newQuery();
        $total = $query->count();
        
        $offset = ($page - 1) * $perPage;
        $results = $query->limit($perPage, $offset)->get();
        
        return [
            'data' => array_map([$this, 'newInstance'], $results),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1,
            ]
        ];
    }

    /**
     * Create new record
     */
    public function create($data)
    {
        $data = $this->filterFillable($data);
        $data = $this->castAttributes($data);
        
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }
        
        $this->fireEvent('creating', $data);
        
        $id = $this->database->insert($this->table, $data);
        $data[$this->primaryKey] = $id;
        
        $instance = $this->newInstance($data);
        
        $this->fireEvent('created', $instance);
        
        return $instance;
    }

    /**
     * Update record
     */
    public function update($id, $data)
    {
        $data = $this->filterFillable($data);
        $data = $this->castAttributes($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $this->fireEvent('updating', $data);
        
        $affected = $this->database->update(
            $this->table,
            $data,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
        
        if ($affected > 0) {
            $instance = $this->find($id);
            $this->fireEvent('updated', $instance);
            return $instance;
        }
        
        return null;
    }

    /**
     * Delete record
     */
    public function delete($id)
    {
        $instance = $this->find($id);
        if (!$instance) {
            return false;
        }
        
        $this->fireEvent('deleting', $instance);
        
        if ($this->softDeletes) {
            $affected = $this->database->update(
                $this->table,
                [$this->deletedAtColumn => date('Y-m-d H:i:s')],
                "{$this->primaryKey} = :id",
                ['id' => $id]
            );
        } else {
            $affected = $this->database->delete(
                $this->table,
                "{$this->primaryKey} = :id",
                ['id' => $id]
            );
        }
        
        if ($affected > 0) {
            $this->fireEvent('deleted', $instance);
            return true;
        }
        
        return false;
    }

    /**
     * Force delete record (permanent delete)
     */
    public function forceDelete($id)
    {
        $instance = $this->find($id);
        if (!$instance) {
            return false;
        }
        
        $this->fireEvent('forceDeleting', $instance);
        
        $affected = $this->database->delete(
            $this->table,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
        
        if ($affected > 0) {
            $this->fireEvent('forceDeleted', $instance);
            return true;
        }
        
        return false;
    }

    /**
     * Restore soft deleted record
     */
    public function restore($id)
    {
        if (!$this->softDeletes) {
            throw new Exception('Model does not use soft deletes');
        }
        
        $affected = $this->database->update(
            $this->table,
            [$this->deletedAtColumn => null],
            "{$this->primaryKey} = :id AND {$this->deletedAtColumn} IS NOT NULL",
            ['id' => $id]
        );
        
        return $affected > 0;
    }

    /**
     * Get trashed records (soft deleted)
     */
    public function trashed()
    {
        if (!$this->softDeletes) {
            throw new Exception('Model does not use soft deletes');
        }
        
        $results = $this->database->table($this->table)
            ->whereNotNull($this->deletedAtColumn)
            ->get();
            
        return array_map([$this, 'newInstance'], $results);
    }

    /**
     * Where clause
     */
    public function where($column, $operator = null, $value = null)
    {
        return $this->newQuery()->where($column, $operator, $value);
    }

    /**
     * Where In clause
     */
    public function whereIn($column, $values)
    {
        return $this->newQuery()->whereIn($column, $values);
    }

    /**
     * Order By clause
     */
    public function orderBy($column, $direction = 'ASC')
    {
        return $this->newQuery()->orderBy($column, $direction);
    }

    /**
     * Limit clause
     */
    public function limit($limit, $offset = null)
    {
        return $this->newQuery()->limit($limit, $offset);
    }

    /**
     * Count records
     */
    public function count()
    {
        return $this->newQuery()->count();
    }

    /**
     * Check if record exists
     */
    public function exists($conditions = [])
    {
        $query = $this->newQuery();
        
        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }
        
        return $query->exists();
    }

    /**
     * Create new model instance
     */
    protected function newInstance($data = [])
    {
        $instance = new static($this->database);
        $instance->fill($data);
        return $instance;
    }

    /**
     * Fill model with data
     */
    public function fill($data)
    {
        foreach ($data as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Set attribute value
     */
    public function setAttribute($key, $value)
    {
        // Check for mutator
        $mutator = 'set' . studly_case($key) . 'Attribute';
        if (method_exists($this, $mutator)) {
            return $this->$mutator($value);
        }
        
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get attribute value
     */
    public function getAttribute($key)
    {
        // Check for accessor
        $accessor = 'get' . studly_case($key) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor($this->attributes[$key] ?? null);
        }
        
        $value = $this->attributes[$key] ?? null;
        
        // Cast attribute
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }
        
        return $value;
    }

    /**
     * Check if attribute is fillable
     */
    protected function isFillable($key)
    {
        if (in_array($key, $this->guarded)) {
            return false;
        }
        
        if (empty($this->fillable)) {
            return true;
        }
        
        return in_array($key, $this->fillable);
    }

    /**
     * Filter fillable attributes
     */
    protected function filterFillable($data)
    {
        $filtered = [];
        
        foreach ($data as $key => $value) {
            if ($this->isFillable($key)) {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }

    /**
     * Cast attributes
     */
    protected function castAttributes($data)
    {
        foreach ($data as $key => $value) {
            if (isset($this->casts[$key])) {
                $data[$key] = $this->castAttribute($key, $value);
            }
        }
        
        return $data;
    }

    /**
     * Cast single attribute
     */
    protected function castAttribute($key, $value)
    {
        if ($value === null) {
            return null;
        }
        
        $cast = $this->casts[$key];
        
        switch ($cast) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'date':
                return date('Y-m-d', strtotime($value));
            case 'datetime':
                return date('Y-m-d H:i:s', strtotime($value));
            default:
                return $value;
        }
    }

    /**
     * Convert model to array
     */
    public function toArray()
    {
        $array = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $array[$key] = $this->getAttribute($key);
            }
        }
        
        // Include loaded relationships
        foreach ($this->loaded as $relation => $data) {
            if (is_array($data)) {
                $array[$relation] = array_map(function($item) {
                    return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item;
                }, $data);
            } else {
                $array[$relation] = is_object($data) && method_exists($data, 'toArray') ? $data->toArray() : $data;
            }
        }
        
        return $array;
    }

    /**
     * Convert model to JSON
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Define a one-to-one relationship
     */
    protected function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        return new HasOneRelation($this, $related, $foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship
     */
    protected function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        return new HasManyRelation($this, $related, $foreignKey, $localKey);
    }

    /**
     * Define a belongs-to relationship
     */
    protected function belongsTo($related, $foreignKey = null, $ownerKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getRelatedForeignKey($related);
        $ownerKey = $ownerKey ?: 'id';
        
        return new BelongsToRelation($this, $related, $foreignKey, $ownerKey);
    }

    /**
     * Define a many-to-many relationship
     */
    protected function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null)
    {
        $table = $table ?: $this->getPivotTableName($related);
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?: $this->getRelatedForeignKey($related);
        
        return new BelongsToManyRelation($this, $related, $table, $foreignPivotKey, $relatedPivotKey);
    }

    /**
     * Get foreign key for this model
     */
    protected function getForeignKey()
    {
        return strtolower(class_basename(get_class($this))) . '_id';
    }

    /**
     * Get foreign key for related model
     */
    protected function getRelatedForeignKey($related)
    {
        return strtolower(class_basename($related)) . '_id';
    }

    /**
     * Get pivot table name
     */
    protected function getPivotTableName($related)
    {
        $models = [
            strtolower(class_basename(get_class($this))),
            strtolower(class_basename($related))
        ];
        sort($models);
        return implode('_', $models);
    }

    /**
     * Load relationship
     */
    public function load($relations)
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }
        
        foreach ($relations as $relation) {
            if (method_exists($this, $relation)) {
                $this->loaded[$relation] = $this->$relation()->get();
            }
        }
        
        return $this;
    }

    /**
     * Fire model event
     */
    protected function fireEvent($event, $data = null)
    {
        if (isset($this->events[$event])) {
            foreach ($this->events[$event] as $callback) {
                call_user_func($callback, $data);
            }
        }
    }

    /**
     * Register event listener
     */
    public function on($event, $callback)
    {
        $this->events[$event][] = $callback;
    }

    /**
     * Magic getter
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Magic unset
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Magic toString
     */
    public function __toString()
    {
        return $this->toJson();
    }
}

// Helper function for studly case
if (!function_exists('studly_case')) {
    function studly_case($value) {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}

if (!function_exists('class_basename')) {
    function class_basename($class) {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('class_uses_recursive')) {
    function class_uses_recursive($class) {
        $results = [];
        
        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += trait_uses_recursive($class);
        }
        
        return array_unique($results);
    }
}

if (!function_exists('trait_uses_recursive')) {
    function trait_uses_recursive($trait) {
        $traits = class_uses($trait);
        
        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }
        
        return $traits;
    }
}