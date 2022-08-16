<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait BuilderTrait
{
    private $cleaner = [];
    private $joinedTable = [];
    public function scopeJoinWith($query, $joiners, $except = [])
    {
        $query->selectBaseTable();
        foreach ($joiners as $joiner) {
            $splits = explode('.', $joiner);
            $counted = count($splits);
            for ($i = 0; $i < $counted; $i++) {
                $base = '';
                $joiner = '';
                if ((isset($splits[$i + 1]) && $i > 0) || ($i + 1 == $counted && $counted > 1)) {
                    $base = $splits[$i - 1];
                    $joiner = $splits[$i];
                } else {
                    $joiner = $splits[$i];
                }
                $connector = 'join' . str_replace("_", "", ucwords($joiner, " /_"));
                if ($base != '') {
                    $Table = 'App\Models\\' . ucwords($base);
                    $base = $Table::getTableName();
                }
                $validator = $base == '' ? $connector : $connector . '(' . $base . ')';
                if (!in_array($validator, $this->cleaner) || $this->cleaner == []) {
                    $query = $base == '' ? $query->$connector() : $query->$connector($base);
                    $this->cleaner[] = $validator;
                    $this->joinedTable[] = $joiner;
                }
            }
        }
        return $query->selectWith();
        return [
            $this->cleaner,
            $this->joinedTable
        ];
    }

    public function scopeSelectBaseTable($query)
    {
        $Table = __CLASS__;
        $fillable =  $Table::getTableFillable();
        $plural = $Table::getTableName();
        foreach ($fillable as $key => $row) {
            $fillable[$key] = $plural . '.' . $row;
        }
        return $query->select($fillable);
    }

    public function toCamelCase($table)
    {
        $table = Str::singular($table);
        $table = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $table))));
        return $table;
    }

    public function scopeSelectWith($query, $except = [])
    {
        $response = [];
        foreach ($this->joinedTable as $key => $table) {
            $table = $this->toCamelCase($table);
            $Table = 'App\Models\\' . $table;
            $fillable =  $Table::getTableFillable();
            $plural = $Table::getTableName();
            $singular = Str::singular($plural);
            if (in_array($singular, $except) || in_array($plural, $except)) {
                continue;
            }
            foreach ($fillable as $key => $row) {
                $fillable[$key] = $plural . '.' . $row . ' as ' . $singular . '.' . $row;
            }
            $response = array_merge($response, $fillable);
        }
        return $query->addSelect($response);
    }

    public function scopeSelectAttribute($query, $relation, $attributes = [])
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
