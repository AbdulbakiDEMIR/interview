<?php

class Home
{
    public function index()
    {
        global $smarty;

        try {
            // 1. API İstemcisini Başlat
            $apiClient = new \Turkpin\InterviewTest\classes\TurkpinApiClient();

            // 2. Oyun Sınıfını Başlat (İçine API istemcisini ver)
            $gameManager = new \Turkpin\InterviewTest\classes\Game($apiClient);

            // 3. Oyunları Çek
            $games = $gameManager->getAllGames();
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " - HATA: " . $e->getMessage() . "\n", 3, __DIR__ . '/../../api_error.log');
            http_response_code(500);
            $games = [];

            // Gerçek hatayı da mesaja ekliyoruz!
            $smarty->assign('api_error', 'Oyun listesi yüklenirken bir sorun oluştu: ' . $e->getMessage());
        }


        $products = [
            [
                'id' => 1,
                'name' => 'Product 1',
                'stock' => 10,
                'min_order' => 1,
                'max_order' => 5,
                'price' => 100
            ],
            [
                'id' => 2,
                'name' => 'Product 2',
                'stock' => 20,
                'min_order' => 1,
                'max_order' => 5,
                'price' => 200
            ],
            [
                'id' => 3,
                'name' => 'Product 3',
                'stock' => 30,
                'min_order' => 1,
                'max_order' => 5,
                'price' => 300
            ],
        ];

        $smarty->assign('games', $games);
        $smarty->assign('products', $products);

        $smarty->assign('template', 'home.html');
    }
}
