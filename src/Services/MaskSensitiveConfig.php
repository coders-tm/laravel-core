<?php

namespace Coderstm\Services;

use Illuminate\View\Compilers\BladeCompiler;

class MaskSensitiveConfig extends BladeCompiler
{
    protected array $dangerousFunctions = ['exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen', 'pcntl_exec', 'file_get_contents', 'file_put_contents', 'fopen', 'fwrite', 'unlink', 'rmdir', 'mkdir', 'rename', 'copy', 'chmod', 'chown', 'eval', 'assert', 'create_function', 'call_user_func', 'call_user_func_array', 'DB::raw', 'DB::statement', 'DB::unprepared', 'ReflectionClass', 'ReflectionFunction', 'ReflectionMethod', 'Cookie::'];

    protected $sensitiveKeys = ['app.key', 'app.cipher', 'auth.passwords.users.provider', 'database.connections.mysql.password', 'database.connections.pgsql.password', 'database.connections.sqlite.password', 'database.connections.sqlsrv.password', 'mail.password', 'mail.username', 'services.mailgun.secret', 'services.postmark.token', 'services.ses.key', 'services.ses.secret', 'services.stripe.key', 'services.stripe.secret', 'services.google.client_id', 'services.google.client_secret', 'services.github.client_id', 'services.github.client_secret', 'services.facebook.client_id', 'services.facebook.client_secret', 'services.twitter.client_id', 'services.twitter.client_secret', 'broadcasting.connections.pusher.key', 'broadcasting.connections.pusher.secret', 'broadcasting.connections.pusher.app_id', 'broadcasting.connections.pusher.options.cluster', 'filesystems.disks.s3.key', 'filesystems.disks.s3.secret', 'queue.connections.sqs.key', 'queue.connections.sqs.secret', 'payments.stripe.secret_key', 'payments.paypal.client_id', 'payments.paypal.client_secret', 'cashier.key', 'cashier.secret', 'cashier.webhook.secret'];

    public function compileString($value)
    {
        $this->blockDangerousFunctions($value);
        $this->blockMutationCalls($value);
        $value = preg_replace('/<\\?(?:php|=).*?\\?>/s', '', $value);
        $value = str_replace([chr(60).'?', '?'.chr(62)], '', $value);
        $compiled = parent::compileString($value);
        $compiled = preg_replace_callback('/config\\((["]|\')(?:\\s*)?([a-zA-Z0-9._]+)(?:\\s*)?\\1\\)/', function ($matches) {
            if (in_array($matches[2], $this->sensitiveKeys)) {
                return "'****'";
            }

            return "config('{$matches[2]}')";
        }, $compiled);
        $compiled = preg_replace_callback('/Config::get\\((["]|\')(?:\\s*)?([a-zA-Z0-9._]+)(?:\\s*)?\\1\\)/', function ($matches) {
            if (in_array($matches[2], $this->sensitiveKeys)) {
                return "'****'";
            }

            return "Config::get('{$matches[2]}')";
        }, $compiled);
        $compiled = $this->sanitizeEnvCalls($compiled);
        $compiled = $this->sanitizeSettingsCalls($compiled);
        $compiled = $this->stripDangerousFunctionsFromCompiled($compiled);

        return $compiled;
    }

    protected function blockDangerousFunctions(string $content): void
    {
        foreach ($this->dangerousFunctions as $function) {
            $pattern = '/\\b'.preg_quote($function, '/').'\\s*\\(/i';
            if (preg_match($pattern, $content)) {
                throw new \InvalidArgumentException("Function '{$function}' is not allowed for security reasons.");
            }
        }
    }

    protected function stripDangerousFunctionsFromCompiled(string $compiled): string
    {
        foreach ($this->dangerousFunctions as $function) {
            $pattern = '/\\b'.preg_quote($function, '/').'\\s*\\(/i';
            $compiled = preg_replace($pattern, '/* BLOCKED */(', $compiled);
        }

        return $compiled;
    }

    protected function blockMutationCalls(string $content): void
    {
        $patterns = ['/\\bconfig\\s*\\(\\s*\\[/i', '/Config::set\\s*\\(/i', '/\\bsettings\\s*\\(\\s*\\[/i', '/(?:->|\\$\\w+->)update\\s*\\(/i', '/(?:\\w+::)update\\s*\\(/i'];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new \InvalidArgumentException('Mutation calls (config/settings/update write) are not allowed for security reasons.');
            }
        }
    }

    public function sanitizeEnvCalls($value)
    {
        return preg_replace_callback('/env\\((["]|\')(?:\\s*)?([a-zA-Z0-9._]+)(?:\\s*)?\\1\\)/', function () {
            return "'****'";
        }, $value);
    }

    public function sanitizeSettingsCalls($value)
    {
        return preg_replace_callback('/settings\\((["]|\')(?:\\s*)?([a-zA-Z0-9._-]+)(?:\\s*)?\\1(?:\\s*,\\s*[^)]*)?\\)/', function () {
            return "'****'";
        }, $value);
    }
}
