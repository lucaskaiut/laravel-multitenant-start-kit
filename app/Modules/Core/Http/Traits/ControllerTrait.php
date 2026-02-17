<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

trait ControllerTrait
{
    /**
     * The concrete controller must define:
     *
     * protected string $service;   // Service class name (FQCN)
     * protected string $resource;  // JsonResource class name (FQCN)
     * protected string $request;   // FormRequest class name (FQCN) for store/update
     */

    protected function service(): object
    {
        return app($this->service);
    }

    protected function db(): DatabaseManager
    {
        return app(DatabaseManager::class);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = (array) $request->query('filters', []);
        $orderBy = (array) $request->query('orderBy', []);
        $perPage = (int) $request->query('per_page', 15);

        $result = $this->service()->paginate(
            perPage: $perPage,
            conditions: $filters,
            orderBy: $orderBy
        );

        return $this->respondWithCollection($result);
    }

    public function show(int|string $id): JsonResponse
    {
        /** @var Model $model */
        $model = $this->service()->findOrFail($id);

        return $this->respondWithItem($model);
    }

    public function store(): JsonResponse
    {
        $validated = app($this->request)->validated();

        $model = $this->db()->transaction(
            fn () => $this->service()->create($validated)
        );

        return $this->respondWithItem($model, 201);
    }

    public function update(int|string $id): JsonResponse
    {
        $validated = app($this->request)->validated();

        $model = $this->db()->transaction(
            fn () => $this->service()->update($id, $validated)
        );

        return $this->respondWithItem($model);
    }

    public function destroy(int|string $id): JsonResponse
    {
        $this->db()->transaction(
            fn () => $this->service()->delete($id)
        );

        return response()->json(null, 204);
    }

    protected function respondWithItem(Model $model, int $status = 200): JsonResponse
    {
        /** @var JsonResource $resource */
        $resource = new $this->resource($model);

        return $resource
            ->response()
            ->setStatusCode($status);
    }

    protected function respondWithCollection(
        LengthAwarePaginator|\Illuminate\Support\Collection $data
    ): JsonResponse {
        $resource = $this->resource;

        return $resource::collection($data)->response();
    }
}
