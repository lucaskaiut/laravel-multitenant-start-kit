<?php

declare(strict_types=1);

namespace App\Services\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait ServiceTrait
{
    /**
     * The concrete service must define:
     *
     * protected Model $model;
     */
    protected Model $model;

    protected function newQuery(array $relations = []): Builder
    {
        return $this->model
            ->newQuery()
            ->with($relations);
    }

    public function create(array $attributes): Model
    {
        return $this->model->create($attributes);
    }

    public function all(
        array $columns = ['*'],
        array $relations = [],
        array $orderBy = []
    ): Collection {
        $query = $this->newQuery($relations);

        $this->applyOrdering($query, $orderBy);

        return $query->get($columns);
    }

    public function find(
        int|string $id,
        array $columns = ['*'],
        array $relations = []
    ): ?Model {
        return $this->newQuery($relations)->find($id, $columns);
    }

    public function findOrFail(
        int|string $id,
        array $columns = ['*'],
        array $relations = []
    ): Model {
        return $this->newQuery($relations)->findOrFail($id, $columns);
    }

    public function findBy(
        array $conditions,
        array $columns = ['*'],
        array $relations = []
    ): ?Model {
        $query = $this->newQuery($relations);

        $this->applyConditions($query, $conditions);

        return $query->first($columns);
    }

    public function getBy(
        array $conditions = [],
        array $columns = ['*'],
        array $relations = [],
        array $orderBy = []
    ): Collection {
        $query = $this->newQuery($relations);

        $this->applyConditions($query, $conditions);
        $this->applyOrdering($query, $orderBy);

        return $query->get($columns);
    }

    public function paginate(
        int $perPage = 15,
        array $conditions = [],
        array $columns = ['*'],
        array $relations = [],
        array $orderBy = []
    ): LengthAwarePaginator {
        $query = $this->newQuery($relations);

        $this->applyConditions($query, $conditions);
        $this->applyOrdering($query, $orderBy);

        return $query->paginate($perPage, $columns);
    }

    public function update(int|string $id, array $attributes): Model
    {
        $model = $this->findOrFail($id);

        $model->update($attributes);

        return $model->fresh();
    }

    public function delete(int|string $id): bool
    {
        $model = $this->findOrFail($id);

        return (bool) $model->delete();
    }

    protected function applyConditions(Builder $query, array $conditions): void
    {
        foreach ($conditions as $field => $value) {
            if (is_array($value) && count($value) === 2) {
                [$operator, $val] = $value;
                $query->where($field, $operator, $val);
                continue;
            }

            $query->where($field, '=', $value);
        }
    }

    protected function applyOrdering(Builder $query, array $orderBy): void
    {
        foreach ($orderBy as $field => $direction) {
            $query->orderBy($field, $direction);
        }
    }
}
