<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface BaseRepositoryInterface
{
    /**
     * @return Collection<int, TModel>
     */
    public function all(): Collection;

    /**
     * @param  array<int, string>  $columns
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 10, array $columns = ['*']): LengthAwarePaginator;

    /**
     * @return TModel|null
     */
    public function find(string $id): ?Model;

    /**
     * @return TModel
     */
    public function findOrFail(string $id): Model;

    /** @return TModel|null */
    public function findBy(string $column, mixed $value): ?Model;

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data): Model;

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function update(string $id, array $data): Model;

    public function delete(string $id): bool;
}
