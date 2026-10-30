<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BumpOrderCacheLog extends Model
{
    use HasUuids;

    protected $table = 'bump_order_cache_logs';

    protected $fillable = ['bump_order_cache_id', 'status', 'payment_status', 'source', 'note'];
}
