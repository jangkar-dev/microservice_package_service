<?php

namespace jangkardev\Bridgings\Services;

use App\Models\Bridging;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BridgingService
{
    /**
     * get id from db shared with provided table model
     */
    public static function getBridgingId(string $model, Collection | array | int $id): int | Collection | Builder
    {
        $id = $id instanceof Collection ? $id->toArray() : $id;
        return  DB::connection('shared')->table('bridgings')
            ->select('id', 'vendor_primary_id')
            ->where('model',  $model)
            ->when(is_array($id), function ($q) use ($id) {
                return $q->whereIn('vendor_primary_id', $id)->get()->pluck('id');
            }, function ($q) use ($id) {
                $finded = $q->where('vendor_primary_id', $id)->first();
                if($finded){
                    return $finded->id;
                }
                return 0;
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
