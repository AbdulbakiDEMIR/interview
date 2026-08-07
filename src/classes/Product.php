<?php
namespace Turkpin\InterviewTest\classes;

class Product
{
    private TurkpinApiClient $apiClient;

    public function __construct(TurkpinApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function getProductsByGameId(int $gameId): array
    {
        // İstekte oyunKodu'nu yollayarak o oyuna ait ürünleri çekiyoruz
        $response = $this->apiClient->execute('epinUrunleri', ['oyunKodu' => $gameId]);

        $formattedProducts = [];


        if (isset($response['params']['epinUrunListesi']['urun'])) {
            $productsData = $response['params']['epinUrunListesi']['urun'];

            // Eğer sadece 1 ürün dönerse XML parser bunu tek boyutlu dizi yapar. Düzeltiyoruz:
            if (isset($productsData['id'])) {
                $productsData = [$productsData];
            }

            foreach ($productsData as $product) {
                // Ayrıca isimler Türkçe (adi, fiyat) değil, İngilizce (name, price) olarak geliyormuş!
                $formattedProducts[] = [
                    'id' => $product['id'] ?? 0,
                    'name' => $product['name'] ?? 'İsimsiz Ürün',
                    'stock' => $product['stock'] ?? 0,
                    'price' => $product['price'] ?? 0,
                    // Boş gelen XML alanları dizi (Array) olarak dönüştüğü için onu da garanti altına alıyoruz
                    'min_order' => is_array($product['min_order']) ? 1 : ($product['min_order'] ?? 1),
                    'max_order' => is_array($product['max_order']) ? 100 : ($product['max_order'] ?? 100)
                ];
            }
        }

        return $formattedProducts;
    }
}
