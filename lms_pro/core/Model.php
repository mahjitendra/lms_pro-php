<?php

namespace LmsPro\Core;

use PDO;
use Exception;

abstract class Model
{
    /**
     * The database connection instance.
     *
     * @var PDO
     */
    protected static $pdo;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected static $table;

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Create a new model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Get the database connection.
     *
     * @return PDO
     */
    protected static function db()
    {
        if (!static::$pdo) {
            static::$pdo = Database::connection();
        }
        return static::$pdo;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     * @return self
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable) || empty($this->fillable)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Dynamically set an attribute.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Dynamically get an attribute.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute($key, $value)
    {
        // Check for a mutator method (e.g., setFirstNameAttribute)
        $mutator = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->$mutator($value);
        } else {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Find a model by its primary key.
     *
     * @param int $id
     * @return static|null
     */
    public static function find($id)
    {
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE id = ?");
        $stmt->execute([$id]);
        $attributes = $stmt->fetch();

        return $attributes ? new static($attributes) : null;
    }

    /**
     * Get all of the models from the database.
     *
     * @return array
     */
    public static function all()
    {
        $stmt = static::db()->query("SELECT * FROM " . static::$table);
        return array_map(fn($attributes) => new static($attributes), $stmt->fetchAll());
    }

    /**
     * Save the model to the database (create or update).
     *
     * @return self
     */
    public function save()
    {
        if (isset($this->attributes['id'])) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    /**
     * Create a new record in the database.
     *
     * @return self
     */
    public function create()
    {
        $columns = implode(', ', array_keys($this->attributes));
        $placeholders = implode(', ', array_fill(0, count($this->attributes), '?'));

        $sql = "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})";

        $stmt = static::db()->prepare($sql);
        $stmt->execute(array_values($this->attributes));

        $this->id = static::db()->lastInsertId();
        return $this;
    }

    /**
     * Update the model in the database.
     *
     * @return self
     */
    public function update()
    {
        $setClauses = [];
        $values = [];
        foreach ($this->attributes as $key => $value) {
            if ($key !== 'id') {
                $setClauses[] = "{$key} = ?";
                $values[] = $value;
            }
        }
        $values[] = $this->id;

        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $stmt = static::db()->prepare($sql);
        $stmt->execute($values);

        return $this;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $sql = "DELETE FROM " . static::$table . " WHERE id = ?";
        $stmt = static::db()->prepare($sql);
        return $stmt->execute([$this->id]);
    }
}