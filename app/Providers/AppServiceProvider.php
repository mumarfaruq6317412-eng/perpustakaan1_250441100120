<?php

namespace App\Providers;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            LoginActivity::create([
                'user_id' => $event->user->getKey(),
                'role' => $event->user->getRoleNames()->first() ?? 'tanpa_role',
                'logged_in_at' => now(),
            ]);
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Definisi Gate kelola-buku untuk Admin dan Petugas (Soal 3)
        Gate::define('kelola-buku', function (User $user) {
            return $user->hasRole(['admin', 'petugas']);
        });
    }
}
