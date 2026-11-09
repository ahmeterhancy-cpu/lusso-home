<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mark_bank_transfer_order_paid(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $order = Order::create([
            'number' => 'LHC-TEST-1', 'payment_method' => 'havale', 'first_name' => 'A', 'last_name' => 'B',
            'email' => 'a@b.co', 'phone' => '1', 'address' => 'x', 'city' => 'Girne',
            'subtotal' => 100, 'total' => 100, 'track_token' => Str::random(32),
        ]);

        $this->actingAs($admin);
        Livewire::test(ListOrders::class)->callTableAction('paid', $order);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
    }
}
