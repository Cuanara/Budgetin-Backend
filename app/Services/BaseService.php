<?php

namespace App\Services;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
abstract class BaseService
{
    /** @var BaseRepositoryInterface<TModel> */
    protected BaseRepositoryInterface $repository;

    /**
     * @param  BaseRepositoryInterface<TModel>  $repository
     */
    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function getAll(int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * @return TModel
     */
    public function getById(string $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function update(string $id, array $data): Model
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }
}