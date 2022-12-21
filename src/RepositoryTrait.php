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
use InvalidArgumentException;
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

    // This function receives a request and sets the pagination limit based on the "per_page" parameter.
    // It also applies a set of rules to the request and returns a new instance of the current class
    // with the modified request and pagination limit.
    public static function request(Collection | array | HttpFormRequest | Request $request)
    {
        // If the request is a Collection, extract the data from the collection.
        // Otherwise, assume it is an array and use it as-is.
        // If the request is neither a Collection nor an array, throw an error.
        if ($request instanceof Collection) {
            $requestData = $request->all();
        } elseif ($request instanceof HttpFormRequest) {
            $requestData = $request->validated();
        } elseif ($request instanceof Request) {
            $requestData = $request->all();
        } elseif (is_array($request)) {
            $requestData = $request;
        } else {
            throw new InvalidArgumentException('Invalid request data. Expected a Collection or an array or HttpFormRequest or Request.');
        }

        // Get the pagination limit from the request data, or use a default value of null.
        $perPage = $requestData['per_page'] ?? $request->per_page ?? null;

        // Apply the rules to the request data and get the filtered request.
        $filteredRequest = self::rule($requestData);

        // Return a new instance of the current class with the modified request and pagination limit.
        return (new static)->setRequest($filteredRequest, $perPage);
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

    public static function get(): collection
    {
        return self::request([])->get();
    }

    public function find($payload): Collection | Model
    {
        return $this->request([])->find($payload);
    }

    public function getModuleName(): String
    {
        // This function gets the short name of the current class (without the "Repository" part)
        // and adds a space before each uppercase letter to make it more readable.
        // For example, the class "MyModuleRepository" would become "My Module".
        $reflection = new \ReflectionClass($this);
        $shortName = $reflection->getShortName();
        $words = preg_replace('/(?<!\ )[A-Z]/', '$0', $shortName);
        $words = str_replace("Repository", "", $words);
        return $words;
    }

    public function storeRule(): void
    {
    }

    public function updateRule(): void
    {
    }

    public function execute(callable $callback, string $action = ''): JsonResponse
    {
        $words = 'Some operation on ' . $this->getModuleName();
        Log::debug($action);
        DB::beginTransaction();
        $response = $action == 'executeStore' ? Response::HTTP_CREATED : Response::HTTP_OK;
        try {
            $result = $callback();
            DB::commit();
            return ResponseService::json($words, $response, $result);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            return ResponseService::json($words, Response::HTTP_BAD_REQUEST);
        }
    }

    public function executeStore(): JsonResponse
    {
        return $this->execute(function () {
            $this->storeRule();
            return $this->store();
        }, __FUNCTION__);
    }

    public function executeUpdate(): JsonResponse
    {
        return $this->execute(function () {
            $this->updateRule();
            return $this->update();
        });
    }

    public function executeReplace(): JsonResponse
    {
        return $this->execute(function () {
            return $this->replace();
        });
    }

    public function executeDestroy(): JsonResponse
    {
        return $this->execute(function () {
            $this->destroy();
        });
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

    public function without(array $without = []): self
    {
        $this->without = $without;
        return $this;
    }

    public function bridging(): Model|Collection
    {
        $request = $this->request;
        $bridging = $request['bridging'] ?? [];
        $moduleName = $this->getModuleName();

        // Check if the bridging data already exists
        $data = Bridging::where('model', 'App\Models\\' . $moduleName)
            ->where('vendor_primary_id', $bridging['vendor_primary_id'])
            ->first();

        // If the data exists, update it and return the updated model
        if ($data) {
            $this->model = $this->model->find($data->id);
            $this->executeUpdate();
            return $this->model;
        }

        // If the data does not exist, start a database transaction
        DB::beginTransaction();

        try {
            // Store the rule and model
            $this->storeRule();
            $this->store();

            // If bridging data is provided, save it
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

            // If everything goes well, commit the transaction and return the model
            DB::commit();
            return $this->model;
        } catch (\Throwable $th) {
            // If an error occurs, roll back the transaction and return the model
            report($th);
            DB::rollBack();
            return $this->model;
        }
    }
}
