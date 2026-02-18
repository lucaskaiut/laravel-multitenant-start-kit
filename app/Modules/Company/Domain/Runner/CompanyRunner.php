<?php

namespace App\Modules\Company\Domain\Runner;

use Closure;
use Illuminate\Support\Facades\App;

class CompanyRunner
{
    /**
     * Runs the callback for each company, in chunks, with context set and cleared
     * after each run. Typical use in Artisan commands or the Scheduler.
     *
     * Example:
     *
     * CompanyRunner::forAll(function () {
     *     app(SomeCompanyDependentService::class)->process();
     * });
     */
    public static function forAll(Closure $callback): void
    {
        $context = App::make(\App\Modules\Company\Domain\Context\CompanyContext::class);
        $context->runForAll($callback);
    }
}
