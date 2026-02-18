<?php

namespace App\Modules\User\Domain\Services;

use App\Models\User;
use App\Modules\Company\Domain\Scopes\CompanyScope;
use App\Modules\Company\Domain\Services\CompanyService;
use App\Modules\Core\Domain\Contracts\ServiceContract;
use App\Modules\Core\Domain\Traits\ServiceTrait;
use Illuminate\Support\Facades\Hash;

class UserService implements ServiceContract
{
    use ServiceTrait;

    protected string $model = User::class;

    public function register(array $data): User
    {
        $company = app(CompanyService::class)->create($data['company']);

        return $company->users()->createQuietly($data['user']);
    }

    public function login(array $data): User
    {
        $user = User::withoutGlobalScope(CompanyScope::class)->where(['email' => $data['email']])->first();

        if (!$user) {
            throw new \Exception('Invalid credentials');
        }

        if (!Hash::check($data['password'], $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->token = $token;

        return $user;
    }
}