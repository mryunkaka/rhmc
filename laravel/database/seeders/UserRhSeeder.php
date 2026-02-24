<?php

namespace Database\Seeders;

use App\Models\UserRh;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRhSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with user_rh.
     */
    public function run(): void
    {
        $users = [
            [
                'full_name' => 'Director',
                'citizen_id' => 'DIR001',
                'no_hp_ic' => '081234567890',
                'jenis_kelamin' => 'Laki-laki',
                'pin' => Hash::make('0000'),
                'api_token' => null,
                'role' => 'Director',
                'batch' => 1,
                'kode_nomor_induk_rs' => 'DIR001',
                'position' => 'Director',
                'tanggal_masuk' => '2020-01-01',
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'full_name' => 'Manager',
                'citizen_id' => 'MGR001',
                'no_hp_ic' => '081234567891',
                'jenis_kelamin' => 'Laki-laki',
                'pin' => Hash::make('0000'),
                'api_token' => null,
                'role' => 'Manager',
                'batch' => 1,
                'kode_nomor_induk_rs' => 'MGR001',
                'position' => 'Manager',
                'tanggal_masuk' => '2020-01-01',
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'full_name' => 'Staff',
                'citizen_id' => 'STF001',
                'no_hp_ic' => '081234567892',
                'jenis_kelamin' => 'Perempuan',
                'pin' => Hash::make('0000'),
                'api_token' => null,
                'role' => 'Staff',
                'batch' => 1,
                'kode_nomor_induk_rs' => 'STF001',
                'position' => 'Staff',
                'tanggal_masuk' => '2020-01-01',
                'is_verified' => true,
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            UserRh::create($user);
        }

        $this->command->info('UserRh created successfully:');
        $this->command->info('- Director (PIN: 0000)');
        $this->command->info('- Manager (PIN: 0000)');
        $this->command->info('- Staff (PIN: 0000)');
    }
}
