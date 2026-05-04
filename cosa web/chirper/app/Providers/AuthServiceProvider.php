<?php

namespace App\Providers;

use App\Models\AuthorityResponse;
use App\Models\Inundacion;
use App\Policies\AuthorityResponsePolicy;
use App\Policies\InundacionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Inundacion::class => InundacionPolicy::class,
        AuthorityResponse::class => AuthorityResponsePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
