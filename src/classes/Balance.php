<?php
namespace App\classes;

class Balance
{
    private TurkpinApiClient $apiClient;

    // Dependency Injection: Balance sınıfı başlarken ona bir API İstemcisi veriyoruz
    public function __construct()
    {
        $this->apiClient = new TurkpinApiClient();
    }


    // ==========================================
    // ANA METOTLAR
    // ==========================================


    public function getBalance(): array
    {
        // execute fonksiyonu sayesinde API Sınıfı XML'i kendi üretip cevabı bize dizi olarak dönecek
        $response = $this->apiClient->execute('balance');

        $balanceData = $response['params']['balanceInformation'] ?? ($response['params'] ?? []);

        return [
            'balance' => (float) ($balanceData['balance'] ?? 0.0),
            'credit' => (float) ($balanceData['credit'] ?? 0.0),
            'bonus' => (float) ($balanceData['bonus'] ?? 0.0),
            'spending' => (float) ($balanceData['spending'] ?? 0.0),
        ];
    }
}
