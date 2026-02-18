<?php

namespace App\Modules\Company\Domain\Context;

use App\Modules\Company\Domain\Models\Company;
use Closure;

class CompanyContext
{
    protected ?Company $current = null;

    public function set(Company $company): void
    {
        $this->current = $company;
    }

    public function get(): ?Company
    {
        return $this->current;
    }

    public function id(): ?int
    {
        return $this->current?->id;
    }

    public function clear(): void
    {
        $this->current = null;
    }

    public function runFor(Company $company, Closure $callback): mixed
    {
        $this->set($company);

        try {
            return $callback($company);
        } finally {
            $this->clear();
        }
    }

    public function runForAll(Closure $callback, int $chunkSize = 100): void
    {
        Company::query()->chunk($chunkSize, function ($companies) use ($callback): void {
            foreach ($companies as $company) {
                $this->runFor($company, $callback);
            }
        });
    }
}
