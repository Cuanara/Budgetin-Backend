<?php

namespace App\Providers;

use App\Repositories\AuthRepository;
use App\Repositories\BudgetRepository;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\BudgetRepositoryInterface;
use App\Repositories\Contracts\EmailVerificationRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\SavingsDepositRepositoryInterface;
use App\Repositories\Contracts\SavingsGoalRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\EmailVerificationRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\SavingsDepositRepository;
use App\Repositories\SavingsGoalRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AuthRepositoryInterface::class => AuthRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        TransactionRepositoryInterface::class => TransactionRepository::class,
        BudgetRepositoryInterface::class => BudgetRepository::class,
        NotificationRepositoryInterface::class => NotificationRepository::class,
        SavingsGoalRepositoryInterface::class => SavingsGoalRepository::class,
        SavingsDepositRepositoryInterface::class => SavingsDepositRepository::class,
        EmailVerificationRepositoryInterface::class => EmailVerificationRepository::class,
    ];
}
