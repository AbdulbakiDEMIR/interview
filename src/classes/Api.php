<?php
namespace Turkpin\InterviewTest\classes;

class Api
{
    // OYUNLARI GETİREN FONKSİYON
    public function getGames()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $apiClient = new TurkpinApiClient();
            $gameManager = new Game($apiClient);
            $games = $gameManager->getAllGames();

            echo json_encode([
                'success' => true,
                'data' => $games
            ]);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    // ÜRÜNLERİ GETİREN FONKSİYON
    public function getProducts()
    {
        header('Content-Type: application/json; charset=utf-8');

        $gameId = $_GET['game_id'] ?? 0;

        if (empty($gameId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Lütfen bir oyun seçiniz.']);
            exit;
        }

        try {
            $apiClient = new TurkpinApiClient();
            $productManager = new Product($apiClient);
            $products = $productManager->getProductsByGameId((int) $gameId);

            echo json_encode(['success' => true, 'data' => $products]);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}
