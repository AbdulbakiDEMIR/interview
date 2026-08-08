<?php
namespace Turkpin\InterviewTest\classes;

class Order
{
    private TurkpinApiClient $apiClient;

    public function __construct(TurkpinApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function getOrders(string $startDate, string $endDate): array
    {
        $response = $this->apiClient->execute('siparisListesi', ['baslangicTarihi' => $startDate, 'bitisTarihi' => $endDate]);

        $formattedOrders = [];


        // API'den dönen cevabın içerisindeki 'SIPARISLER' yapısını arıyoruz
        if (isset($response['params']['SIPARISLER']['SIPARIS']) || isset($response['SIPARIS'])) {
            $ordersData = $response['params']['SIPARISLER']['SIPARIS'] ?? ($response['SIPARIS'] ?? []);

            // Eğer sadece 1 sipariş dönerse XML parser bunu tek boyutlu dizi yapar. Düzeltiyoruz:
            // Anahtarı 'id' değil 'SIPARIS_NO' olarak kontrol etmeliyiz!
            if (isset($ordersData['SIPARIS_NO'])) {
                $ordersData = [$ordersData];
            }

            foreach ($ordersData as $order) {
                $formattedOrders[] = [
                    'SIPARIS_NO' => $order['SIPARIS_NO'] ?? 0,
                    'SIPARIS_DURUMU' => $order['SIPARIS_DURUMU'] ?? 'Bilinmiyor',
                    'KONTROL_TARIHI' => $order['KONTROL_TARIHI'] ?? '-',
                    'SIPARIS_DURUMU_ACIKLAMA' => $order['SIPARIS_DURUMU_ACIKLAMA'] ?? 'Açıklama Yok',
                    'SIPARIS_TUTARI' => $order['SIPARIS_TUTARI'] ?? 0,
                    'EXTRA' => $order['EXTRA'] ?? '',
                    'EPIN_LIST' => $order['epin_list']['epin'] ?? []
                ];
            }
        }


        return $formattedOrders;
    }

    public function getOrderStatus(string $orderId): array
    {
        // Turkpin API'sine 'siparisDurumu' komutu ve ilgili sipariş numarası ile istek atıyoruz
        $response = $this->apiClient->execute('siparisDurumu', ['siparisNo' => $orderId]);

        // Yanıt XML parser'dan bazen ['params'] içinde bazen direkt kökte gelebilir
        $data = $response['params'] ?? $response;


        if (isset($data['SIPARIS_NO'])) {
            return [
                'SIPARIS_NO' => $data['SIPARIS_NO'] ?? $orderId,
                'SIPARIS_DURUMU' => $data['SIPARIS_DURUMU'] ?? 'Bilinmiyor',
                'KONTROL_TARIHI' => $data['KONTROL_TARIHI'] ?? '-',
                'SIPARIS_DURUMU_ACIKLAMA' => $data['SIPARIS_DURUMU_ACIKLAMA'] ?? 'Açıklama Yok',
                'SIPARIS_TUTARI' => $data['SIPARIS_TUTARI'] ?? 0,
                'EPIN_LIST' => $data['epin_list']['epin'] ?? []
            ];
        }
        throw new \Exception("Sipariş verisine ulaşılamadı.");
    }

    public function buyProduct(int $gameId, int $productId, int $amount, string $user, bool $pre_order = false, ?float $barem = null)
    {
        // 1. Ortak parametreleri bir dizi içinde topla
        $params = [
            "oyunKodu" => $gameId,
            "urunKodu" => $productId,
            "adet" => $amount,
            "character" => $user,
        ];

        // Ön sipariş ise ek parametre göndermek gerekiyorsa (opsiyonel)
        if ($pre_order) {
            $params["pre_order"] = true;
        }

        // 2. Barem varsa, parametrelere ekle ve adedi 1'e sabitle
        if (!is_null($barem)) {
            $params["adet"] = 1;
            $params["barem"] = $barem;
        }

        // 3. Tek bir execute komutuyla gönder
        $response = $this->apiClient->execute("epinSiparisYarat", $params);

        $formattedOrder = [];
        if (isset($response['params']['epinSiparisSonuc'])) {
            $formattedOrder[] = $response['params']['epinSiparisSonuc'];
        }

        return $formattedOrder;
    }
}
