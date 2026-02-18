<?php

namespace App\Modules\Company\Domain\Jobs;

use App\Modules\Company\Domain\Context\CompanyContext;
use App\Modules\Company\Domain\Models\Company;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class CompanyAwareJob implements ShouldQueue
{
    public function __construct(
        protected int $companyId
    ) {
    }

    public function handle(CompanyContext $context): void
    {
        $company = Company::query()->withoutGlobalScopes()->findOrFail($this->companyId);

        $context->runFor($company, function (): void {
            $this->execute();
        });
    }

    abstract protected function execute(): void;
}
