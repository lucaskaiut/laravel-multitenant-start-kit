<?php

namespace App\Modules\Company\Http\Middlewares;

use App\Modules\Company\Domain\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InitializeCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $this->authenticateIfTokenPresent($request);

        $company = $this->company($request);

        throw_if(!$company, new NotFoundHttpException('Company not found'));

        $context = app(\App\Modules\Company\Domain\Context\CompanyContext::class);
        $context->set($company);

        try {
            return $next($request);
        } finally {
            $context->clear();
        }
    }

    private function authenticateIfTokenPresent(Request $request): void
    {
        if ($request->bearerToken() && !Auth::check()) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            
            if ($token && $user = $token->tokenable) {
                Auth::setUser($user);
            }
        }
    }

    private function company(Request $request): ?Company
    {
        if ($user = Auth::user()) {
            return $user->company;
        }

        return null;
    }
}