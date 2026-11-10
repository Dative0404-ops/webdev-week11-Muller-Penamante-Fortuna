<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Event = something that happens at one venue and that participants join.
 *
 * Three links are declared here:
 *   - venue()        : the "one" side of venue -> events (belongsTo)
 *   - participants() : many-to-many with Participant via the event_participant pivot
 *   - notes()        : polymorphic notes attached to an event
 */
class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'title',
        'description',
        'details',
        'starts_at',
    ];

    // Cast stored values into rich types automatically.
    // `details` is a PostgreSQL jsonb column; Laravel decodes it to an array.
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'details'   => 'array',
        ];
    }

    // ------------------------------------------------------------------
    // LOCAL SCOPES (Week 11 - Step 3)
    //
    // A local scope is a reusable query fragment we can chain. Two are defined here;
    // both accept a parameter, and we chain them together in the controller.
    // ------------------------------------------------------------------

    /**
     * Scope: only events that start at or after the given date/time.
     * Usage: Event::upcoming('2026-12-01')->get()
     */
    public function scopeUpcoming($query, $from = null)
    {
        $from = $from ?? now();
        return $query->where('starts_at', '>=', $from);
    }

    /**
     * Scope: only events whose details->capacity is at least $min.
     * This reads the `capacity` key inside the JSONB `details` column.
     * Usage: Event::minCapacity(500)->get()
     */
    public function scopeMinCapacity($query, int $min)
    {
        // The arrow form: details->>'capacity' is cast to integer for comparison.
        return $query->whereRaw("(details->>'capacity')::int >= ?", [$min]);
    }

    /**
     * Scope: only events whose details->category equals $category.
     * Usage: Event::category('workshop')->get()
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('details->category', $category);
    }

    /**
     * One-to-many (inverse): THIS event happens at ONE venue.
     * The foreign key venue_id lives on this (events) table.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Many-to-many: THIS event has MANY participants, and each participant
     * can join MANY events. The link table is event_participant.
     * withPivot('status') lets us read the extra "status" column of a registration.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class)
                    ->withPivot('status') // expose the pivot column on each related row
                    ->withTimestamps();   // keep the pivot's created_at/updated_at filled
    }

    /**
     * Polymorphic: THIS event owns MANY notes.
     * morphMany() stores notable_type = App\Models\Event on each note.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }
}
