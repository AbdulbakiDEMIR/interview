<?php
namespace App\classes;

class Api
{
    // ==========================================
    // 1. YARDIMCI METOTLAR (RESPONSE & REQUEST)
    // ==========================================

    // Başarılı JSON yanıtı döner.
    private function successResponse($data = null, int $statusCode = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }


    // Gelen JSON gövdesini diziye çevirir.
    private function getJsonInput(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    // ==========================================
    // 2. API METODLARI 
    // ==========================================

    public function getGames(): void
    {
        $gameManager = new Game();
        $this->successResponse($gameManager->getAllGames());
    }

    public function getBalance(): void
    {
        $balanceManager = new Balance();
        $this->successResponse($balanceManager->getBalance());
    }

    public function getProducts(): void
    {
        $gameId = (int) ($_GET['game_id'] ?? 0);

        if (empty($gameId)) {
            throw new \App\Exceptions\ValidationException('Lütfen bir oyun seçiniz.', 400);
        }

        $productManager = new Product();
        $this->successResponse($productManager->getProductsByGameId($gameId));
    }

    public function getProductsByProductId(): void
    {
        $gameId = (int) ($_GET['game_id'] ?? 0);
        $productId = (int) ($_GET['product_id'] ?? 0);

        if (empty($gameId) || empty($productId)) {
            throw new \App\Exceptions\ValidationException('Lütfen bir oyun ve ürün seçiniz.', 400);
        }

        $productManager = new Product();
        $product = $productManager->getProductByProductId($gameId, $productId);
        $this->successResponse($product);
    }

    public function getOrders(): void
    {
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        if (empty($startDate) || empty($endDate)) {
            throw new \App\Exceptions\ValidationException('Tarihler zorunludur.', 400);
        }

        $orderManager = new Order();
        $orders = $orderManager->getOrders((string) $startDate, (string) $endDate);
        $this->successResponse($orders);

    }

    public function getOrderStatus($orderId): void
    {
        if (empty($orderId)) {
            throw new \App\Exceptions\ValidationException('Sipariş ID zorunludur.', 400);
        }

        $orderManager = new Order();
        $orderStatus = $orderManager->getOrderStatus((string) $orderId);
        $this->successResponse($orderStatus);

    }

    public function createOrder(): void
    {
        $input = $this->getJsonInput();

        $gameId = (int) ($input['game_id'] ?? 0);
        $productId = (int) ($input['product_id'] ?? 0);
        $amount = (int) ($input['amount'] ?? 0);
        $user = (string) ($input['user'] ?? '');
        $preOrder = (bool) ($input['pre_order'] ?? false);
        $barem = isset($input['barem']) ? (float) $input['barem'] : null;

        if (empty($gameId) || empty($productId) || empty($amount) || empty($user)) {
            throw new \App\Exceptions\ValidationException('Lütfen tüm alanları doldurun.');
        }

        $orderManager = new Order();
        $order = $orderManager->buyProduct($gameId, $productId, $amount, $user, $preOrder, $barem);

        $this->successResponse($order);
    }
}