<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\Amenity;
use App\Models\Branch;
use App\Models\CorporateAccount;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\GuestContact;
use App\Models\GuestDocument;
use App\Models\GuestNote;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Builds a realistic, browsable demo dataset for one hotel group across two branches.
 * Depends on RolePermissionSeeder having already run (roles must exist before staff
 * users are assigned to them).
 */
class HotelDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Aurora Hotels',
            'slug' => 'aurora-hotels',
            'default_currency' => 'USD',
        ]);

        $branches = collect([
            ['name' => 'Aurora Downtown', 'city' => 'Chicago'],
            ['name' => 'Aurora Beachfront', 'city' => 'Miami'],
        ])->map(fn (array $attrs) => Branch::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $attrs['name'],
            'code' => Str::upper(Str::substr($attrs['city'], 0, 3)) . '-01',
            'city' => $attrs['city'],
            'country' => 'United States',
        ]));

        $amenities = collect(['Free Wi-Fi', 'Air Conditioning', 'Mini Bar', 'City View', 'Balcony', 'Room Service', 'Flat-Screen TV', 'Safe'])
            ->map(fn (string $name) => Amenity::firstOrCreate(['name' => $name], ['icon' => 'heroicon-o-check-circle']));

        $superAdminRole = Role::where('name', 'Super Administrator')->firstOrFail();

        $superAdmin = User::factory()->create([
            'name' => 'System Administrator',
            'email' => 'admin@aurorahotels.test',
            'tenant_id' => $tenant->id,
        ]);
        $superAdmin->assignRole($superAdminRole);

        $owner = User::factory()->create([
            'name' => 'Aurora Owner',
            'email' => 'owner@aurorahotels.test',
            'tenant_id' => $tenant->id,
        ]);
        $owner->assignRole('Hotel Owner');

        $corporateAccount = CorporateAccount::factory()->create([
            'tenant_id' => $tenant->id,
            'company_name' => 'Globex Travel Partners',
        ]);

        $rooms = collect();

        foreach ($branches as $branch) {
            $roomTypes = collect(['Standard', 'Deluxe', 'Executive Suite'])->map(function (string $name) use ($branch, $amenities) {
                $roomType = RoomType::factory()->create([
                    'branch_id' => $branch->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                ]);

                $roomType->amenities()->attach($amenities->random(3)->pluck('id'));

                return $roomType;
            });

            $branchManager = User::factory()->create([
                'name' => "{$branch->city} Branch Manager",
                'email' => Str::slug($branch->city) . '.manager@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $branchManager->assignRole('Branch Manager');
            $this->assignToBranch($branchManager, $branch, 'Branch Manager');

            $receptionist = User::factory()->create([
                'name' => "{$branch->city} Receptionist",
                'email' => Str::slug($branch->city) . '.receptionist@aurorahotels.test',
                'tenant_id' => $tenant->id,
                'current_branch_id' => $branch->id,
            ]);
            $receptionist->assignRole('Receptionist');
            $this->assignToBranch($receptionist, $branch, 'Receptionist');

            foreach ($roomTypes as $roomType) {
                $branchRooms = Room::factory(5)->create([
                    'branch_id' => $branch->id,
                    'room_type_id' => $roomType->id,
                ]);

                $rooms = $rooms->merge($branchRooms);
            }
        }

        $guests = Guest::factory(25)->create(['tenant_id' => $tenant->id]);

        $guests->take(3)->each(fn (Guest $guest) => GuestDocument::factory()->create(['guest_id' => $guest->id]));
        $guests->take(3)->each(fn (Guest $guest) => GuestContact::factory()->create(['guest_id' => $guest->id]));

        $vipGuest = Guest::factory()->vip()->create(['tenant_id' => $tenant->id]);
        GuestNote::factory()->alert()->create(['guest_id' => $vipGuest->id, 'created_by_user_id' => $superAdmin->id]);

        Guest::factory()->blacklisted()->create(['tenant_id' => $tenant->id]);

        $roomsByBranch = $rooms->groupBy('branch_id');

        // Past, settled stays.
        $branches->each(function (Branch $branch) use ($guests, $roomsByBranch, $corporateAccount) {
            foreach (range(1, 5) as $i) {
                $this->createSettledStay($branch, $guests->random(), $roomsByBranch->get($branch->id)->random(), $i % 4 === 0 ? $corporateAccount : null);
            }
        });

        // Guests currently in-house.
        $branches->each(function (Branch $branch) use ($guests, $roomsByBranch) {
            foreach (range(1, 3) as $i) {
                $this->createInHouseStay($branch, $guests->random(), $roomsByBranch->get($branch->id)->random());
            }
        });

        // Upcoming, confirmed reservations.
        $branches->each(function (Branch $branch) use ($guests) {
            foreach (range(1, 6) as $i) {
                Reservation::factory()->create([
                    'branch_id' => $branch->id,
                    'guest_id' => $guests->random()->id,
                ]);
            }
        });

        // Cancelled reservation.
        $branches->each(function (Branch $branch) use ($guests) {
            Reservation::factory()->cancelled()->create([
                'branch_id' => $branch->id,
                'guest_id' => $guests->random()->id,
            ]);
        });

        // Waitlist entry.
        $firstBranch = $branches->first();
        WaitlistEntry::factory()->create([
            'branch_id' => $firstBranch->id,
            'guest_id' => $guests->random()->id,
            'room_type_id' => RoomType::where('branch_id', $firstBranch->id)->first()->id,
        ]);
    }

    private function assignToBranch(User $user, Branch $branch, string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);
    }

    private function createSettledStay(Branch $branch, Guest $guest, Room $room, ?CorporateAccount $corporateAccount): void
    {
        $reservation = Reservation::factory()->checkedOut()->create([
            'branch_id' => $branch->id,
            'guest_id' => $guest->id,
            'corporate_account_id' => $corporateAccount?->id,
            'arrival_date' => now()->subDays(10),
            'departure_date' => now()->subDays(7),
        ]);

        ReservationRoom::factory()->create([
            'reservation_id' => $reservation->id,
            'room_type_id' => $room->room_type_id,
            'room_id' => $room->id,
            'rate_cents' => $room->roomType->base_rate_cents,
        ]);

        $nights = 3;
        $roomChargeCents = $room->roomType->base_rate_cents * $nights;
        $taxCents = (int) round($roomChargeCents * 0.12);

        $folio = Folio::factory()->closed()->create([
            'branch_id' => $branch->id,
            'reservation_id' => $reservation->id,
            'guest_id' => $guest->id,
        ]);

        $folio->charges()->create([
            'charge_type' => ChargeType::Room,
            'description' => "{$nights} night(s) — {$room->roomType->name}",
            'amount_cents' => $roomChargeCents,
            'charge_date' => now()->subDays(7)->toDateString(),
        ]);

        $folio->charges()->create([
            'charge_type' => ChargeType::Tax,
            'description' => 'Occupancy tax',
            'amount_cents' => $taxCents,
            'charge_date' => now()->subDays(7)->toDateString(),
        ]);

        Payment::factory()->create([
            'branch_id' => $branch->id,
            'folio_id' => $folio->id,
            'amount_cents' => $roomChargeCents + $taxCents,
        ]);
    }

    private function createInHouseStay(Branch $branch, Guest $guest, Room $room): void
    {
        $reservation = Reservation::factory()->checkedIn()->create([
            'branch_id' => $branch->id,
            'guest_id' => $guest->id,
            'arrival_date' => now()->subDays(1),
            'departure_date' => now()->addDays(2),
        ]);

        ReservationRoom::factory()->create([
            'reservation_id' => $reservation->id,
            'room_type_id' => $room->room_type_id,
            'room_id' => $room->id,
            'rate_cents' => $room->roomType->base_rate_cents,
        ]);

        $room->update(['status' => RoomStatus::Occupied, 'housekeeping_status' => HousekeepingStatus::Dirty]);

        $folio = Folio::factory()->create([
            'branch_id' => $branch->id,
            'reservation_id' => $reservation->id,
            'guest_id' => $guest->id,
        ]);

        $folio->charges()->create([
            'charge_type' => ChargeType::Room,
            'description' => "1 night — {$room->roomType->name}",
            'amount_cents' => $room->roomType->base_rate_cents,
            'charge_date' => now()->subDays(1)->toDateString(),
        ]);

        $folio->update(['balance_cents' => $room->roomType->base_rate_cents]);
    }
}
