<?php

namespace Turkpin\InterviewTest\classes;

class TurkpinApiClient
{
    private string $apiUrl;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->apiUrl = $_ENV['TURKPIN_API_URL'] ?? '';
        $this->username = $_ENV['TURKPIN_API_USERNAME'] ?? '';
        $this->password = $_ENV['TURKPIN_API_PASSWORD'] ?? '';


        if (empty($this->apiUrl) || empty($this->username) || empty($this->password)) {
            throw new \Exception("Turkpin API yapılandırma ayarları (.env) eksik. Lütfen kontrol ediniz.");
        }
    }

    private function request(string $xmlPayload, string $method = 'POST')
    {
        $method = strtoupper($method);
        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        if (!in_array($method, $allowedMethods)) {
            throw new \Exception("Geçersiz HTTP Metodu kullanıldı: {$method}");
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);


        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['DATA' => $xmlPayload]); // Dizi olarak (Multipart)
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['DATA' => $xmlPayload]); // Dizi olarak
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        // PHP 8+ için curl_close sildik

        if ($response === false) {
            throw new \Exception("API Ağ Hatası: " . $error);
        }

        if ($httpCode !== 200) {
            throw new \Exception("API Hatası (Kod: {$httpCode}). Sunucu Yanıtı: " . strip_tags($response));
        }

        $parsedXml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($parsedXml === false) {
            throw new \Exception("API'den geçersiz bir yanıt geldi (XML çözümlenemedi): " . $response);
        }

        // Okunan XML objesini, sanki JSON'dan gelmiş gibi düz Array'e çeviriyoruz
        $decodedResponse = json_decode(json_encode($parsedXml), true);
        // --- 1. TİP YANITLAR (HATA_NO ve HATA_ACIKLAMA ile gelenler) ---
        if (isset($decodedResponse['params']['HATA_NO'])) {
            if ($decodedResponse['params']['HATA_NO'] !== '000') {
                // HATA İSE UYARI FIRLAT
                throw new \Exception("Turkpin Hatası (Kod: " . $decodedResponse['params']['HATA_NO'] . ") - " . ($decodedResponse['params']['HATA_ACIKLAMA'] ?? 'Bilinmeyen Hata'));
            } else {
                // 000 İSE BAŞARILI LOGU YAZ (İsteğe bağlı)
                error_log("Turkpin API Başarılı: " . ($decodedResponse['params']['HATA_ACIKLAMA'] ?? 'İşlem Başarılı'));
            }
        }
        // --- 2. TİP YANITLAR (error ve error_desc ile gelenler) ---
        if (isset($decodedResponse['params']['error'])) {
            if ($decodedResponse['params']['error'] !== '000') {
                // HATA İSE UYARI FIRLAT
                throw new \Exception("Turkpin Hatası (Kod: " . $decodedResponse['params']['error'] . ") - " . ($decodedResponse['params']['error_desc'] ?? 'Bilinmeyen Hata'));
            } else {
                // 000 İSE BAŞARILI LOGU YAZ
                error_log("Turkpin API Başarılı: " . ($decodedResponse['params']['error_desc'] ?? 'İşlem Başarılı'));
            }
        }
        // Her şey yolundaysa temizlenmiş diziyi geri döndür
        return $decodedResponse;
    }


    public function execute(string $cmd, array $params = [])
    {
        $xml = $this->buildXml($cmd, $params);
        return $this->request($xml);
    }


    private function buildXml(string $cmd, array $params = []): string
    {
        $xml = "<APIRequest>\n";
        $xml .= "    <params>\n";
        $xml .= "        <cmd>" . $cmd . "</cmd>\n";
        $xml .= "        <username>" . $this->username . "</username>\n";
        $xml .= "        <password>" . $this->password . "</password>\n";
        foreach ($params as $key => $value) {
            $xml .= "        <" . $key . ">" . $value . "</" . $key . ">\n";
        }
        $xml .= "    </params>\n";
        $xml .= "</APIRequest>";

        return $xml;
    }
}