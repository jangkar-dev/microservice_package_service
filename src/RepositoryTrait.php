<?php

namespace App\Traits;

use App\Http\Requests\FormRequest;
use App\Models\Bridging;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest as HttpFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

trait RepositoryTrait
{
    protected $action = '';
    protected $request = [];
    protected $without = [];

    public static function query(): self
    {
        $class = __CLASS__;
        return new $class();
    }

    public static function model($model = null): self
    {
        return (new static)->setModel($model);
    }

    public function compact($request): self
    {
        $this->request = $request;
        return $this;
    }

    public static function request($request)
    {
        $request = is_array($request) ? $request : $request->all();
        $per_page = $request['per_page'] ?? null;
        $request = self::rule($request);
        return (new static)->setRequest($request, $per_page);
    }

    public function setRequest($request, $per_page = null)
    {
        $this->model->per_page_request = $per_page;
        $this->request = $request;
        return $this->executeIndex();
    }

    public function setModel($model): self
    {
        if ($model !== null) {
            if (is_numeric($model)) {
                $this->model = $this->model->find($model);
            } else {
                $this->model = $model;
            }
        }
        return $this;
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function executeIndex(): collection | Model | Builder
    {
        return $this->index();
    }

    public function collection(): collection | Model | Builder
    {
        return $this->model;
    }

    public function get(): collection
    {
        return $this->request([])->get();
    }

    public function find($payload): Collection | Model
    {
        return $this->request([])->find($payload);
    }

    public function getModuleName(): String
    {
        $string = (new \ReflectionClass($this))->getShortName();
        $words = preg_replace('/(?<!\ )[A-Z]/', '$0', $string);
        $words = str_replace("Repository", "", $words);
        return $words;
    }

    public function executeStore(): JsonResponse
    {
        $words = $this->getModuleName();
        DB::beginTransaction();
        try {
            $this->storeRule();
            $stored = $this->store();
            DB::commit();
            return ResponseService::json($words, Response::HTTP_CREATED, $stored);
        } catch (\Throwable $th) {
            report($th);
            DB::rollBack();
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }

    public function storeRule(): void
    {
    }

    public function executeUpdate(): JsonResponse
    {
        $words = 'Update ' . $this->getModuleName();
        DB::beginTransaction();
        try {
            $this->updateRule();
            $updated = $this->update();
            DB::commit();
            return ResponseService::json($words, Response::HTTP_OK, $updated);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateRule(): void
    {
    }

    public function executeReplace(): JsonResponse
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

    public function executeDestroy(): JsonResponse
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

    /**
     * > This function creates a new instance of the model and sets the `created_by` and `updated_by`
     * fields to the user id of the user who created the record
     * @param \Illuminate\Http\Request|array|\Illuminate\Foundation\Http\FormRequest $request
     * @return self A new instance of the class with the action set to store and the request set to the request
     */
    public static function make(Request | array | HttpFormRequest | Collection $request): self
    {
        if (!is_array($request)) {
            $request = $request->validated();
        }
        $default_user  = config('user')->id ?? 1;
        $request['created_by']  = $request['created_by'] ?? $default_user;
        $request['updated_by']  = $request['updated_by'] ?? $default_user;
        return (new static)->actionSetter('store', $request);
    }

    /**
     * > This function creates a new instance of the model and sets the `created_by` and `updated_by`
     * fields to the user id of the user who created the record
     * @param \Illuminate\Http\Request|array|\Illuminate\Foundation\Http\FormRequest $request
     * @return self A new instance of the class with the action set to store and the request set to the request
     */
    public static function batch(Request | array | HttpFormRequest | Collection $request): self
    {
        if (!is_array($request)) {
            $request = $request->validated();
        }
        return (new static)->actionSetter('batch', $request);
    }

    public function fill(Request | array | HttpFormRequest | Collection  $request): self
    {
        if (!is_array($request)) {
            $request = $request->validated();
        }
        $default_user  = config('user')->id ?? 1;
        $request['updated_by']  = $request['updated_by'] ?? $default_user;
        return $this->actionSetter('update', $request);
    }

    public function actionSetter(String $payload, Request | array | HttpFormRequest | Collection  $request)
    {
        $this->request = $request;
        $this->action = $payload;
        return $this;
    }

    public function save(): JsonResponse
    {
        $execute = 'execute' . ucwords($this->action);
        return $this->$execute();
    }

    public function delete(): JsonResponse
    {
        $execute = 'execute' . ucwords('destroy');
        return $this->$execute($this->request);
    }

    public function without(array $without = []): self
    {
        $this->without = $without;
        return $this;
    }

   public function bridging(): Model | Collection
    {
        $request = $this->request;
        $bridging = $request['bridging'] ?? [];
        $moduleName = $this->getModuleName();
        $data = Bridging::where('model', 'App\Models\\' . $moduleName)
            ->where('vendor_primary_id', $bridging['vendor_primary_id'])
            ->first();
        if ($data) {
            $this->model = $this->model->find($data->id);
            $this->executeUpdate();
            return $this->model;
        }
        DB::beginTransaction();
        try {
            $this->store();
            $bridging = $request['bridging'] ?? [];
            if (!empty($bridging)) {
                $this->model->hasOne(Bridging::class, 'id')->save(
                    new Bridging([
                        'id' => $this->model->id,
                        'model' => 'App\Models\\' . $moduleName,
                        'vendor_id' => $bridging['vendor_id'],
                        'vendor_primary_id' => $bridging['vendor_primary_id'],
                    ])
                );
            }
            DB::commit();
            return $this->model;
        } catch (\Throwable $th) {
            report($th);
            DB::rollBack();
            return $this->model;
        }
    }
}
