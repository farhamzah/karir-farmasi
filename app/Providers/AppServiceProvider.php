<?php

namespace App\Providers;

use App\Contracts\CoreAlumniGateway;
use App\Contracts\CoreDirectoryGateway;
use App\Contracts\CoreIdentityGateway;
use App\Contracts\CvPdfRenderer;
use App\Cv\ChromiumCvPdfRenderer;
use App\Models\CareerCertification;
use App\Models\CareerEducation;
use App\Models\CareerEventRegistration;
use App\Models\CareerExperience;
use App\Models\CareerJobPreference;
use App\Models\CareerLanguage;
use App\Models\CareerProfile;
use App\Models\CareerProject;
use App\Models\CareerPublication;
use App\Models\CareerSkill;
use App\Observers\TalentIndexObserver;
use App\Services\FixtureCoreIdentityGateway;
use App\Services\HttpCoreAlumniGateway;
use App\Services\HttpCoreDirectoryGateway;
use App\Services\HttpCoreIdentityGateway;
use App\Services\UnavailableCoreDirectoryGateway;
use App\Services\UnavailableCoreIdentityGateway;
use App\Support\CareerRoleRegistry;
use App\Support\CorePrincipalNormalizer;
use App\Support\EnvironmentSafetyGuard;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CvPdfRenderer::class, ChromiumCvPdfRenderer::class);
        $this->app->singleton(CorePrincipalNormalizer::class, fn () => new CorePrincipalNormalizer(
            roles: new CareerRoleRegistry,
            expectedAppCode: (string) config('core_identity.app_code'),
            allowedProgramIds: config('core_identity.allowed_program_ids', []),
            environment: $this->app->environment(),
        ));

        $this->app->singleton(CoreDirectoryGateway::class, function () {
            if (config('core_identity.driver') === 'http') {
                return new HttpCoreDirectoryGateway(
                    enabled: (bool) config('core_identity.http.enabled'),
                    baseUrl: config('core_identity.http.directory_base_url'),
                    clientId: config('core_identity.http.client_id'),
                    clientSecret: config('core_identity.http.client_secret'),
                    allowedProgramIds: config('core_identity.allowed_program_ids', []),
                    connectTimeoutSeconds: (int) config('core_identity.http.connect_timeout_seconds'),
                    timeoutSeconds: (int) config('core_identity.http.timeout_seconds'),
                    environment: $this->app->environment(),
                );
            }

            return new UnavailableCoreDirectoryGateway;
        });

        $this->app->singleton(CoreIdentityGateway::class, function () {
            if (config('core_identity.driver') === 'fixture') {
                return new FixtureCoreIdentityGateway($this->app->environment());
            }

            if (config('core_identity.driver') === 'http') {
                return new HttpCoreIdentityGateway(
                    normalizer: $this->app->make(CorePrincipalNormalizer::class),
                    enabled: (bool) config('core_identity.http.enabled'),
                    verifyUrl: config('core_identity.http.verify_url'),
                    clientId: config('core_identity.http.client_id'),
                    clientSecret: config('core_identity.http.client_secret'),
                    connectTimeoutSeconds: (int) config('core_identity.http.connect_timeout_seconds'),
                    timeoutSeconds: (int) config('core_identity.http.timeout_seconds'),
                    environment: $this->app->environment(),
                );
            }

            return new UnavailableCoreIdentityGateway;
        });

        $this->app->singleton(CoreAlumniGateway::class, fn () => new HttpCoreAlumniGateway(
            enabled: (bool) config('core_identity.http.enabled'),
            baseUrl: config('core_identity.http.alumni_base_url'),
            clientId: config('core_identity.http.client_id'),
            clientSecret: config('core_identity.http.client_secret'),
            connectTimeoutSeconds: (int) config('core_identity.http.connect_timeout_seconds'),
            timeoutSeconds: (int) config('core_identity.http.timeout_seconds'),
            environment: $this->app->environment(),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        EnvironmentSafetyGuard::assertIdentityDriver(
            $this->app->environment(),
            (string) config('core_identity.driver'),
        );
        EnvironmentSafetyGuard::assertDatabase(
            $this->app->environment(),
            (string) config('database.default'),
            (string) config('database.connections.mysql.database'),
        );

        foreach ([CareerProfile::class, CareerEducation::class, CareerExperience::class, CareerSkill::class,
            CareerCertification::class, CareerProject::class, CareerPublication::class, CareerLanguage::class,
            CareerJobPreference::class, CareerEventRegistration::class] as $model) {
            $model::observe(TalentIndexObserver::class);
        }
    }
}
