<?php

namespace App\Providers;

use App\Repositories\AuthRepository;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\TransactionRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Daftar binding interface => implementasi.
     * Tambahkan repository lain di sini.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AuthRepositoryInterface::class => AuthRepository::class,
        TransactionRepositoryInterface::class => TransactionRepository::class,
    ];
}
