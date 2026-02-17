<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface ServiceContract
{
    public function create(array $attributes): Model;

    public function all(
        array $columns = ['*'],
        array $relations = [],
        array $orderBy = []
    ): Collection;

    public function find(
        int|string $id,
        array $columns = ['*'],
        array $relations = []
    ): ?Model;

    public function findOrFail(
        int|string $id,
        array $columns = ['*'],
        array $relations = []
    ): Model;

    public function findBy(
        array $conditions,
        array $columns = ['*'],
        array $relations = []
    ): ?Model;

    public function getBy(
        array $conditions = [],
        array $columns = ['*'],
        array $relations = [],
        array $orderBy = []
    ): Collection;

    public function paginate(
        int $perPage = 15,
        array $conditions = [],
        array $columns = ['*'],
        array $relations = [],
        array $orderBy = []
    ): LengthAwarePaginator;

    public function update(int|string $id, array $attributes): Model;

    public function delete(int|string $id): bool;
}
