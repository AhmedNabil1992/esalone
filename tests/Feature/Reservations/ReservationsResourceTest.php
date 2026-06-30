<?php

namespace Tests\Feature\Reservations;

use App\Filament\Resources\Tenant\Reservations\ReservationsResource;
use App\Models\Tenant\Reservation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class ReservationsResourceTest extends TestCase
{
    public function test_reservation_model_has_correct_relationships(): void
    {
        $reservation = new Reservation;

        $this->assertInstanceOf(BelongsTo::class, $reservation->device());
        $this->assertInstanceOf(BelongsTo::class, $reservation->customer());
        $this->assertInstanceOf(BelongsTo::class, $reservation->playPrice());
    }

    public function test_reservations_resource_has_correct_metadata(): void
    {
        $this->assertEquals(Reservation::class, ReservationsResource::getModel());
        $this->assertEquals('heroicon-o-calendar', ReservationsResource::getNavigationIcon());
        $this->assertEquals('اللعب', ReservationsResource::getNavigationGroup());
    }
}
