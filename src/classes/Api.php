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

    public function getProductsByProductId()
    {
        header('Content-Type: application/json; charset=utf-8');

        $gameId = $_GET['game_id'] ?? 0;
        $productId = $_GET['product_id'] ?? 0;

        if (empty($gameId) || empty($productId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Lütfen bir oyun ve ürün seçiniz.']);
            exit;
        }

        try {
            $apiClient = new TurkpinApiClient();
            $productManager = new Product($apiClient);
            $product = $productManager->getProductByProductId((int) $gameId, (int) $productId);

            echo json_encode(['success' => true, 'data' => $product]);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function getOrders()
    {
        header('Content-Type: application/json; charset=utf-8');
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;

        if (empty($start_date) || empty($end_date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tarihler zorunludur.']);
            exit;
        }

        try {
            $apiClient = new TurkpinApiClient();
            $orderManager = new Order($apiClient);
            $orders = $orderManager->getOrders((string) $start_date, (string) $end_date);

            echo json_encode(['success' => true, 'data' => $orders]);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function getOrderStatus($orderId)
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($orderId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Sipariş ID zorunludur.']);
            exit;
        }
        try {
            $apiClient = new TurkpinApiClient();
            $orderManager = new Order($apiClient);

            // Order.php sınıfında birazdan yazacağımız fonksiyona yönlendiriyoruz
            $orderStatus = $orderManager->getOrderStatus((string) $orderId);
            echo json_encode(['success' => true, 'data' => $orderStatus]);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function createOrder()
    {
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true);

        $gameId = $input['game_id'] ?? 0;
        $productId = $input['product_id'] ?? 0;
        $amount = $input['amount'] ?? 0;
        $user = $input['user'] ?? '';
        $pre_order = $input['pre_order'] ?? false;
        $barem = $input['barem'] ?? null;


        if (empty($gameId) || empty($productId) || empty($amount) || empty($user)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
            exit;
        }

        try {
            $apiClient = new TurkpinApiClient();
            $orderManager = new Order($apiClient);
            $order = $orderManager->buyProduct((int) $gameId, (int) $productId, (int) $amount, (string) $user, (bool) $pre_order, $barem ? (float) $barem : null);

            echo json_encode(['success' => true, 'data' => $order]);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}
