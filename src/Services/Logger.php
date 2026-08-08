<?php
namespace App\Services;

class Logger
{
    private string $logFile;

    public function __construct(string $filename = 'app.log')
    {
        // Logların kaydedileceği dizin
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $this->logFile = $logDir . '/' . $filename;
    }

    public function info(string $message, array $context = []): void
    {
        $this->writeLog('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->writeLog('ERROR', $message, $context);
    }

    private function writeLog(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[{$date}] [{$level}] {$message} {$contextString}" . PHP_EOL;

        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}
