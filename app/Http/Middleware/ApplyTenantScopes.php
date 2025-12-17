<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class ApplyTenantScopes
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        $tenant = Filament::getTenant();

        if (!$tenant instanceof Tenant) {
            return $next($request);
        }

        // Apply global scopes to all models that belong to a tenant
        $models = [
            \App\Models\CustomerMembership::class,
            \App\Models\PointsSetting::class,
            \App\Models\Tier::class,
            \App\Models\Transaction::class,
            \App\Models\Reward::class,
            \App\Models\Redemption::class,
            \App\Models\Staff::class,
            \App\Models\Notification::class,
        ];

        foreach ($models as $model) {
            $model::addGlobalScope('tenant', function (Builder $builder) use ($tenant) {
                $builder->where('tenant_id', $tenant->id);
            });
        }

        return $next($request);
    }
}
