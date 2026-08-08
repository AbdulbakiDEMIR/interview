<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\classes\Game;
use App\classes\TurkpinApiClient;

class GameTest extends TestCase
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
                'error' => '000',
                'error_desc' => 'Islem Basarili',
                'oyunListesi' => [
                    'oyun' => [
                        [
                            'id' => 1,
                            'name' => 'Game 1'
                        ],
                        [
                            'id' => 2,
                            'name' => 'Game 2'
                        ],
                        [
                            'id' => 3,
                            'name' => 'Game 3'
                        ]
                    ]
                ]
            ]
        ]);

        // 3. Order servisimize sahte istemcimizi veriyoruz
        $orderService = new Game($mockApiClient);
        $orders = $orderService->getAllGames();
        $this->assertCount(3, $orders);

    }



}
