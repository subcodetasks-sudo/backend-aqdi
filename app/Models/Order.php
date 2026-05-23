<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'amount_payment',
    ];

    /**
     * Match orders.uuid to a contract uuid (exact or with "-{suffix}").
     */
    public function scopeMatchingContractUuid(Builder $query, string|int $contractUuid): Builder
    {
        $uuid = (string) $contractUuid;

        return $query->where(function (Builder $q) use ($uuid) {
            $q->where('uuid', $uuid)
                ->orWhere('uuid', 'like', $uuid.'-%');
        });
    }

    /**
     * Match orders.uuid to contracts.uuid on the parent query (for subqueries/joins).
     */
    public function scopeMatchingContractUuidColumn(Builder $query, string $column = 'contracts.uuid'): Builder
    {
        return $query->where(function (Builder $q) use ($column) {
            $q->whereColumn('orders.uuid', $column)
                ->orWhereRaw("orders.uuid LIKE CONCAT(CAST({$column} AS CHAR), '-%')");
        });
    }
}
