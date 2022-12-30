<?php

namespace App\Console\Commands;

use App\Services\BotService;
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
        if(env('APP_ENV') != 'Production'){
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
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function callBridging(callable $callback, callable $callbackChain = null)
    {
        $callbackChain();
        $botService = new BotService('telegram');
        $startMessage = 'Bridging ' . $this->model . ' Running!!';
        $botService->sticker('CAACAgIAAxkBAAIDa2OuWOxAUOQg2tEZdTULcuedJ1eyAALLEAACg4ihSjNsJ7Kc3_mPLQQ')->send();
        $botService->message($startMessage)->send();
        Log::debug($startMessage);
        $yesterday = Carbon::yesterday();
        $bridgingHistories = DB::connection('shared')
            ->table('bridging_histories')
            ->where('model', $this->model)
            ->where('created_at', '>', $yesterday)
            ->count();
        if ($bridgingHistories > 5) {
            $limitMessage = 'Bridging ' . $this->model . ' Has Try Five Times!!';
            $stopMessage = 'Bridging ' . $this->model . ' Stopped!!';
            $botService->message($limitMessage)->send();
            $botService->message($stopMessage)->send();
            Log::debug($limitMessage);
            Log::debug($stopMessage);
            return;
        };
        $this->start();
        try {
            $callback();
        } catch (\Throwable $th) {
            report($th);
            $retryMessage = 'Bridging ' . $this->model . ' Retried!!';
            $botService->message($retryMessage)->send();
            $botService->message($th)->send();
            Log::debug($retryMessage);
            $this->close();
        }
        $this->close();
        $this->output->progressFinish();
        $finishedMessage = 'Bridging '.$this->model.' Finished!!';
        $botService->message($finishedMessage)->send();
        Log::debug($finishedMessage);
    }
}
