<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Week 11 - Step 2: grow the dataset large enough to see real differences.
 *
 * Targets:
 *   - 3 venues (kept from Week 10)
 *   - >= 20,000 events
 *   - >= 200 participants
 *   - registrations for at least the first 100 events
 *
 * Searchability:
 *   - The word "firefly" appears in fewer than 1% of event descriptions
 *     (so a full-text search for it is selective).
 *   - The details->category value "astronomy" appears in fewer than 1% of
 *     events (so a JSONB filter on it is selective).
 *
 * All bulk rows are inserted in CHUNKS so seeding stays fast.
 */
class CampusEventSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------
        // 1) Three venues
        // ------------------------------------------------------------------
        $venues = collect([
            ['name' => 'Grand Auditorium', 'address' => 'PUP Main Campus, Sta. Mesa', 'capacity' => 1200],
            ['name' => 'Engineering Hall',  'address' => 'PUP Main Campus, Sta. Mesa', 'capacity' => 400],
            ['name' => 'Open Field',        'address' => 'PUP Main Campus, Sta. Mesa', 'capacity' => 3000],
        ])->map(fn (array $row) => Venue::create($row));

        $venueIds = $venues->pluck('id')->all();

        // ------------------------------------------------------------------
        // 2) 200 participants (chunk insert)
        // ------------------------------------------------------------------
        $participantRows = [];
        for ($i = 1; $i <= 200; $i++) {
            $participantRows[] = [
                'name'       => "Participant {$i}",
                'email'      => "participant{$i}@example.com",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($participantRows, 200) as $chunk) {
            DB::table('participants')->insert($chunk);
        }
        $participantIds = range(1, 200);

        // ------------------------------------------------------------------
        // 3) 20,000 events (chunk insert of 1000)
        // ------------------------------------------------------------------
        // Description fragments — combine them so descriptions are searchable.
        $intros = [
            'A hands-on workshop about',
            'An exciting seminar covering',
            'A fun gathering for fans of',
            'A practical session on',
            'A live demonstration of',
        ];
        $subjects = [
            'web development', 'data science', 'public speaking', 'robotics',
            'photography', 'creative writing', 'digital marketing', 'cybersecurity',
            'machine learning', 'mobile design', 'game development', 'cloud computing',
        ];
        $endings = [
            'open to all students.', 'with free snacks provided.',
            'led by industry professionals.', 'followed by a Q&A panel.',
            'suitable for beginners and experts alike.', 'including a small group activity.',
        ];

        // "firefly" is the rare word: it appears in only ~0.5% of events.
        $rareWord          = 'firefly';
        $rareWordEvery     = 200; // 1 in 200 = 0.5% < 1%

        // details keys
        $dressCodes = ['casual', 'business casual', 'formal', 'sports attire'];
        $categories = ['workshop', 'seminar', 'competition', 'social', 'exhibit'];
        $rareCategory = 'astronomy';   // appears in ~0.5% of events
        $rareCategoryEvery = 200;

        $eventsCount = 20000;
        $chunkSize   = 1000;
        $buffer      = [];
        $startsAt    = now()->startOfDay();

        for ($i = 1; $i <= $eventsCount; $i++) {
            $intro    = $intros[array_rand($intros)];
            $subject  = $subjects[array_rand($subjects)];
            $ending   = $endings[array_rand($endings)];

            // Inject the rare word only every 200th event.
            $rareBit  = ($i % $rareWordEvery === 0) ? " {$rareWord}" : '';
            $description = "{$intro} {$subject}{$rareBit}, {$ending}";

            // details payload.
            $category = ($i % $rareCategoryEvery === 0)
                ? $rareCategory
                : $categories[array_rand($categories)];

            $details = [
                'dress_code' => $dressCodes[array_rand($dressCodes)],
                'capacity'   => rand(50, 1500),
                'tags'       => [$subject, $category],
                'category'   => $category,
            ];

            $buffer[] = [
                'venue_id'    => $venueIds[array_rand($venueIds)],
                'title'       => "Event {$i}: " . ucfirst($subject),
                'description' => $description,
                'details'     => json_encode($details),
                'starts_at'   => $startsAt->copy()->addHours($i * 2)->format('Y-m-d H:i:s'),
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            if (count($buffer) >= $chunkSize) {
                DB::table('events')->insert($buffer);
                $buffer = [];
            }
        }
        if (! empty($buffer)) {
            DB::table('events')->insert($buffer);
        }

        // ------------------------------------------------------------------
        // 4) Registrations for the first 100 events (chunk insert)
        // ------------------------------------------------------------------
        $regBuffer = [];
        $now       = now()->format('Y-m-d H:i:s');
        for ($eventId = 1; $eventId <= 100; $eventId++) {
            // Each of the first 100 events gets 5 to 15 participants.
            $count = rand(5, 15);
            $chosen = array_rand(array_flip($participantIds), min($count, count($participantIds)));
            foreach ((array) $chosen as $pid) {
                $regBuffer[] = [
                    'event_id'       => $eventId,
                    'participant_id' => $pid,
                    'status'         => (rand(0, 1) === 0) ? 'registered' : 'waitlisted',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }
        foreach (array_chunk($regBuffer, 500) as $chunk) {
            DB::table('event_participant')->insert($chunk);
        }
    }
}
