<?php

namespace App\classes;

class Product
{
    private TurkpinApiClient $apiClient;

    public function __construct(?TurkpinApiClient $apiClient = null)
    {
        $this->apiClient = $apiClient ?? new TurkpinApiClient();
    }

    // ==========================================
    // ANA METOTLAR
    // ==========================================

    //Belirli bir oyuna ait tüm ürünleri getirir.
    public function getProductsByGameId(int $gameId): array
    {
        $response = $this->apiClient->execute('epinUrunleri', ['oyunKodu' => $gameId]);
        return $this->extractProductsFromResponse($response);
    }

    //Belirli bir oyuna ve ürün ID'sine ait ürünü getirir.

    public function getProductByProductId(int $gameId, int $productId): array
    {
        $response = $this->apiClient->execute('epinUrunleri', [
            'oyunKodu' => $gameId,
            'urunKodu' => $productId
        ]);

        return $this->extractProductsFromResponse($response);
    }




    // ==========================================
    // YARDIMCI METOTLAR
    // ==========================================

    //API yanıtından ürün listesini ayıklar ve normalize eder.
    private function extractProductsFromResponse(array $response): array
    {
        $productsData = $response['params']['epinUrunListesi']['urun'] ?? [];

        if (empty($productsData)) {
            return [];
        }

        // Tek bir ürün döndüğünde XML parser tek boyutlu dizi üretir, diziye sarıyoruz:
        if (isset($productsData['id'])) {
            $productsData = [$productsData];
        }

        return array_map([$this, 'formatProduct'], $productsData);
    }

    //Tek bir ürün verisini temizler ve tiplerini garanti altına alır.
    private function formatProduct(array $product): array
    {
        // "true" / "false" string gelen pre_order alanını boolean'a çevir
        $isPreOrder = false;
        if (isset($product['pre_order']) && !is_array($product['pre_order'])) {
            $isPreOrder = filter_var($product['pre_order'], FILTER_VALIDATE_BOOLEAN);
        }

        return [
            'id' => (int) ($product['id'] ?? 0),
            'name' => (string) ($product['name'] ?? 'İsimsiz Ürün'),
            'stock' => (int) ($product['stock'] ?? 0),
            'price' => (float) ($product['price'] ?? 0),
            'pre_order' => $isPreOrder,
            'tax_type' => (string) ($product['tax_type'] ?? '0'),
            'min_barem' => $this->getFloatOrNull($product, 'min_barem'),
            'max_barem' => $this->getFloatOrNull($product, 'max_barem'),
            'barem_step' => $this->getFloatOrNull($product, 'barem_step'),
            'min_order' => is_array($product['min_order'] ?? null) ? 1 : (int) ($product['min_order'] ?? 1),
            'max_order' => is_array($product['max_order'] ?? null) ? 100 : (int) ($product['max_order'] ?? 100)
        ];
    }

    //Barem değerleri dizi veya boş geldiyse null, geçerli ise float döner.
    private function getFloatOrNull(array $data, string $key): ?float
    {
        if (isset($data[$key]) && !is_array($data[$key]) && $data[$key] !== '') {
            return (float) $data[$key];
        }
        return null;
    }
}
