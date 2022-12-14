<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Helper;

class BridgingService
{
    private int $bridgingStarted = 0;
    /**
     * get id from db shared with provided table model
     */
    public static function getBridgingId(string $model, Collection | array | int $id): Collection
    {
        $id = Helper::isCollection($id) ? $id->toArray() : $id;
        return  DB::connection('shared')->table('bridgings')
            ->select('id', 'vendor_primary_id')
            ->where('model',  $model)
            ->when(is_array($id), function ($q) {
                return $q->whereIn('vendor_primary_id', $id)->get()->pluck('id');
            }, function ($q) {
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

    /* Updating the bridging_histories table with the current time. */
    public function close(int $bridgingStarted = null): self
    {
        $bridgingStarted = $bridgingStarted ?? $this->bridgingStarted;
        DB::connection('shared')
            ->table('bridging_histories')
            ->where('id', $bridgingStarted)
            ->update([ 'finished_at' => Carbon::now()]);
        return $this;
    }
    
    public function start(): self
    {
        $bridgingStarted = DB::connection('shared')
            ->table('bridging_histories')
            ->insertGetId(['model' => Brand::class]);
        $this->bridgingStarted = $bridgingStarted;
        return $this;
    }
}
