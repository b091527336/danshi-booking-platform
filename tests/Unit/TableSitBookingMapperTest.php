<?php

namespace Tests\Unit;

use App\Services\TableSit\BookingMapper;
use PHPUnit\Framework\TestCase;

class TableSitBookingMapperTest extends TestCase
{
    public function test_it_maps_official_booking_resource(): void
    {
        $mapped = (new BookingMapper())->booking([
            'uid' => '550e8400-e29b-41d4-a716-446655440000',
            'status' => 'confirmed',
            'source' => 'reserve_with_google',
            'client_count' => 2,
            'start_at' => '2026-09-20T02:00:00Z',
            'end_at' => '2026-09-20T03:00:00Z',
            'services' => [
                ['uid' => 'service-1', 'name' => '到府安裝'],
            ],
            'public_notes' => [
                ['comment' => '請先電話聯絡', 'created_at' => '2026-09-19T00:00:00Z'],
            ],
        ], 'Asia/Taipei');

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $mapped['external_id']);
        $this->assertSame('confirmed', $mapped['status']);
        $this->assertSame(2, $mapped['party_size']);
        $this->assertSame('到府安裝', $mapped['service_name']);
        $this->assertSame('reserve_with_google', $mapped['source']);
        $this->assertSame('請先電話聯絡', $mapped['notes']);
    }

    public function test_it_maps_official_client_resource(): void
    {
        $customer = (new BookingMapper())->customer([
            'client' => [
                'name' => '王小明',
                'phone' => '0912345678',
                'email' => 'USER@EXAMPLE.COM',
            ],
        ]);

        $this->assertSame('王小明', $customer['name']);
        $this->assertSame('0912345678', $customer['phone']);
        $this->assertSame('user@example.com', $customer['email']);
    }

    public function test_it_reads_official_pagination_meta(): void
    {
        $mapper = new BookingMapper();

        $this->assertTrue($mapper->hasNextPage(['meta' => ['page' => 1, 'total_pages' => 2]], 1));
        $this->assertFalse($mapper->hasNextPage(['meta' => ['page' => 2, 'total_pages' => 2]], 2));
    }
}
