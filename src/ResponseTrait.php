<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Http\Resources\MissingValue;

trait ResponseTrait
{
    public function whenJoined($table)
    {
        $default = new MissingValue;
        $parameter =  $this->{$table . '.id'} ?? null;
        $model = Str::singular($table);
        $model = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $table))));
        if ($parameter !== null) {
            $Table = 'App\Models\\' . ucwords($model);
            $fillable =  $Table::getTableFillable();
            $resource = [];
            foreach ($fillable as  $row) {
                $resource[$row] = $this->{$table . '.' . $row};
            }
            return (object)$resource;
        } else {
            return $default;
        }
    }

    public function whenHasRelationship($table)
    {
        $resource = new MissingValue;
        $parameter =  $this->{$table . '.id'} ?? null;
        if ($parameter === null) {
            $test = false;
            $lele = $this->$table ?? null;
            if ($lele === null) {
                return $resource;
            } else {
                if ($this->relationLoaded($table)) {
                    $test = true;
                }
                if (method_exists($this, $table)) {
                    $test = true;
                }
                if ($test) {
                    $resource = $this->whenLoaded($table);
                }
            }
        } else {
            $resource = $this->whenJoined($table);
        }
        return $resource;
    }
}
