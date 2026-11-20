<?php

namespace App\Providers;

use App\Services\Search\AuditSearchSource;
use App\Services\Search\ClientSearchSource;
use App\Services\Search\EnquirySearchSource;
use App\Services\Search\FeeScheduleSearchSource;
use App\Services\Search\GlobalSearch;
use App\Services\Search\IndustryUnitSearchSource;
use App\Services\Search\QuotationSearchSource;
use App\Services\Search\ServiceSearchSource;
use App\Services\Search\UserSearchSource;
use App\Services\Search\WorkOrderSearchSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([
            UserSearchSource::class,
            ClientSearchSource::class,
            IndustryUnitSearchSource::class,
            EnquirySearchSource::class,
            WorkOrderSearchSource::class,
            QuotationSearchSource::class,
            ServiceSearchSource::class,
            FeeScheduleSearchSource::class,
            AuditSearchSource::class,
        ], 'search.sources');

        $this->app->singleton(GlobalSearch::class, function ($app) {
            return new GlobalSearch($app->tagged('search.sources'));
        });
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers());

        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
