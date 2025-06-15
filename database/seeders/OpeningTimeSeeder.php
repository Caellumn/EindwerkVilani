<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OpeningTime;

class OpeningTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing opening times
        OpeningTime::truncate();

        // Hair salon opening hours
        $openingHours = [
            [
                'day' => 'monday',
                'status' => 'open',
                'open' => '09:00:00',
                'close' => '18:00:00',
            ],
            [
                'day' => 'tuesday',
                'status' => 'gesloten',
                'open' => null,
                'close' => null,
            ],
            [
                'day' => 'wednesday',
                'status' => 'gesloten',
                'open' => null,
                'close' => null,
            ],
            [
                'day' => 'thursday',
                'status' => 'open',
                'open' => '09:00:00',
                'close' => '18:00:00',
            ],
            [
                'day' => 'friday',
                'status' => 'open',
                'open' => '09:00:00',
                'close' => '18:00:00',
            ],
            [
                'day' => 'saturday',
                'status' => 'open',
                'open' => '09:00:00',
                'close' => '16:00:00',
            ],
            [
                'day' => 'sunday',
                'status' => 'open',
                'open' => '09:00:00',
                'close' => '13:00:00',
            ],
        ];

        // Note: Tuesday and Wednesday are not included as the salon is closed these days

        foreach ($openingHours as $hours) {
            OpeningTime::create($hours);
        }

        $this->command->info('Opening times seeded successfully!');
        $this->command->info('Salon is open:');
        $this->command->info('- Monday: 9:00 - 18:00');
        $this->command->info('- Tuesday: GESLOTEN');
        $this->command->info('- Wednesday: GESLOTEN');
        $this->command->info('- Thursday: 9:00 - 18:00');
        $this->command->info('- Friday: 9:00 - 18:00');
        $this->command->info('- Saturday: 9:00 - 16:00');
        $this->command->info('- Sunday: 9:00 - 13:00');
    }
} 