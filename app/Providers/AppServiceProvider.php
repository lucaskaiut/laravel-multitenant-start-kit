<?php

namespace App\Providers;

use App\Modules\Company\Domain\Context\CompanyContext;
use App\Modules\Company\Domain\Singletons\CompanySingleton;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CompanyContext::class, function () {
            return new CompanyContext();
        });

        $this->app->scoped('company', function ($app) {
            return new CompanySingleton($app->make(CompanyContext::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
