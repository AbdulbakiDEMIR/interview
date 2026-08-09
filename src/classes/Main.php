<?php

class Main
{
    public $router;

    public function __construct()
    {
        global $lang, $smarty;

        // DUZELTILDI: 'lang' key'i yoksa hata veriyordu. ?? operatoru eklendi.
        // isset() fonskiyonunu da kullanabilirdim ancak ?? operatorunu tercih ettim.
        $lang = $_SESSION['lang'] ?? 'tr';

        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
        }

        require_once __DIR__ . "/../languages/{$lang}.php";

        // Hata yöneticisini başlat
        set_exception_handler(['\App\Handlers\ExceptionHandler', 'handle']);

        $smarty = new Smarty\Smarty();
        $this->router = new \Bramus\Router\Router();

        $smarty->setTemplateDir('src/templates');

        // DUZELTILDI: Sabit '/tmp' yolu Windows ve farklı sunucu ortamlarında hata ürettiği için
        // cross-platform uyumlu proje içi 'templates_c' dizini ile değiştirildi.
        $smarty->setCompileDir(__DIR__ . '/../../templates_c');

        $smarty->assign('LANG', $lang);
        $smarty->assign('langs', ['tr' => 'Türkçe', 'en' => 'English']);
    }

    public function run()
    {
        global $smarty;


        //apiler
        $this->router->get('/api/games', function () {
            $api = new \App\classes\Api();
            $api->getGames();
        });

        $this->router->get('/api/balance', function () {
            $api = new \App\classes\Api();
            $api->getBalance();
        });

        $this->router->get('/api/products', function () {
            $api = new \App\classes\Api();
            $api->getProducts();
        });

        $this->router->get('/api/product', function () {
            $api = new \App\classes\Api();
            $api->getProductsByProductId();
        });

        $this->router->get('/api/orders', function () {
            $api = new \App\classes\Api();
            $api->getOrders();
        });

        $this->router->post('/api/orders', function () {
            $api = new \App\classes\Api();
            $api->createOrder();
        });


        $this->router->get('/api/order/(\d+)', function ($orderId) {
            $api = new \App\classes\Api();
            $api->getOrderStatus($orderId);
        });



        //sayfalar
        $this->router->get('/', function () {
            global $smarty;
            $games = [];
            try {
                $gameManager = new \App\classes\Game();
                $games = $gameManager->getAllGames();
            } catch (\Exception $e) {
                // API hatası oluşursa boş dizi kalır
                $games = [];
            }
            $smarty->assign('games', $games);
            $smarty->assign('template', 'products.html');
        });

        $this->router->get('/balance', function () {
            global $smarty;

            $balanceManager = new \App\classes\Balance();
            $balanceData = $balanceManager->getBalance();

            $smarty->assign('balanceData', $balanceData);
            $smarty->assign('template', 'balance.html');
        });

        $this->router->get('/orders', function () {
            global $smarty;
            $smarty->assign('template', 'orders.html');
        });


        $this->router->get('/order/(\d+)', function ($orderId) {
            global $smarty;

            try {
                $orderManager = new \App\classes\Order();
                $order = $orderManager->getOrderStatus((string) $orderId);
                if (empty($order)) {
                    $smarty->assign('template', '404.html');
                    return;
                }
            } catch (\Exception $e) {
                $smarty->assign('template', '404.html');
                return;
            }

            $smarty->assign('order', $order);
            $smarty->assign('template', 'order_detail.html');
        });


        $this->router->get('/product/(\d+)/(\d+)', function ($gameId, $productId) {
            global $smarty;

            try {
                $productManager = new \App\classes\Product();
                $products = $productManager->getProductByProductId((int) $gameId, (int) $productId);

                // Ürün listesi boşsa 404 sayfasına yönlendir
                if (empty($products)) {
                    $smarty->assign('template', '404.html');
                    return;
                }

                // Bulunan ilk ürünü template'e aktar
                $smarty->assign('game_id', $gameId);
                $smarty->assign('product', $products[0]);
                $smarty->assign('template', 'product-detail.html');

            } catch (\Exception $e) {
                // Ürün bulunamadığında veya API hata verdiğinde 404 göster
                $smarty->assign('template', '404.html');
            }
        });



        // 404 Sayfa Bulunamadı
        $this->router->set404(function () {
            global $smarty;
            http_response_code(404);

            if (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'API uç noktası bulunamadı.'
                ]);
                exit;
            }

            $smarty->assign('template', '404.html');
        });

        $this->router->run();
        $smarty->display('index.html');
    }
}
