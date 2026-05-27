<?php

declare(strict_types=1);

namespace App\Core;

class Logger
{
    private string $logPath;

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?: dirname(dirname(__DIR__)) . '/storage/logs';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function log(string $level, string|\Stringable $message, array $context = []): void
    {
        $message = (string)$message;
        $message = $this->interpolate($message, $context);
        
        $timestamp = date('Y-m-d H:i:s');
        $level = strtoupper($level);
        
        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[{$timestamp}] [{$level}] {$message}{$contextString}" . PHP_EOL;
        
        $filePath = $this->logPath . '/app-' . date('Y-m-d') . '.log';
        file_put_contents($filePath, $logLine, FILE_APPEND);
    }

    /**
     * Interpolate context values into message placeholders.
     */
    private function interpolate(string $message, array &$context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string)$val;
            }
        }
        return strtr($message, $replace);
    }
}
