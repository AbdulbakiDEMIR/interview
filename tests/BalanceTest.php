<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\classes\Balance;
use App\classes\TurkpinApiClient;

class BalanceTest extends TestCase
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
                'balanceInformation' => [
                    'balance' => '1000',
                    'credit' => '1000',
                    'bonus' => '123456789',
                    'spending' => '1233'
                ]
            ]
        ]);

        // 3. Order servisimize sahte istemcimizi veriyoruz
        $balanceService = new Balance($mockApiClient);
        $balance = $balanceService->getBalance();

        //Beklediğimiz sonucu assertion ediyoruz.
        $this->assertArrayHasKey('balance', $balance);
        $this->assertArrayHasKey('credit', $balance);
        $this->assertArrayHasKey('bonus', $balance);
        $this->assertArrayHasKey('spending', $balance);
        $this->assertEquals('1000', $balance['balance']);
        $this->assertEquals('1000', $balance['credit']);
        $this->assertEquals('123456789', $balance['bonus']);
        $this->assertEquals('1233', $balance['spending']);

    }



}
