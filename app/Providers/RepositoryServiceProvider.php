<?php

namespace App\Providers;

use App\Repositories\AuthRepository;
use App\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Daftar binding interface => implementasi.
     * Tambahkan repository lain di sini.
     */
    public array $bindings = [
        AuthRepositoryInterface::class => AuthRepository::class,
    ];
}
