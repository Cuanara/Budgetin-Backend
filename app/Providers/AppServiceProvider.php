<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

use App\Repositories\TransactionRepository;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\SavingsGoalRepository;
use App\Repositories\SavingsDepositRepository;
use App\Repositories\Contracts\SavingsGoalRepositoryInterface;
use App\Repositories\Contracts\SavingsDepositRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TransactionRepositoryInterface::class,
            TransactionRepository::class
        );

        $this->app->bind(
            SavingsGoalRepositoryInterface::class,
            SavingsGoalRepository::class
        );

        $this->app->bind(
            SavingsDepositRepositoryInterface::class,
            SavingsDepositRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
