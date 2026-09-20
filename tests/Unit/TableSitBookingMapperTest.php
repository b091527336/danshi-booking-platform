<?php

namespace Tests\Unit;

use App\Services\TableSit\BookingMapper;
use PHPUnit\Framework\TestCase;

class TableSitBookingMapperTest extends TestCase
{
    public function test_it_maps_common_booking_fields(): void
    {
        $mapped = (new BookingMapper())->booking([
            'id' => 'bk_123',
            'status' => 'accepted',
            'startTime' => '2026-09-20 10:00:00',
            'endTime' => '2026-09-20 11:00:00',
            'partySize' => 2,
            'service' => ['name' => '到府安裝'],
            'source' => 'google',
        ], 'Asia/Taipei');

        $this->assertSame('bk_123', $mapped['external_id']);
        $this->assertSame('confirmed', $mapped['status']);
        $this->assertSame(2, $mapped['party_size']);
        $this->assertSame('到府安裝', $mapped['service_name']);
        $this->assertSame('google', $mapped['source']);
        $this->assertSame('2026-09-20 02:00:00', $mapped['starts_at']->format('Y-m-d H:i:s'));
    }

    public function test_it_maps_nested_customer_fields(): void
    {
        $customer = (new BookingMapper())->customer([
            'customer' => [
                'fullName' => '王小明',
                'phoneNumber' => '0912345678',
                'email' => 'USER@EXAMPLE.COM',
            ],
        ]);

        $this->assertSame('王小明', $customer['name']);
        $this->assertSame('0912345678', $customer['phone']);
        $this->assertSame('user@example.com', $customer['email']);
    }
}
