<?php

namespace App\Services;

use App\Models\Bridging;
use Illuminate\Database\Query\Builder;https://github.com/jangkar-dev/microservice_package_service/blob/main/src/BridgingService.php
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BridgingService
{
    /**
     * get id from db shared with provided table model
     */
    public static function getBridgingId(string $model, Collection | array | int $id): Collection | Builder
    {
        $id = $id instanceof Collection ? $id->toArray() : $id;
        return  DB::connection('shared')->table('bridgings')
            ->select('id', 'vendor_primary_id')
            ->where('model',  $model)
            ->when(is_array($id), function ($q) use ($id) {
                return $q->whereIn('vendor_primary_id', $id)->get()->pluck('id');
            }, function ($q) use ($id) {
                return $q->where('vendor_primary_id', $id)->first()->id;
            });
    }

    /**
     * get id from db shared with provided table model
     */
    public static function getBridgedId(string $model): Collection
    {
        return Bridging::select('vendor_primary_id as id')
        ->where('model', $model)
            ->get()
            ->pluck('id');
    }
}
