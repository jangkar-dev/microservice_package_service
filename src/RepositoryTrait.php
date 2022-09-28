<?php

namespace App\Traits;

use App\Http\Requests\FormRequest;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function executeStore($request)
    {
        $words = $this->getModuleName();
        DB::beginTransaction();
        try {
            $this->store($request);
            DB::commit();
            return ResponseService::json($words, Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            report($th);
            DB::rollBack();
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }

    public function executeUpdate($request)
    {
        $words = 'Update ' . $this->getModuleName();
        DB::beginTransaction();
        try {
            $this->update($request);
            DB::commit();
            return ResponseService::json($words, Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }

    public function executeDestroy($request)
    {
        $words = 'Delete ' . $this->getModuleName();
        DB::beginTransaction();
        try {
            $this->destroy($request);
            DB::commit();
            return ResponseService::json($words, Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }

    public static function make($request)
    {
        $request['created_by']  = $request->get('created_by') ?? config('user')->id;
        $request['updated_by']  = $request->get('updated_by') ?? config('user')->id;
        return (new static)->actionSetter('store', $request);
    }

    public function fill($request)
    {
        $request['updated_by']  = $request->get('updated_by') ?? config('user')->id;
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
        return $this->$execute($this->request);
    }

    public function delete()
    {
        $execute = 'execute' . ucwords('destroy');
        return $this->$execute($this->request);
    }
}
