<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\classes\Product;
use App\classes\TurkpinApiClient;

class ProductTest extends TestCase
{
    /**
     * Turkpin API'sinden gelen gerçek XML yanıt dizisi ile sipariş formatlama testi.
     */
    public function testGetProductsWithRealXmlStructure(): void
    {
        // 1. Sahte (Stub) API İstemcisi oluşturuyoruz
        $mockApiClient = $this->createStub(TurkpinApiClient::class);

        // 2. Turkpin API'sinin gönderdiği GERÇEK XML yanıt dizisini mock olarak tanımlıyoruz
        $mockApiClient->method('execute')->willReturn([
            'params' => [
                'error' => '000',
                'error_desc' => 'Islem Basarili',
                'oyun' => '1',
                'epinUrunListesi' => [
                    'urun' => [
                        [
                            'name' => 'Test',
                            'id' => '1',
                            'stock' => '24017',
                            'min_Product' => '1',
                            'max_Product' => [],
                            'price' => '0.001',
                            'tax_type' => '0',
                            'pre_Product' => 'false',
                        ]
                    ]
                ]
            ]
        ]);

        // 3. Product servisimize sahte istemcimizi veriyoruz
        $productService = new Product($mockApiClient);
        $products = $productService->getProductByProductId(1, 1);

        // 4. DOĞRULAMALAR (Assertions):
        $this->assertCount(1, $products);

        $this->assertArrayHasKey('id', $products[0]);
        $this->assertArrayHasKey('name', $products[0]);
        $this->assertArrayHasKey('stock', $products[0]);
        $this->assertArrayHasKey('price', $products[0]);
        $this->assertArrayHasKey('pre_order', $products[0]);
        $this->assertArrayHasKey('tax_type', $products[0]);
        $this->assertArrayHasKey('min_barem', $products[0]);
        $this->assertArrayHasKey('max_barem', $products[0]);
        $this->assertArrayHasKey('barem_step', $products[0]);
        $this->assertArrayHasKey('min_order', $products[0]);
        $this->assertArrayHasKey('max_order', $products[0]);

        $this->assertEquals('1', $products[0]['id']);
        $this->assertEquals('Test', $products[0]['name']);
        $this->assertEquals(24017, $products[0]['stock']);
        $this->assertEquals(0.001, $products[0]['price']);
        $this->assertEquals(false, $products[0]['pre_order']);
        $this->assertEquals(0, $products[0]['tax_type']);
        $this->assertEquals(null, $products[0]['min_barem']);
        $this->assertEquals(null, $products[0]['max_barem']);
        $this->assertEquals(null, $products[0]['barem_step']);
        $this->assertEquals(1, $products[0]['min_order']);
        $this->assertEquals(100, $products[0]['max_order']);

    }

}
