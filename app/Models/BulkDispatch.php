<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkDispatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'idempotency_key',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
