<?php

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Domain\Services\CompanyService;
use App\Modules\User\Domain\Services\UserService;
use App\Modules\User\Http\Resources\UserResource;
use App\Modules\User\Http\Requests\UserRequest;
use App\Modules\Core\Http\Traits\ControllerTrait;
use App\Modules\User\Http\Requests\UserLoginRequest;
use App\Modules\User\Http\Requests\UserRegisterRequest;

class UserController extends Controller
{
    use ControllerTrait;

    protected string $service = UserService::class;
    protected string $resource = UserResource::class;
    protected string $request = UserRequest::class;

    public function register(UserRegisterRequest $request)
    {
        $validated = $request->validated();

        return $this->db()->transaction(function () use ($validated) {  
            $model = $this->service()->register($validated);

            return $this->respondWithItem($model, 201);
        });
    }

    public function login(UserLoginRequest $request)
    {
        $validated = $request->validated();

        return $this->db()->transaction(function () use ($validated) {
            $model = $this->service()->login($validated);

            return $this->respondWithItem($model, 201);
        });
    }
}
