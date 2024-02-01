<?php

namespace DownToWorld\LaravelDevops;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Console\Scheduling\Schedule;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Our app is being served internally as HTTP but Cloudflare serves it as HTTPS so we need to force HTTPS
        if (env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        $this->publishes([
            __DIR__ . '/../docker-compose-prod.yml' => base_path('docker-compose-prod.yml'),
            __DIR__ . '/../docker-compose-prod.yml' => base_path('docker-compose-worker.yml'),
            __DIR__ . '/../docker-compose-prod.env' => base_path('docker-compose-prod.env'),

            __DIR__ . '/../config/seq.php' => config_path('seq.php'),
            __DIR__ . '/../config/seq.php' => config_path('backup.php'),
        ], 'laravel-devops');

        Config::set('logging.channels.gelf.system_name', Cache::rememberForever('hostname_ip', function () {
            return Str::betweenFirst(Http::get('https://cloudflare.com/cdn-cgi/trace')->body(), 'ip=', "\n");
        }));

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('backup:run')->daily();
        });
    }

    public function register()
    {
        if (config('seq.host')) {
            $this->setGelfDriverAtLoggingConfig();
        }
    }

    protected function setGelfDriverAtLoggingConfig()
    {
        Config::set('logging.channels.gelf', [
            'driver' => 'custom',

            'via' => \Hedii\LaravelGelfLogger\GelfLoggerFactory::class,

            // This optional option determines the processors that should be
            // pushed to the handler. This option is useful to modify a field
            // in the log context (see NullStringProcessor), or to add extra
            // data. Each processor must be a callable or an object with an
            // __invoke method: see monolog documentation about processors.
            // Default is an empty array.
            'processors' => [
                \Hedii\LaravelGelfLogger\Processors\NullStringProcessor::class,
                \Hedii\LaravelGelfLogger\Processors\RenameIdFieldProcessor::class,
                // another processor...
            ],

            // This optional option determines the minimum "level" a message
            // must be in order to be logged by the channel. Default is 'debug'
            'level' => 'debug',

            // This optional option determines the channel name sent with the
            // message in the 'facility' field. Default is equal to app.env
            // configuration value
            'name' => config('app.name'),

            // This optional option determines the system name sent with the
            // message in the 'source' field. When forgotten or set to null,
            // the current hostname is used.
            'system_name' => null,

            // This optional option determines if you want the UDP, TCP or HTTP
            // transport for the gelf log messages. Default is UDP
            'transport' => 'udp',

            // This optional option determines the host that will receive the
            // gelf log messages. Default is 127.0.0.1
            'host' => config('seq.host'),

            // This optional option determines the port on which the gelf
            // receiver host is listening. Default is 12201
            'port' => config('seq.port'),

            // This optional option determines the chunk size used when
            // transferring message via UDP transport. Default is 1420.
            'chunk_size' => 1420,

            // This optional option determines the path used for the HTTP
            // transport. When forgotten or set to null, default path '/gelf'
            // is used.
            'path' => null,

            // This optional option enable or disable ssl on TCP or HTTP
            // transports. Default is false.
            'ssl' => false,

            // If ssl is enabled, the following configuration is used.
            'ssl_options' => [
                // Enable or disable the peer certificate check. Default is
                // true.
                'verify_peer' => true,

                // Path to a custom CA file (eg: "/path/to/ca.pem"). Default
                // is null.
                'ca_file' => null,

                // List of ciphers the SSL layer may use, formatted as
                // specified in ciphers(1). Default is null.
                'ciphers' => null,

                // Whether self-signed certificates are allowed. Default is
                // false.
                'allow_self_signed' => false,
            ],

            // This optional option determines the maximum length per message
            // field. When forgotten or set to null, the default value of 
            // \Monolog\Formatter\GelfMessageFormatter::DEFAULT_MAX_LENGTH is
            // used (currently this value is 32766)
            'max_length' => null,

            // This optional option determines the prefix for 'context' fields
            // from the Monolog record. Default is null (no context prefix)
            'context_prefix' => null,

            // This optional option determines the prefix for 'extra' fields
            // from the Monolog record. Default is null (no extra prefix)
            'extra_prefix' => null,

            // This optional option determines whether errors thrown during
            // logging should be ignored or not. Default is true.
            'ignore_error' => true,
        ]);
    }
}
