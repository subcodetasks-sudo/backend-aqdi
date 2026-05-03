<?php

namespace App\Providers;
use Illuminate\Support\Facades\Blade;
use Carbon\Carbon;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

    Blade::directive('ifContractOwnership',function($value)
    {
       return "<?php if ($value === 'owner'): ?> المالك <?php else: ?> المستأجر <?php endif; ?>";
        });
        Blade::directive('ifContractType', function ($value) {
        return "<?php if ($value === 'housing'): ?> سكني <?php else: ?> تجاري <?php endif; ?>";
        });
        Blade::directive('ifInstrumentType', function ($expression) {
        return "<?php if($expression === 'electronic'): ?>
        إلكتروني
        <?php elseif($expression === 'old_handwritten'): ?>
        مكتوب بخط اليد<br>
        <?php elseif($expression === 'strong_argument'): ?>
        حجة استحكام
        <?php endif; ?>";
    });
  Validator::extend('valid_contract_start_date', function ($attribute, $value, $parameters, $validator) {
            // Convert the value to a Carbon instance for easier comparison
            $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $value);
            
            // Check if the contract starting date is not more than 280 days ago
            return $startDate->gte(now()->subDays(280));
        });
    
        }
}

