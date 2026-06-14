<?php

namespace App\Providers;

use App\Http\Responses\ApprovedDeviceAuthorizationResponse;
use App\Services\ModelCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Contracts\ApprovedDeviceAuthorizationResponse as ApprovedDeviceAuthorizationResponseContract;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ApprovedDeviceAuthorizationResponseContract::class,
            ApprovedDeviceAuthorizationResponse::class,
        );

        $this->app->singleton(
            ModelCatalog::class,
            fn (): ModelCatalog => new ModelCatalog(
                (string) config('pricing.catalog_path'),
            ),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Device authorization (CLI device-code login) views.
        Passport::deviceUserCodeView('oauth.device');
        Passport::deviceAuthorizationView('oauth.authorize');
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
