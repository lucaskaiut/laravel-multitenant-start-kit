<?php

namespace App\Modules\Company\Domain\Singletons;

use App\Modules\Company\Domain\Context\CompanyContext;
use App\Modules\Company\Domain\Models\Company;

class CompanySingleton
{
    public function __construct(
        protected CompanyContext $context
    ) {
    }

    public function registerCompany(Company $company): void
    {
        $this->context->set($company);
    }

    public function company(): ?Company
    {
        return $this->context->get();
    }
}