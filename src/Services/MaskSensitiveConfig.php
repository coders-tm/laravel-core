<?php

namespace Coderstm\Services;

use Illuminate\View\Compilers\BladeCompiler;

class MaskSensitiveConfig extends BladeCompiler
{
    protected $sensitiveKeys = [
        // Sensitive keys from config/app.php
        'app.key',
        'app.cipher',

        // Sensitive keys from config/auth.php
        'auth.passwords.users.provider',

        // Sensitive keys from config/database.php
        'database.connections.mysql.password',
        'database.connections.pgsql.password',
        'database.connections.sqlite.password',
        'database.connections.sqlsrv.password',

        // Sensitive keys from config/mail.php
        'mail.password',
        'mail.username',

        // Sensitive keys from config/services.php
        'services.mailgun.secret',
        'services.postmark.token',
        'services.ses.key',
        'services.ses.secret',
        'services.stripe.key',
        'services.stripe.secret',
        'services.google.client_id',
        'services.google.client_secret',
        'services.github.client_id',
        'services.github.client_secret',
        'services.facebook.client_id',
        'services.facebook.client_secret',
        'services.twitter.client_id',
        'services.twitter.client_secret',

        // Sensitive keys from config/broadcasting.php
        'broadcasting.connections.pusher.key',
        'broadcasting.connections.pusher.secret',
        'broadcasting.connections.pusher.app_id',
        'broadcasting.connections.pusher.options.cluster',

        // Sensitive keys from config/filesystems.php
        'filesystems.disks.s3.key',
        'filesystems.disks.s3.secret',

        // Sensitive keys from config/queue.php
        'queue.connections.sqs.key',
        'queue.connections.sqs.secret',

        // Sensitive keys from config/payments.php (custom payment configs)
        'payments.stripe.secret_key',
        'payments.paypal.client_id',
        'payments.paypal.client_secret',

        // Sensitive keys from config/cashier.php (for Laravel Cashier)
        'cashier.key',
        'cashier.secret',
        'cashier.webhook.secret',

        // Add other sensitive keys you want to mask
    ];

    /**
     * Compile the Blade template into PHP, and mask sensitive config calls.
     *
     * @param  string  $value
     * @return string
     */
    public function compileString($value)
    {
        // Call the parent method to get the compiled Blade string
        $compiled = parent::compileString($value);

        // Regular expression to find config() calls
        $compiled = preg_replace_callback(
            '/config\(\'([a-zA-Z0-9._]+)\'\)/',
            function ($matches) {
                // If the matched config key is sensitive, return '****', otherwise return the config call
                if (in_array($matches[1], $this->sensitiveKeys)) {
                    return "'****'";
                }

                // Leave non-sensitive config keys as they are
                return "config('{$matches[1]}')";
            },
            $compiled
        );

        // Regular expression to find config() calls
        $compiled = preg_replace_callback(
            '/Config::get\(\'([a-zA-Z0-9._]+)\'\)/',
            function ($matches) {
                // If the matched config key is sensitive, return '****', otherwise return the config call
                if (in_array($matches[1], $this->sensitiveKeys)) {
                    return "'****'";
                }

                // Leave non-sensitive config keys as they are
                return "Config::get('{$matches[1]}')";
            },
            $compiled
        );

        // Sanitize environment variables
        $compiled = $this->sanitizeEnvCalls($compiled);

        return $compiled;
    }

    /**
     * Sanitize all env() calls in Blade templates
     *
     * @param string $value
     * @return string
     */
    public function sanitizeEnvCalls($value)
    {
        // Replace all env() calls with '****'
        return preg_replace_callback(
            '/env\(\'([a-zA-Z0-9._]+)\'\)/', // Matches any env('KEY')
            function () {
                return "'****'"; // Masked value
            },
            $value
        );
    }
}
