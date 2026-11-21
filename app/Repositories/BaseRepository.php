<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements BaseRepositoryInterface<TModel>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /** @var TModel */
    protected Model $model;

    public function __construct()
    {
        $this->model = app($this->model());
    }

    /**
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    /**
     * @return Collection<int, TModel>
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * @param  array<int, string>  $columns
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(
        int $perPage = 10,
        array $columns = ['*']
    ): LengthAwarePaginator {
        return $this->model
            ->newQuery()
            ->paginate($perPage, $columns);
    }

    /**
     * @return TModel|null
     */
    public function find(string $id): ?Model
    {
        return $this->model
            ->newQuery()
            ->find($id);
    }

    /**
     * @return TModel
     */
    public function findOrFail(string $id): Model
    {
        return $this->model
            ->newQuery()
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data): Model
    {
        return $this->model
            ->newQuery()
            ->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function update(string $id, array $data): Model
    {
        $model = $this->findOrFail($id);

        $model->update($data);

        return $model->refresh();
    }

    public function delete(string $id): bool
    {
        $model = $this->findOrFail($id);

        return $model->delete();
    }

    /**
     * Cari satu data berdasarkan kolom tertentu.
     *
     * @return TModel|null
     */
    public function findBy(string $column, mixed $value): ?Model
    {
        return $this->model
            ->newQuery()
            ->where($column, $value)
            ->first();
    }
}
