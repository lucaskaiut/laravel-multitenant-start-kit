<?php

namespace App\Modules\Company\Domain\Jobs;

class ExampleCompanyJob extends CompanyAwareJob
{
    protected function execute(): void
    {
        // Job logic in company context: reports, sync, notifications, etc.
        // Models with HasCompany and CompanyScope use the context set by CompanyAwareJob automatically.
    }
}
