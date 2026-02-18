<?php

namespace App\Modules\Core\Domain\Traits;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Company\Domain\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

trait HasCompany
{
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function bootHasCompany(): void
    {
        static::addGlobalScope(new CompanyScope());
        static::creating(function (Model $model) {
            $model->company_id = app('company')->company()?->id;
        });
    }
}