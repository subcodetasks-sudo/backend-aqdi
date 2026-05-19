<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait AdminSearchableContract
{
    /**
     * Admin list search across all `contracts` columns plus linked user / status / payments.
     */
    public function scopeAdminSearch(Builder $query, ?string $term): Builder
    {
        $term = is_string($term) ? trim($term) : '';
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like, $term) {
            foreach (static::adminSearchableContractColumns() as $column) {
                $q->orWhere($column, 'like', $like);
            }

            if (ctype_digit($term)) {
                $id = (int) $term;
                $q->orWhere($q->getModel()->getQualifiedKeyName(), $id);
            }

            $q->orWhereHas('user', function (Builder $uq) use ($like) {
                $uq->where('name', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });

            $q->orWhereHas('contractStatus', function (Builder $sq) use ($like) {
                $sq->where('name', 'like', $like);
            });

            $q->orWhereHas('contractPayments', function (Builder $pq) use ($like) {
                $pq->where('amount', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhere('payment_method', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('contract_uuid', 'like', $like);
            });

            $q->orWhereHas('receivedContract.employee', function (Builder $eq) use ($like) {
                $eq->where('name', 'like', $like);
            });
        });
    }

    /**
     * @return list<string>
     */
    public static function adminSearchableContractColumns(): array
    {
        static $columns = null;

        if ($columns !== null) {
            return $columns;
        }

        $instance = new static;
        $columns = Schema::getColumnListing($instance->getTable());

        return $columns;
    }
}
