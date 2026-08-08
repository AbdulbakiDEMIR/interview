<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\classes\Order;
use App\classes\TurkpinApiClient;

class OrderTest extends TestCase
{
    /**
     * Turkpin API'sinden gelen gerçek XML yanıt dizisi ile sipariş formatlama testi.
     */
    public function testGetOrdersWithRealXmlStructure(): void
    {
        // 1. Sahte (Stub) API İstemcisi oluşturuyoruz
        $mockApiClient = $this->createStub(TurkpinApiClient::class);

        // 2. Turkpin API'sinin gönderdiği GERÇEK XML yanıt dizisini mock olarak tanımlıyoruz
        $mockApiClient->method('execute')->willReturn([
            'params' => [
                'HATA_NO' => '000',
                'HATA_ACIKLAMA' => 'Islem Basarili',
                'SIPARISLER' => [
                    'SIPARIS' => [
                        // Sipariş 1: Tek epin içeren sipariş
                        [
                            'EXTRA' => [],
                            'DURUM_KODU' => '000',
                            'SIPARIS_NO' => '26080519063401',
                            'SIPARIS_DURUMU' => '2',
                            'KONTROL_TARIHI' => '08-08-2026 12:10:38',
                            'SIPARIS_DURUMU_ACIKLAMA' => 'Siparişiniz Tamamlandı',
                            'SIPARIS_TUTARI' => '0.0010',
                            'epin_list' => [
                                'epin' => [
                                    'code' => 'WFWK-C738-AWZS-QYKG .',
                                    'desc' => 'Test ürün',
                                    'id' => '5081'
                                ]
                            ]
                        ],
                        // Sipariş 2: Çoklu (2 epin) içeren sipariş
                        [
                            'EXTRA' => [],
                            'DURUM_KODU' => '000',
                            'SIPARIS_NO' => '26080519153501',
                            'SIPARIS_DURUMU' => '2',
                            'KONTROL_TARIHI' => '08-08-2026 12:10:38',
                            'SIPARIS_DURUMU_ACIKLAMA' => 'Siparişiniz Tamamlandı',
                            'SIPARIS_TUTARI' => '0.0010',
                            'epin_list' => [
                                'epin' => [
                                    [
                                        'code' => 'WFWK-C738-AWZS-QYKG .',
                                        'desc' => 'Test ürün',
                                        'id' => '5081'
                                    ],
                                    [
                                        'code' => 'AJ3Y-QK2C-EZC2-VFH2 .',
                                        'desc' => 'Test ürün',
                                        'id' => '5082'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        // 3. Order servisimize sahte istemcimizi veriyoruz
        $orderService = new Order($mockApiClient);
        $orders = $orderService->getOrders('2026-08-01', '2026-08-08');

        // 4. DOĞRULAMALAR (Assertions):
        $this->assertIsArray($orders);
        $this->assertCount(2, $orders); // 2 sipariş döndü mü?

        // Sipariş 1 Doğrulamaları:
        $this->assertEquals('26080519063401', $orders[0]['SIPARIS_NO']);
        $this->assertEquals('Siparişiniz Tamamlandı', $orders[0]['SIPARIS_DURUMU_ACIKLAMA']);
        $this->assertEquals(0.0010, $orders[0]['SIPARIS_TUTARI']);
        $this->assertCount(1, $orders[0]['EPIN_LIST']); // Tek epin düzgün diziye çevrildi mi?

        // Sipariş 2 (Çoklu Epin) Doğrulamaları:
        $this->assertEquals('26080519153501', $orders[1]['SIPARIS_NO']);
        $this->assertCount(2, $orders[1]['EPIN_LIST']); // 2 epin de dizide var mı?
        $this->assertEquals('WFWK-C738-AWZS-QYKG .', $orders[1]['EPIN_LIST'][0]['code']);
        $this->assertEquals('AJ3Y-QK2C-EZC2-VFH2 .', $orders[1]['EPIN_LIST'][1]['code']);
    }


    public function testGetOrderStatusReturnsFormattedStatus(): void
    {
        // 1. Sahte (Stub) API İstemcisi oluşturuyoruz
        $mockApiClient = $this->createStub(TurkpinApiClient::class);

        // 2. Turkpin API'sinin gönderdiği GERÇEK XML yanıt dizisini mock olarak tanımlıyoruz
        $mockApiClient->method('execute')->willReturn([
            'params' => [
                'DURUM_KODU' => '000',
                'SIPARIS_NO' => '26080519153501',
                'SIPARIS_DURUMU' => '2',
                'KONTROL_TARIHI' => '08-08-2026 12:18:25',
                'SIPARIS_DURUMU_ACIKLAMA' => 'Siparişiniz Tamamlandı',
                'SIPARIS_TUTARI' => '0.0010',
                'epin_list' => [
                    'epin' => [
                        'code' => 'AJ3Y-QK2C-EZC2-VFH2 .',
                        'desc' => 'Test ürün',
                        'id' => '5082'
                    ]
                ]
            ]
        ]);


        // 3. Order servisimize sahte istemcimizi veriyoruz
        $orderService = new Order($mockApiClient);
        $status = $orderService->getOrderStatus('26080519153501');

        // 4. DOĞRULAMALAR (Assertions):
        $this->assertIsArray($status);

        // Sipariş 1 Doğrulamaları:
        $this->assertEquals('26080519153501', $status['SIPARIS_NO']);
        $this->assertEquals('Siparişiniz Tamamlandı', $status['SIPARIS_DURUMU_ACIKLAMA']);
        $this->assertEquals(0.001, $status['SIPARIS_TUTARI']);
        $this->assertCount(1, $status['EPIN_LIST']); // Tek epin düzgün diziye çevrildi mi?
    }
}
