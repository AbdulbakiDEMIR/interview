<?php

namespace App\Handlers;

use App\Services\Logger;
use App\Exceptions\ValidationException;
use App\Exceptions\TurkpinApiException;

class ExceptionHandler
{
    public static function handle(\Throwable $exception)
    {
        $logger = new Logger();

        // Hatayı log dosyasına yaz (Sadece biz görelim)
        $logger->error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);

        // Kullanıcıya dönecek HTTP durum kodu ve mesaj
        $statusCode = 500;
        $message = "Sunucu tarafında beklenmeyen bir hata oluştu.";

        // Eğer hata bizim bildiğimiz bir hataysa, mesajı kullanıcıya göster
        if ($exception instanceof ValidationException) {
            $statusCode = 400;
            $message = $exception->getMessage();
        } elseif ($exception instanceof TurkpinApiException) {
            $statusCode = 400; // veya API'den dönen kod
            $message = $exception->getMessage();
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}
