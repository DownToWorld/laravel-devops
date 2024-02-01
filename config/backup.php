<?php

return [

    'backup' => [

        'name' => env('APP_NAME', 'laravel-backup'),

        'source' => [

            'files' => [
                'include' => [
                    // We store files on S3
                ],
            ],

            'databases' => [
                'mysql',
            ],
        ],

        'database_dump_compressor' => Spatie\DbDumper\Compressors\GzipCompressor::class,

        'destination' => [

            'disks' => [
                's3' => [
                    'driver' => 's3',
                ],
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
    ],

    'notifications' => [

        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => ['mail'],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => 'your@example.com',

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',

            /*
         * If this is set to null the default channel of the webhook will be used.
         */
            'channel' => null,

            'username' => null,

            'icon' => null,

        ],

        'discord' => [
            'webhook_url' => env('DISCORD_NOTIFICATIONS_WEBHOOK'),

            /*
         * If this is an empty string, the name field on the webhook will be used.
         */
            'username' => '',

            /*
         * If this is an empty string, the avatar on the webhook will be used.
         */
            'avatar_url' => '',
        ],
    ],


    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'laravel-backup'),
            'disks' => ['s3'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],

    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [

            /*
         * The number of days for which backups must be kept.
         */
            'keep_all_backups_for_days' => 7,

            /*
         * The number of days for which daily backups must be kept.
         */
            'keep_daily_backups_for_days' => 16,

            /*
         * The number of weeks for which one weekly backup must be kept.
         */
            'keep_weekly_backups_for_weeks' => 8,

            /*
         * The number of months for which one monthly backup must be kept.
         */
            'keep_monthly_backups_for_months' => 4,

            /*
         * The number of years for which one yearly backup must be kept.
         */
            'keep_yearly_backups_for_years' => 2,

            /*
         * After cleaning up the backups remove the oldest backup until
         * this amount of megabytes has been reached.
         */
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],

];
