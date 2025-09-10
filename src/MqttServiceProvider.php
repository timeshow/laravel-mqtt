<?php
declare(strict_types=1);
namespace TimeShow\Mqtt;

use Illuminate\Support\ServiceProvider;
class MqttServiceProvider extends ServiceProvider
{

    /**
     * The base package path.
     *
     * @var string|null
     * @var string|null
     */
    public static string|null $packagePath = null;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/config/mqtt.php' => config_path('mqtt.php'),
            ],
            'mqtt'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('mqtt', function () {
            return new Mqtt();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['mqtt'];
    }

}