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
                // pre_order alanı literal olarak "true" veya "false" string'i geldiği için:
                $isPreOrder = false;
                if (isset($product['pre_order']) && !is_array($product['pre_order'])) {
                    $isPreOrder = filter_var($product['pre_order'], FILTER_VALIDATE_BOOLEAN); // "true" => true, "false" => false
                }

                $formattedProducts[] = [
                    'id' => $product['id'] ?? 0,
                    'name' => $product['name'] ?? 'İsimsiz Ürün',
                    'stock' => $product['stock'] ?? 0,
                    'price' => $product['price'] ?? 0,
                    'pre_order' => $isPreOrder,
                    'tax_type' => $product['tax_type'] ?? '0',
                    'min_barem' => (isset($product['min_barem']) && !is_array($product['min_barem'])) ? (float) $product['min_barem'] : null,
                    'max_barem' => (isset($product['max_barem']) && !is_array($product['max_barem'])) ? (float) $product['max_barem'] : null,
                    'barem_step' => (isset($product['barem_step']) && !is_array($product['barem_step'])) ? (float) $product['barem_step'] : null,

                    // Boş gelen XML alanları dizi (Array) olarak dönüştüğü için onu da garanti altına alıyoruz
                    'min_order' => is_array($product['min_order']) ? 1 : ($product['min_order'] ?? 1),
                    'max_order' => is_array($product['max_order']) ? 100 : ($product['max_order'] ?? 100)
                ];
            }
        }

        return $formattedProducts;
    }

    public function getProductByProductId(int $gameId, int $productId): array
    {
        // İstekte oyunKodu'nu yollayarak o oyuna ait ürünleri çekiyoruz
        $response = $this->apiClient->execute('epinUrunleri', ['oyunKodu' => $gameId, 'urunKodu' => $productId]);

        $formattedProducts = [];

        if (isset($response['params']['epinUrunListesi']['urun'])) {
            $productsData = $response['params']['epinUrunListesi']['urun'];


            // Eğer sadece 1 ürün dönerse XML parser bunu tek boyutlu dizi yapar. Düzeltiyoruz:
            if (isset($productsData['id'])) {
                $productsData = [$productsData];
            }

            foreach ($productsData as $product) {
                // pre_order alanı literal olarak "true" veya "false" string'i geldiği için:
                $isPreOrder = false;
                if (isset($product['pre_order']) && !is_array($product['pre_order'])) {
                    $isPreOrder = filter_var($product['pre_order'], FILTER_VALIDATE_BOOLEAN); // "true" => true, "false" => false
                }

                $formattedProducts[] = [
                    'id' => $product['id'] ?? 0,
                    'name' => $product['name'] ?? 'İsimsiz Ürün',
                    'stock' => $product['stock'] ?? 0,
                    'price' => $product['price'] ?? 0,
                    'pre_order' => $isPreOrder,
                    'tax_type' => $product['tax_type'] ?? '0',
                    'min_barem' => (isset($product['min_barem']) && !is_array($product['min_barem'])) ? (float) $product['min_barem'] : null,
                    'max_barem' => (isset($product['max_barem']) && !is_array($product['max_barem'])) ? (float) $product['max_barem'] : null,
                    'barem_step' => (isset($product['barem_step']) && !is_array($product['barem_step'])) ? (float) $product['barem_step'] : null,
                    // Boş gelen XML alanları dizi (Array) olarak dönüştüğü için onu da garanti altına alıyoruz
                    'min_order' => is_array($product['min_order']) ? 1 : ($product['min_order'] ?? 1),
                    'max_order' => is_array($product['max_order']) ? 100 : ($product['max_order'] ?? 100)
                ];
            }
        }

        return $formattedProducts;
    }


}
