<?php

namespace App\Traits;

use App\Http\Requests\FormRequest;
use App\Models\Bridging;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

trait RepositoryTrait
{
    public $action = '';
    public $request = [];

    public static function query()
    {
        $class = __CLASS__;
        return new $class();
    }

    public static function model(Model $model)
    {
        return (new static)->setModel($model);
    }

    public function compact($request)
    {
        $this->request = $request;
        return $this;
    }

    public static function request($request)
    {
        $request = is_array($request) ? new Request($request) : $request;
        $per_page = $request->get('per_page') ?? null;
        $request = self::rule($request);
        return (new static)->setRequest($request, $per_page);
    }

    public function setRequest($request, $per_page = null)
    {
        $this->model->per_page_request = $per_page;
        $this->request = $request;
        return $this->execute();
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function execute()
    {
        return $this->index();
    }

    public function collection()
    {
        return $this->model;
    }

    public function get()
    {
        return $this->request([])->get();
    }

    public function find($payload)
    {
        return $this->request([])->find($payload);
    }

    public function getModuleName()
    {
        $string = (new \ReflectionClass($this))->getShortName();
        $words = preg_replace('/(?<!\ )[A-Z]/', '$0', $string);
        $words = str_replace("Repository", "", $words);
        return $words;
    }

    public function executeStore()
    {
        $words = $this->getModuleName();
        DB::beginTransaction();
        try {
            $stored = $this->store();
            DB::commit();
            return ResponseService::json($words, Response::HTTP_CREATED, $stored);
        } catch (\Throwable $th) {
            report($th);
            DB::rollBack();
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }

    public function executeUpdate()
    {
        $words = 'Update ' . $this->getModuleName();
        DB::beginTransaction();
        try {
            $updated = $this->update();
            DB::commit();
            return ResponseService::json($words, Response::HTTP_OK, $updated);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }


    public function executeReplace()
    {
        $words = 'Replace ' . $this->getModuleName();
        DB::beginTransaction();
        try {
            $updated = $this->replace();
            DB::commit();
            return ResponseService::json($words, Response::HTTP_OK, $updated);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }

    public function executeDestroy()
    {
        $words = 'Delete ' . $this->getModuleName();
        DB::beginTransaction();
        try {
            $this->destroy();
            DB::commit();
            return ResponseService::json($words, Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }

    public static function make($request)
    {
        if (!is_array($request)) {
            $request = $request->validated();
        }
        $default_user  = config('user')->id ?? 1;
        $request['created_by']  = $request['created_by'] ?? $default_user;
        $request['updated_by']  = $request['updated_by'] ?? $default_user;
        return (new static)->actionSetter('store', $request);
    }

    public function fill($request)
    {
        if (!is_array($request)) {
            $request = $request->validated();
        }
        $default_user  = config('user')->id ?? 1;
        $request['updated_by']  = $request['updated_by'] ?? $default_user;
        return $this->actionSetter('update', $request);
    }

    public function actionSetter($payload, $request)
    {
        $this->request = $request;
        $this->action = $payload;
        return $this;
    }

    public function save()
    {
        $execute = 'execute' . ucwords($this->action);
        return $this->$execute();
    }

    public function delete()
    {
        $execute = 'execute' . ucwords('destroy');
        return $this->$execute($this->request);
    }

    public function replace()
    {
        $request = $this->request;
        $moduleName = $this->getModuleName();
        $bridging = $request['bridging'] ?? [];
        $data = Bridging::$moduleName()
            ->where('vendor_primary_id', $bridging['vendor_primary_id'])
            ->first();
        if ($data) {
            $this->model = $this->model->find($data->id);
            return $this->executeUpdate();
        }
        return $this->executeStore();
    }
}
