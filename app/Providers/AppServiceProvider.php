<?php

namespace App\Providers;

use App\Events\PertemuanSelesai;
use App\Listeners\KirimNotifikasiWaliMurid;
use App\Services\Wa\NullWaService;
use App\Services\Wa\OpenWaService;
use App\Services\Wa\WaServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WaServiceInterface::class, function () {
            return match (config('wa.provider')) {
                'openwa' => new OpenWaService(),
                default  => new NullWaService(),
            };
        });
    }

    public function boot(): void
    {
        Event::listen(PertemuanSelesai::class, KirimNotifikasiWaliMurid::class);
    }
}
