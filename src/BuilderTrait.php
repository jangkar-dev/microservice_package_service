<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait BuilderTrait
{
    private $cleaner = [];
    private $joinedTable = [];

    /**
     * It joins the tables based on the given joiners and returns the query
     * 
     * @param query The query builder object
     * @param array joiners An array of strings that will be used to join the tables.
     * @param array except This is an array of tables that you don't want to join.
     * 
     * @return The query builder object.
     */
    public function scopeJoinWith($query, array $joiners, array $except = [])
    {
        // Select the base table
        $query->selectBaseTable();
        // Loop through the given joiners
        foreach ($joiners as $joiner) {
            // Split the joiner by '.' to get the base table and the joiner
            $splits = explode('.', $joiner);
            // Count the number of splits
            $counted = count($splits);
            // Loop through the splits
            for ($i = 0; $i < $counted; $i++) {
                // Initialize variables
                $base = '';
                $joiner = '';

                if ((isset($splits[$i + 1]) && $i > 0) || ($i + 1 == $counted && $counted > 1)) {
                    // Set the base and joiner
                    $base = $splits[$i - 1];
                    $joiner = $splits[$i];
                } else {
                    // Set the joiner only
                    $joiner = $splits[$i];
                }

                // Remove underscores and capitalize the joiner
                $connector = 'join' . str_replace("_", "", ucwords($joiner, " /_"));

                if ($base != '') {
                    // Get the table name for the base
                    $Table = 'App\Models\\' . ucwords($base);
                    $base = $Table::getTableName();
                }

                // Check if the base is empty
                $validator = $base == '' ? $connector : $connector . '(' . $base . ')';

                // Check if the validator is not in the cleaner array
                if (!in_array($validator, $this->cleaner) || $this->cleaner == []) {
                    // Join the tables
                    $query = $base == '' ? $query->$connector() : $query->$connector($base);

                    // Add the validator to the cleaner array
                    $this->cleaner[] = $validator;

                    // Add the joiner to the joined table array
                    $this->joinedTable[] = $joiner;
                }
            }

        }
        return $query->selectWith();
        // return [
        //     $this->cleaner,
        //     $this->joinedTable
        // ];
    }

    /**
     * It takes the fillable columns of the current class and 
     * adds the plural form of the table name as a prefix to each column
     */
    public function scopeSelectBaseTable($query)
    {
        // Get the name of the current class and its fillable columns
        $table = __CLASS__;
        $fillableColumns = $table::getTableFillable();

        // Get the plural form of the table name
        $tableNamePlural = $table::getTableName();

        // Create an array to store the selected columns
        $selectedColumns = [];

        // Add each fillable column to the selected columns array,
        // using the plural table name as a prefix
        foreach ($fillableColumns as $column) {
            $selectedColumns[] = "$tableNamePlural.$column";
        }

        // Return the query with the selected columns
        return $query->select($selectedColumns);
    }

    public function toCamelCase($table)
    {
        $table = Str::singular($table);
        $table = Str::camel($table);
        return $table;
    }
    public function scopeSelectWith($query, array $except = [])
    {
        $response = [];

        // Loop through the joined table array
        foreach ($this->joinedTable as $table) {
            // Convert the table name to camel case
            $table = $this->toCamelCase($table);

            // Get the table model class
            $Table = 'App\Models\\' . $table;

            // Get the fillable fields of the table
            $fillable =  $Table::getTableFillable();

            // Get the plural and singular names of the table
            $plural = $Table::getTableName();
            $singular = Str::singular($plural);

            // Check if the singular or plural name is in the except array
            if (in_array($singular, $except) || in_array($plural, $except)) {
                continue;
            }

            // Append the table name to the fillable fields
            foreach ($fillable as $key => $row) {
                $fillable[$key] = $plural . '.' . $row . ' as ' . $singular . '.' . $row;
            }

            // Merge the fillable fields with the response array
            $response = array_merge($response, $fillable);
        }

        // Add the fillable fields to the query and return it
        return $query->addSelect($response);
    }

    public function scopeSelectAttribute($query, array $relation, array $attributes = [])
    {
        if (empty($attributes)) {
            $table = $relation;
            $table = $this->toCamelCase($table);
            $Table = 'App\Models\\' . $table;
            $model = new $Table();
            $attributes = $model->attributes;
        }
        $relation = $this->toCamelCase($relation);
        foreach ($attributes as $attribute) {
            $attribute = ucwords($attribute);
            $scope = $relation . $attribute . 'Attribute';
            $query->$scope();
        }
        return $query;
    }

    public function scopeGroupBySelf($query)
    {
        $table = __CLASS__;
        return $query->groupBy($table::getTablePrimaryKey());
    }

    /**
     * It joins a table to another table using the singular name of the base table as the foreign key
     *
     * @param query The query object that is being built.
     * @param baseTable The table you're joining from
     * @param targetTable The table you want to join to.
     *
     * @return A query builder object.
     */
    public function scopeJoinTo($query, $baseTable, $targetTable)
    {
        return $query->join($targetTable, $baseTable . '.id', $targetTable . '.' . Str::singular($baseTable)  . '_id');
    }

    public function scopeJoinFrom($query, $baseTable, $targetTable)
    {
        return $query->join($targetTable, $baseTable . '.' . Str::singular($targetTable)  . '_id', $targetTable . '.id');
    }

    public static function getTableName()
    {
        return (new self())->getTable();
    }

    public static function getTableFillable()
    {
        $type = (new self())->type ?? '';
        if ($type != 'pivot') {
            $fillable = ['id'];
        } else {
            $fillable = [];
        }
        $dates = (new self())->dates ?? [];
        $users = (new self())->users ?? [];
        $fillable = array_merge($fillable, (new self())->getFillable());
        $fillable = array_merge($fillable, $dates);
        $fillable = array_merge($fillable, $users);
        return $fillable;
    }

    public static function getTableForeignKey()
    {
        return Str::singular((new self())->getTable()) . '_id';
    }

    public static function getTablePrimaryKey()
    {
        return (new self())->getTable() . '.id';
    }

    public function scopeSelectGroup($query, $payload, $alias)
    {
        return $query->selectRaw('GROUP_CONCAT( ' . $payload . ' ) as ' . $alias);
    }

    public static function getTableModel($table)
    {
        return $table !== null  ? 'App\Models\\' . ucfirst(Str::singular($table)) : (new self())::class;
    }
}
