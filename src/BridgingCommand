<?php

namespace App\Console\Commands;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BridgingCommand extends Command
{
    protected $model = '';
    protected $bridgingStarted = 0;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bridging';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function start(): self
    {
        $this->bridgingStarted = DB::connection('shared')
            ->table('bridging_histories')
            ->insertGetId(['model' => $this->model]);
        return $this;
    }
    public function close(): self
    {
        DB::connection('shared')
            ->table('bridging_histories')
            ->where('id', $this->bridgingStarted)
            ->update([
                'finished_at' => Carbon::now(),
            ]);
        return $this;
    }

    public function clearBridged(array $truncatedTables, array $models)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($truncatedTables as $truncatedTable) {
            DB::table($truncatedTable)->truncate();
        }
        $truncatedSharers = [
            'bridgings',
            'bridging_histories',
        ];
        foreach ($truncatedSharers as $truncatedShare) {
            DB::connection('shared')->table($truncatedShare)->whereIn('model', $models)->delete();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function callBridging(callable $callback, callable $callbackChain = null)
    {
        $callbackChain();
        Log::debug('Bridging '.$this->model.' Running!!');
        $yesterday = Carbon::yesterday();
        $bridgingHistories = DB::connection('shared')
            ->table('bridging_histories')
            ->where('model', $this->model)
            ->where('created_at', '>', $yesterday)
            ->count();
        if ($bridgingHistories > 5) {
            Log::debug('Bridging '.$this->model.' Has Try Five Times!!');
            Log::debug('Bridging '.$this->model.' Stopped!!');
            return;
        };
        $this->start();
        try {
            $callback();
        } catch (\Throwable $th) {
            report($th);
            Log::debug('Bridging '.$this->model.' Retried!!');
            $this->close();
        }
        $this->close();
        $this->output->progressFinish();
        Log::debug('Bridging '.$this->model.' Finished!!');
    }
}
