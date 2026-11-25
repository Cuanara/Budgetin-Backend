<?php

namespace App\Providers;

use App\Repositories\AuthRepository;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\EmailVerificationRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\SavingsGoalRepositoryInterface;
use App\Repositories\Contracts\SavingsDepositRepositoryInterface;
use App\Repositories\EmailVerificationRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\SavingsGoalRepository;
use App\Repositories\SavingsDepositRepository;
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
        UserRepositoryInterface::class => UserRepository::class,
        TransactionRepositoryInterface::class => TransactionRepository::class,
        SavingsGoalRepositoryInterface::class => SavingsGoalRepository::class,
        SavingsDepositRepositoryInterface::class => SavingsDepositRepository::class,
        EmailVerificationRepositoryInterface::class => EmailVerificationRepository::class,
    ];
}
