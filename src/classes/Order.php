<?php

namespace App\classes;

use App\Exceptions\TurkpinApiException;

class Order
{
    private TurkpinApiClient $apiClient;

    public function __construct(?TurkpinApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? new TurkpinApiClient();
    }

    // ==========================================
    // ANA METOTLAR
    // ==========================================


    //Belirli bir tarih aralığındaki siparişleri getirir.
    public function getOrders(string $startDate, string $endDate): array
    {
        $response = $this->apiClient->execute('siparisListesi', [
            'baslangicTarihi' => $startDate,
            'bitisTarihi' => $endDate
        ]);

        $ordersData = $response['params']['SIPARISLER']['SIPARIS'] ?? ($response['SIPARIS'] ?? []);

        if (empty($ordersData)) {
            return [];
        }

        // Tek bir sipariş döndüğünde diziye sarıyoruz
        if (isset($ordersData['SIPARIS_NO'])) {
            $ordersData = [$ordersData];
        }

        return array_map([$this, 'formatOrder'], $ordersData);
    }

    //Tek bir siparişin güncel durumunu sorgular.
    public function getOrderStatus(string $orderId): array
    {
        $response = $this->apiClient->execute('siparisDurumu', ['siparisNo' => $orderId]);
        $data = $response['params'] ?? $response;

        if (!isset($data['SIPARIS_NO'])) {
            throw new TurkpinApiException("Sipariş verisine ulaşılamadı veya sipariş bulunamadı.", 'ORDER_NOT_FOUND');
        }

        return $this->formatOrder($data);
    }

    //Yeni bir E-Pin siparişi oluşturur.
    public function buyProduct(
        int $gameId,
        int $productId,
        int $amount,
        string $user,
        bool $preOrder = false,
        ?float $barem = null
    ): array {
        $params = [
            'oyunKodu' => $gameId,
            'urunKodu' => $productId,
            'adet' => $amount,
            'character' => $user,
        ];

        try {
            $productManager = new Product();
            $product = $productManager->getProductByProductId($gameId, $productId);
            if (empty($product)) {
                throw new TurkpinApiException("Ürün bulunamadı.", "PRODUCT_NOT_FOUND");
            }
        } catch (TurkpinApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TurkpinApiException("Ürün bulunamadı.", "PRODUCT_NOT_FOUND");
        }

        $minOrder = $product[0]['min_order'];
        $maxOrder = $product[0]['max_order'];

        if ($amount < $minOrder || $amount > $maxOrder) {
            throw new TurkpinApiException("Sipariş miktarı belirtilen limitlerin dışında.", "ORDER_LIMITS_ERROR");
        }

        if ($preOrder) {
            $params['pre_order'] = true;
        }

        if ($barem !== null) {
            $params['adet'] = 1;
            $params['barem'] = $barem;
        }

        $response = $this->apiClient->execute('epinSiparisYarat', $params);

        return $response['params']['epinSiparisSonuc'] ?? $response['params'] ?? [];
    }

    // ==========================================
    // YARDIMCI FORMATLAMA METOTLARI
    // ==========================================

    //Ham sipariş verisini standart anahtarlara ve tiplere dönüştürür.
    private function formatOrder(array $order): array
    {
        // EXTRA alanı boş XML tag'i olduğunda dizi olarak gelir, kontrol ediyoruz:
        $extra = '';
        if (isset($order['EXTRA'])) {
            $extra = is_array($order['EXTRA']) ? '' : (string) $order['EXTRA'];
        }

        return [
            'SIPARIS_NO' => (string) ($order['SIPARIS_NO'] ?? ''),
            'SIPARIS_DURUMU' => (string) ($order['SIPARIS_DURUMU'] ?? 'Bilinmiyor'),
            'KONTROL_TARIHI' => (string) ($order['KONTROL_TARIHI'] ?? '-'),
            'SIPARIS_DURUMU_ACIKLAMA' => (string) ($order['SIPARIS_DURUMU_ACIKLAMA'] ?? 'Açıklama Yok'),
            'SIPARIS_TUTARI' => (float) ($order['SIPARIS_TUTARI'] ?? 0.0),
            'EXTRA' => $extra,
            'EPIN_LIST' => $this->normalizeEpinList($order['epin_list']['epin'] ?? [])
        ];
    }

    //E-Pin listesini tekil veya boş dönse dahi standart liste dizisine çevirir.
    private function normalizeEpinList($epinData): array
    {
        if (empty($epinData)) {
            return [];
        }

        if (!is_array($epinData)) {
            return [$epinData];
        }

        // Tek bir pin associative array olarak döndüğünde
        if (!isset($epinData[0])) {
            return [$epinData];
        }

        return $epinData;
    }
}
