<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Domain\Services\CompanyService;
use App\Modules\Company\Http\Requests\CompanyRequest;
use App\Modules\Company\Http\Resources\CompanyResource;
use Illuminate\Http\Request;
use App\Modules\Core\Http\Traits\ControllerTrait;

class CompanyController extends Controller
{
    use ControllerTrait;

    protected string $service = CompanyService::class;
    protected string $resource = CompanyResource::class;
    protected string $request = CompanyRequest::class;
}
