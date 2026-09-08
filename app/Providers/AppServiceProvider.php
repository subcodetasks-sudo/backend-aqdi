<?php

namespace App\Providers;

use App\Interfaces\PaymentGatewayInterface;
use App\Modules\Employees\Models\Employee;
use App\Routing\UnicodeJsonResponseFactory;
use App\Services\MoyasarPaymentService;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ResponseFactoryContract::class, function ($app) {
            return new UnicodeJsonResponseFactory(
                $app->make(ViewFactory::class),
                $app->make(Redirector::class)
            );
        });

        $this->app->alias(ResponseFactoryContract::class, 'Illuminate\Routing\ResponseFactory');

        $this->app->bind(PaymentGatewayInterface::class, MoyasarPaymentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'employee' => Employee::class,
            'App\\Models\\Employee' => Employee::class,
            'App\\Modules\\Employees\\Models\\Employee' => Employee::class,
        ]);

        Validator::extend('valid_contract_start_date', function ($attribute, $value, $parameters, $validator) {
            $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $value);

            return $startDate->gte(now()->subDays(280));
        });
    }
}

