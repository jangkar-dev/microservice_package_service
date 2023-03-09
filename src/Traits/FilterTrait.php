<?php

namespace jangkardev\Traits;

use jangkardev\Services\FilterService;

trait FilterTrait
{
    public function scopeWhereKeywords($query, $fields, $keyword)
    {
        return $query->where(function ($q) use ($keyword, $fields) {
            foreach ($keyword as $search_key) {
                foreach ($fields as $field) {
                    $q = $q->when($search_key <> '' && $field <> '', function ($q) use ($field, $search_key) {
                        return $q->orWhere($field, 'LIKE', '%' . $search_key . '%');
                    });
                }
            }
        });
    }
    public function scopeSearch($query, $fields, $keyword)
    {
        $fields = FilterService::mustArray($fields);
        $keyword = FilterService::mustArray($keyword);
        return $query->when(!empty($keyword) && !empty($fields), function ($q) use ($keyword, $fields) {
            return $q->whereKeywords($fields, $keyword);
        });
    }
}
