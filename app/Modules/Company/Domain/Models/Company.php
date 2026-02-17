<?php

namespace App\Modules\Company\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
