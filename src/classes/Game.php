<?php

namespace App\classes;

class Game
{
    private TurkpinApiClient $apiClient;

    // Dependency Injection: Game sınıfı başlarken ona bir API İstemcisi veriyoruz
    public function __construct(?TurkpinApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? new TurkpinApiClient();
    }


    // ==========================================
    // ANA METOTLAR
    // ==========================================


    public function getAllGames()
    {
        // execute fonksiyonu sayesinde API Sınıfı XML'i kendi üretip cevabı bize JSON olarak dönecek
        $response = $this->apiClient->execute('epinOyunListesi');

        $formattedGames = [];

        // 3. Oyunlar dizisi gelmiş mi diye kontrol et
        if (isset($response['params']['oyunListesi']['oyun'])) {
            $gamesData = $response['params']['oyunListesi']['oyun'];
            // 4. Diziyi dön ve [ id => name ] formatına getir
            foreach ($gamesData as $game) {
                // Burada id'yi anahtar (key), name'i ise değer (value) yapıyoruz
                $formattedGames[$game['id']] = $game['name'];
            }
        }

        return $formattedGames;
    }
}
