<?php

namespace App\Modules\Company\Domain\Services;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Core\Domain\Contracts\ServiceContract;
use App\Modules\Core\Domain\Traits\ServiceTrait;

class CompanyService implements ServiceContract
{
    use ServiceTrait;

    protected string $model = Company::class;
}