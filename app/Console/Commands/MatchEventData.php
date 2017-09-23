<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MatchEventData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:match';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Match events with ticket data.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $data = \App\Data::all();

        $numMatches = 0;
        foreach ($data as $item) {

            $listings = \App\Listing::where("event_name",$item->category)->orWhere( "event_name", 'like', '%' . $item->category . "%" )->get();

            foreach ($listings as $listing) {

                /* Verify match */
                if ( strlen( $item->category ) <= 5 && $item->category != $listing->event_name) {
                    //echo "X\n";
                    continue;
                }

                $distance = levenshtein($item->category, $listing->event_name);
                $similarity = similar_text($item->category, $listing->event_name);
                $confidence = max(0,($similarity / max(1, (strlen($listing->event_name) - strlen($item->category)))) * 100 - $distance);

                if ($confidence >= 5 || $item->category == $listing->event_name) {
                    $numMatches++;
                    $this->info( "Matching data found for " . $listing->event_name . " matched with " . $item->category );
                    $this->info( "Distance: " . $distance . " Similarity: " . $similarity );
                    $this->info( "Confidence: " . $confidence );

                    $this->saveNewMatch($listing, $item, $confidence);
                }
            }
        }

        $this->info( "Found Matches: " . $numMatches );
    }

    public function saveNewMatch(\App\Listing $listing, \App\Data $data, $confidence = 100)
    {
        /* Create new lookup in the lookups table */
        \App\EventLookup::firstOrCreate([ 'event_name' => $listing->event_name ],
        [
            'match_name' => $data->category,
            'event_slug' => str_slug($listing->event_name),
            'match_slug' => str_slug($data->category),
            'confidence' => min(100, $confidence * 3)
        ]);

        $listing->performer = $data->category;
        $listing->save();

        /* Create new entry in the listing_data pivot table */
        $listing->data()->sync([$data->id], ['confidence' => min( 100, $confidence * 3 ) ]);

    }
}
