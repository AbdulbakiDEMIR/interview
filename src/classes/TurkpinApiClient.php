<?php

namespace App\classes;

use App\Exceptions\TurkpinApiException;
use App\Services\Logger;

class TurkpinApiClient
{
    private string $apiUrl;
    private string $username;
    private string $password;
    private Logger $logger;

    public function __construct()
    {
        $this->apiUrl = $_ENV['TURKPIN_API_URL'] ?? '';
        $this->username = $_ENV['TURKPIN_API_USERNAME'] ?? '';
        $this->password = $_ENV['TURKPIN_API_PASSWORD'] ?? '';
        $this->logger = new Logger('turkpin_api.log');

        if (empty($this->apiUrl) || empty($this->username) || empty($this->password)) {
            throw new TurkpinApiException("Turkpin API yapılandırma ayarları (.env) eksik.");
        }
    }

    // ==========================================
    // ANA METOT
    // ==========================================

    //Dışarıya açık ana API çağrı metodu.
    public function execute(string $cmd, array $params = []): array
    {
        $xmlPayload = $this->buildXml($cmd, $params);
        $rawResponse = $this->sendHttpRequest($xmlPayload);
        $parsedData = $this->parseXmlResponse($rawResponse);

        $this->validateApiResponse($parsedData);

        return $parsedData;
    }

    // ==========================================
    // YARDIMCI METOTLAR
    // ==========================================

    //Turkpin formatına uygun XML gövdesi oluşturur.
    private function buildXml(string $cmd, array $params = []): string
    {
        $xml = "<APIRequest>\n";
        $xml .= "    <params>\n";
        $xml .= "        <cmd>{$cmd}</cmd>\n";
        $xml .= "        <username>{$this->username}</username>\n";
        $xml .= "        <password>{$this->password}</password>\n";

        foreach ($params as $key => $value) {
            $xml .= "        <{$key}>{$value}</{$key}>\n";
        }

        $xml .= "    </params>\n";
        $xml .= "</APIRequest>";

        return $xml;
    }

    //cURL isteğini gönderir ve ham metni döner.
    private function sendHttpRequest(string $xmlPayload): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['DATA' => $xmlPayload]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        $logResponse = (is_string($response) && mb_strlen($response) > 2000)
            ? mb_substr($response, 0, 2000) . '... [Truncated due to length]'
            : $response;

        $this->logger->info("Turkpin API İsteği", [
            'url' => $this->apiUrl,
            'http_code' => $httpCode,
            'response' => $logResponse,
            'curl_error' => $error
        ]);

        if ($response === false) {
            throw new TurkpinApiException("API Ağ Hatası: {$error}");
        }

        if ($httpCode !== 200) {
            throw new TurkpinApiException("API HTTP Hatası (Kod: {$httpCode})", (string) $httpCode, strip_tags($response));
        }

        return $response;
    }

    //XML yanıtını diziye (array) çevirir.
    private function parseXmlResponse(string $rawResponse): array
    {
        $parsedXml = simplexml_load_string($rawResponse, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($parsedXml === false) {
            throw new TurkpinApiException("API yanıtı XML formatında çözümlenemedi.", 'XML_PARSE_ERROR', $rawResponse);
        }

        return json_decode(json_encode($parsedXml), true) ?? [];
    }

    // Turkpin'den dönen 000 dışındaki hata kodlarını kontrol eder.
    private function validateApiResponse(array $data): void
    {
        $params = $data['params'] ?? [];

        // 1. Tip Kontrol (HATA_NO / HATA_ACIKLAMA)
        if (isset($params['HATA_NO']) && $params['HATA_NO'] !== '000') {
            $code = (string) $params['HATA_NO'];
            $desc = $params['HATA_ACIKLAMA'] ?? 'Bilinmeyen Hata';
            throw new TurkpinApiException("Turkpin Hatası: {$desc} (Kod: {$code})", $code, $data);
        }

        // 2. Tip Kontrol (error / error_desc)
        if (isset($params['error']) && $params['error'] !== '000') {
            $code = (string) $params['error'];
            $desc = $params['error_desc'] ?? 'Bilinmeyen Hata';
            throw new TurkpinApiException("Turkpin Hatası: {$desc} (Kod: {$code})", $code, $data);
        }
    }
}
