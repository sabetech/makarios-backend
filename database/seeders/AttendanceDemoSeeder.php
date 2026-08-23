<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Church, Stream, Region, Zone, Bacenta, Member, Service, ServiceType, MemberAttendance, UserChurchInfo};
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class AttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup roles
        $roles = ['Super Admin', 'Bishop', 'Stream Lead', 'Region Lead', 'Zone Lead', 'Bacenta Leader'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // 2. Create login user
        $user = User::firstOrCreate(
            ['email' => 'admin@makarios.com'],
            [
                'name' => 'Elisha Admin',
                'password' => bcrypt('password'),
                'img_url' => 'https://ui-avatars.com/api/?name=Elisha+Admin&background=580B1E&color=fff',
            ]
        );
        $user->assignRole('Bacenta Leader');

        // 3. Create bishop
        $bishop = User::firstOrCreate(
            ['email' => 'bishop@makarios.com'],
            [
                'name' => 'Bishop Kofi',
                'password' => bcrypt('password'),
                'img_url' => 'https://ui-avatars.com/api/?name=Bishop+Kofi&background=580B1E&color=fff',
            ]
        );
        $bishop->assignRole('Bishop');

        // Second bacenta leader (for testing cross-bacenta scoping)
        $faithLeader = User::firstOrCreate(
            ['email' => 'faith@makarios.com'],
            [
                'name' => 'Faith Leader',
                'password' => bcrypt('password'),
                'img_url' => 'https://ui-avatars.com/api/?name=Faith+Leader&background=580B1E&color=fff',
            ]
        );
        $faithLeader->assignRole('Bacenta Leader');

        // 4. Create church hierarchy
        $church = Church::create(['name' => 'Makarios Church']);
        $stream = Stream::create([
            'name' => 'Morning Stream',
            'meeting_day' => 'Sunday',
            'meeting_time' => '09:00',
            'church_id' => $church->id,
            'stream_overseer_id' => $bishop->id,
        ]);

        $region = Region::create([
            'name' => 'Region Alpha',
            'leader_id' => $bishop->id,
            'stream_id' => $stream->id,
        ]);

        $zone = Zone::create([
            'name' => 'Zone One',
            'region_id' => $region->id,
            'leader_id' => $bishop->id,
        ]);

        $bacenta = Bacenta::create([
            'name' => 'Bacenta Grace',
            'region_id' => $region->id,
            'zone_id' => $zone->id,
            'leader_id' => $user->id,
        ]);

        $bacenta2 = Bacenta::create([
            'name' => 'Bacenta Faith',
            'region_id' => $region->id,
            'zone_id' => $zone->id,
            'leader_id' => $faithLeader->id,
        ]);

        // Link leaders to their bacentas via users_church_info
        UserChurchInfo::create([
            'user_id' => $user->id,
            'church_id' => $church->id,
            'stream_id' => $stream->id,
            'region_id' => $region->id,
            'zone_id' => $zone->id,
            'bacenta_id' => $bacenta->id,
        ]);

        UserChurchInfo::create([
            'user_id' => $faithLeader->id,
            'church_id' => $church->id,
            'stream_id' => $stream->id,
            'region_id' => $region->id,
            'zone_id' => $zone->id,
            'bacenta_id' => $bacenta2->id,
        ]);

        // 5. Create service type
        $serviceType = ServiceType::firstOrCreate(
            ['service_type' => 'Bacenta Service'],
            ['role_id' => Role::where('name', 'Bacenta Leader')->first()->id, 'church_id' => $church->id]
        );

        // 6. Create members with varying attendance patterns
        $memberData = [
            // Severe (4+ weeks absent)
            ['name' => 'Kwame Asante', 'phone' => '0241234567', 'gender' => 'male', 'bacenta_id' => $bacenta->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
            ['name' => 'Ama Mensah', 'phone' => '0251234567', 'gender' => 'female', 'bacenta_id' => $bacenta->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
            ['name' => 'Kojo Appiah', 'phone' => '0261234567', 'gender' => 'male', 'bacenta_id' => $bacenta2->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
            // Moderate (2-3 weeks absent)
            ['name' => 'Efua Owusu', 'phone' => '0271234567', 'gender' => 'female', 'bacenta_id' => $bacenta->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
            ['name' => 'Yaw Boakye', 'phone' => '0201234567', 'gender' => 'male', 'bacenta_id' => $bacenta2->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
            // Mild (1 week absent)
            ['name' => 'Adwoa Poku', 'phone' => '0211234567', 'gender' => 'female', 'bacenta_id' => $bacenta->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
            // Active (0 absences - present last service)
            ['name' => 'Kofi Agyeman', 'phone' => '0221234567', 'gender' => 'male', 'bacenta_id' => $bacenta->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
            ['name' => 'Akua Dadzie', 'phone' => '0231234567', 'gender' => 'female', 'bacenta_id' => $bacenta2->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
            ['name' => 'Nana Adu', 'phone' => '0249876543', 'gender' => 'male', 'bacenta_id' => $bacenta->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
            ['name' => 'Abena Osei', 'phone' => '0259876543', 'gender' => 'female', 'bacenta_id' => $bacenta2->id, 'region_id' => $region->id, 'zone_id' => $zone->id, 'stream_id' => $stream->id, 'church_id' => $church->id],
        ];

        $members = [];
        foreach ($memberData as $data) {
            $members[] = Member::create($data);
        }

        // 7. Create services for the last 8 weeks
        $services = [];
        for ($i = 7; $i >= 0; $i--) {
            $date = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::SUNDAY);
            $services[] = Service::create([
                'date' => $date,
                'bacenta_id' => $bacenta->id,
                'region_id' => $region->id,
                'zone_id' => $zone->id,
                'stream_id' => $stream->id,
                'church_id' => $church->id,
                'service_type_id' => $serviceType->id,
                'attendance' => rand(8, 15),
                'offering' => rand(100, 500),
                'treasurers' => 'Elisha Admin',
                'treasurer_photo' => 'https://via.placeholder.com/150',
                'service_photo' => 'https://via.placeholder.com/300',
            ]);
        }

        // 8. Create attendance records with specific patterns
        // Patterns: 1=present, 0=absent. Index 0 = oldest, index 7 = most recent service.
        // Streak counts absences backward from the most recent service.
        $attendancePatterns = [
            // Kwame Asante - 6 consecutive absences (Severe - Red)
            0 => [1,1,0,0,0,0,0,0],
            // Ama Mensah - 5 consecutive absences (Severe - Red)
            1 => [1,1,1,0,0,0,0,0],
            // Kojo Appiah - 4 consecutive absences (Severe - Red)
            2 => [1,1,1,1,0,0,0,0],
            // Efua Owusu - 3 consecutive absences (Moderate - Amber)
            3 => [1,1,1,1,1,0,0,0],
            // Yaw Boakye - 2 consecutive absences (Moderate - Amber)
            4 => [1,1,1,1,1,1,0,0],
            // Adwoa Poku - 1 consecutive absence (Mild - Yellow)
            5 => [1,1,1,1,1,1,1,0],
            // Kofi Agyeman - Active (present most recent)
            6 => [1,1,1,1,1,1,1,1],
            // Akua Dadzie - Active (present most recent)
            7 => [0,0,1,0,1,1,1,1],
            // Nana Adu - Active (present most recent)
            8 => [0,1,1,0,1,1,1,1],
            // Abena Osei - Active (present most recent)
            9 => [0,0,1,0,1,1,1,1],
        ];

        foreach ($members as $memberIndex => $member) {
            $pattern = $attendancePatterns[$memberIndex];
            foreach ($services as $serviceIndex => $service) {
                $status = $pattern[$serviceIndex] === 1 ? 'present' : 'absent';
                MemberAttendance::create([
                    'member_id' => $member->id,
                    'service_id' => $service->id,
                    'status' => $status,
                    'consecutive_absences' => 0,
                ]);
            }
        }

        // 9. Calculate and update consecutive absences (chronological order by service date)
        foreach ($members as $member) {
            $records = MemberAttendance::join('services', 'services.id', '=', 'member_attendance.service_id')
                ->where('member_attendance.member_id', $member->id)
                ->select('member_attendance.*')
                ->orderBy('services.date')
                ->get();

            $counter = 0;
            foreach ($records as $record) {
                if ($record->status === 'absent') {
                    $counter++;
                } else {
                    $counter = 0;
                }
                $record->update(['consecutive_absences' => $counter]);
            }

            // Cache the current streak on the member record
            $member->update(['current_consecutive_absences' => $counter]);
        }

        // 10. Seed severity thresholds
        $this->call(AttendanceSeverityThresholdSeeder::class);

        $this->command->info('Attendance demo data seeded successfully!');
        $this->command->info('Login: admin@makarios.com / password');
    }
}
