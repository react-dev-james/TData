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

    protected $exclusions = [
        'fast lane',
        'express lane',
        'club and parking upgrade',
        'vs.',
        'v.'
    ];

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
        $listings = \App\Listing::all();

        /* Check lookups table first */
        foreach ($listings as $listing) {

            /* If listing has match, skip it */
            if ($listing->data->count() > 0 || $listing->slug == 'pnk-beautiful-trauma-world-tour') {
                continue;
            }

            /* Check lookups table */
            $lookup = \App\EventLookup::where( "event_name", $listing->event_name )->orderBy("is_auto",'ASC')->orderBy('confidence','DESC')->first();
            if ( $lookup ) {
                $dataLookup = \App\Data::where("category", $lookup->match_name)->first();
                if ($dataLookup) {

                    $this->info("Manual lookup match found for" . $listing->event_name);
                    $listing->performer = $dataLookup->category;
                    $listing->save();

                    /* Create new entry in the listing_data pivot table */
                    $listing->data()->sync( [ $dataLookup->id ], [ 'confidence' => 100 ] );
                }
            }

            /* Exclusions */
            if ($listing->category == 'Sports') {
                $listing->forceDelete();
                continue;
            }

            /* Exclusion list */
            foreach ($this->exclusions as $exclusion) {
                if (stristr($listing->event_name, $exclusion) !== false) {
                    $listing->forceDelete();
                    break;
                }
            }

        }

        /* Check for matches based on event data names */
        $numMatches = 0;
        foreach ($data as $item) {

            $listings = \App\Listing::where("event_name",$item->category)->orWhere( "event_name", 'like', '%' . $item->category . "%" )->get();

            foreach ($listings as $listing) {

                if ($listing->status == 'excluded') {
                    continue;
                }

                /* Skip listings with matching 100% confidence lookups */
                $lookup = \App\EventLookup::where( "event_name", $listing->event_name )->orderBy( "is_auto", 'ASC' )->orderBy( 'confidence', 'DESC' )->first();
                if ($lookup && $lookup->confidence >= 100) {
                    continue;
                }

                /* Verify match */
                if ( strlen( $item->category ) <= 5 && strtolower($item->category) != strtolower($listing->event_name)) {
                    echo "X\n";
                    continue;
                }

                /* Calculate string similarity */
                $distance = levenshtein($item->category, $listing->event_name);
                $similarity = similar_text($item->category, $listing->event_name);
                $confidence = max(0,($similarity / max(1, (strlen($listing->event_name) - strlen($item->category)))) * 100 - $distance);

                if ($confidence >= 5 || strtolower($item->category) == strtolower($listing->event_name)) {
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
