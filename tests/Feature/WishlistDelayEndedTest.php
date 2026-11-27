<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wishlist;
use App\Notifications\WishlistDelayEnded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WishlistDelayEndedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->user = User::factory()->create();
    }

    private function makeWishlist(array $attrs = []): Wishlist
    {
        return Wishlist::forceCreate(array_merge([
            'user_id' => $this->user->id,
            'item_name' => 'Headphone',
            'price' => 1500000,
            'delay_days' => 7,
            'delay_until' => now('Asia/Jakarta')->toDateString(),
            'status' => 'delayed',
        ], $attrs));
    }

    public function test_mengirim_notifikasi_saat_delay_until_hari_ini_dan_mengubah_status(): void
    {
        $w = $this->makeWishlist();

        $this->artisan('wishlist:notify-delay-ended')->assertSuccessful();

        Notification::assertSentTo($this->user, WishlistDelayEnded::class,
            fn ($n) => $n->wishlist->is($w));
        $this->assertSame('pending_decision', $w->fresh()->status);
    }

    public function test_tetap_mengirim_wishlist_yang_terlewat(): void
    {
        $this->makeWishlist(['delay_until' => now('Asia/Jakarta')->subDay()->toDateString()]);

        $this->artisan('wishlist:notify-delay-ended');

        Notification::assertSentTo($this->user, WishlistDelayEnded::class);
    }

    public function test_tidak_mengirim_kalau_delay_until_masih_besok(): void
    {
        $this->makeWishlist(['delay_until' => now('Asia/Jakarta')->addDay()->toDateString()]);

        $this->artisan('wishlist:notify-delay-ended');

        Notification::assertNothingSent();
    }

    public function test_mengabaikan_status_lain_delay_until_null_dan_yang_dihapus(): void
    {
        $this->makeWishlist(['status' => 'bought']);
        $this->makeWishlist(['status' => 'cancelled']);
        $this->makeWishlist(['delay_until' => null]);
        $this->makeWishlist()->delete(); // soft delete

        $this->artisan('wishlist:notify-delay-ended');

        Notification::assertNothingSent();
    }

    public function test_tidak_mengirim_dobel_walau_command_dijalankan_dua_kali(): void
    {
        $this->makeWishlist();

        $this->artisan('wishlist:notify-delay-ended');
        $this->artisan('wishlist:notify-delay-ended');

        Notification::assertSentToTimes($this->user, WishlistDelayEnded::class, 1);
    }

    public function test_memakai_tanggal_wib_bukan_utc(): void
    {
        // 00:30 WIB tgl 27 = 17:30 UTC tgl 26
        $this->travelTo(Carbon::parse('2026-11-27 00:30', 'Asia/Jakarta'));
        $this->makeWishlist(['delay_until' => '2026-11-27']);

        $this->artisan('wishlist:notify-delay-ended');

        Notification::assertSentTo($this->user, WishlistDelayEnded::class);
    }

    public function test_dikirim_ke_channel_database_dan_broadcast_dengan_payload_benar(): void
    {
        $w = $this->makeWishlist();

        $this->artisan('wishlist:notify-delay-ended');

        Notification::assertSentTo($this->user, WishlistDelayEnded::class,
            function ($n, array $channels) use ($w) {
                $data = $n->toArray($this->user);

                return in_array('database', $channels)
                    && in_array('broadcast', $channels)
                    && $data['wishlist_id'] === $w->id
                    && $data['item_name'] === 'Headphone';
            });
    }
}
