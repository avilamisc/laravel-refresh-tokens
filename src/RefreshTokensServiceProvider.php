<?php


namespace UonSoftware\RefreshTokens;


use Illuminate\Support\ServiceProvider;
use UonSoftware\RefreshTokens\Service\RefreshTokenDecoder;
use UonSoftware\RefreshTokens\Service\RefreshTokenEncoder;
use UonSoftware\RefreshTokens\Service\RefreshTokenVerifier;
use UonSoftware\RefreshTokens\Service\RefreshTokenGenerator;
use UonSoftware\RefreshTokens\Http\Middleware\RefreshMiddleware;
use UonSoftware\RefreshTokens\Console\DeleteExpiredRefreshTokens;
use UonSoftware\RefreshTokens\Service\RefreshTokenService as Service;
use UonSoftware\RefreshTokens\Contracts\{
    RefreshTokenDecoder as Decoder,
    RefreshTokenEncoder as Encoder,
    RefreshTokenVerifier as Verifier,
    RefreshTokenGenerator as Generator,
    RefreshTokenRepository as Repository};

/**
 * Class RefreshTokensServiceProvider
 *
 * @package UonSoftware\RefreshTokens
 * @author   Dusan Malusev <malusev@uon.rs>
 */
class RefreshTokensServiceProvider extends ServiceProvider
{
    protected $middlewareAliases = [
        'refresh.token' => RefreshMiddleware::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/refresh_tokens.php', 'refresh_tokens');

        // Services
        $this->app->singleton(Decoder::class, RefreshTokenDecoder::class);
        $this->app->singleton(Encoder::class, RefreshTokenEncoder::class);
        $this->app->singleton(Generator::class, RefreshTokenGenerator::class);
        $this->app->singleton(Verifier::class, RefreshTokenVerifier::class);
        $this->app->singleton(Repository::class, Service::class);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands(DeleteExpiredRefreshTokens::class);
        }
    }

    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../config/refresh_tokens.php' => config_path('refresh_tokens.php'),
            ]
        );

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->aliasMiddleware();
    }

    /**
     * Alias the middleware.
     *
     * @return void
     */
    protected function aliasMiddleware(): void
    {
        $router = $this->app['router'];

        $method = method_exists($router, 'aliasMiddleware') ? 'aliasMiddleware' : 'middleware';

        foreach ($this->middlewareAliases as $alias => $middleware) {
            $router->$method($alias, $middleware);
        }
    }
}
