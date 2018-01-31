<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportTicketNetwork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:importtn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import raw ticket network data.';

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
        $data = file_get_contents(__DIR__ . "/Data/ticketnetwork.csv");
        $lines = explode("\n", $data);

        $this->info(count($lines) . " Entries Found For Importing");
        $items = [];
        foreach ($lines as $line) {
            $item = [];
            $item['source'] = 'ticketnetwork';
            $line = trim($line);

            list( $item['event_name'],
                $item['parent_category'],
                $item['child_category'],
                $item['available_tixs'],
                $item['value'],
                $item['avg_tix_price'],
                $item['last_event_date'],
                $item['venues'],
                $item['dates'],
                $item['tix_sold_in_date_range'],
                $item['avg_sold_price_in_date_range'],
                $item['last_refreshed']) = explode(",", $line);

            /* Skip empty entries and sports events */
            if ( empty( $item['event_name'] ) || $item['parent_category'] == 'SPORTS') {
                continue;
            }

            $items[] = $item;
        }

        /* Aggregate results with weighted averages for matching event names */
        $aggregateItems = [];
        foreach ($items as $item) {
            $item['avg_tix_price'] = intval( $item['avg_tix_price']);
            $item['value'] = intval( $item['value']);
            $item['avg_sold_price_in_date_range'] = intval( $item['avg_sold_price_in_date_range']);
            $item['tix_sold_in_date_range'] = intval( $item['tix_sold_in_date_range']);
            $item['dates'] = intval( $item['dates']);

            $itemKey = str_slug($item['event_name']);
            if (isset($aggregateItems[$itemKey])) {

                $this->info('Aggregating data for ' . $item['event_name']);

                /* Calc and update the weighted average */
                $existing = $aggregateItems[$itemKey];
                $totalWeight = ($existing['avg_sold_price_in_date_range'] * $existing['tix_sold_in_date_range'])
                                + ($item['avg_sold_price_in_date_range'] * $item['tix_sold_in_date_range']);

                $totalTixSold = $existing['tix_sold_in_date_range'] + $item['tix_sold_in_date_range'];
                $numDates = $existing['tn_events'] + $item['dates'];

                $aggregateItems[$itemKey]['tix_sold_in_date_range'] = $totalTixSold;
                $aggregateItems[$itemKey]['avg_sold_price_in_date_range'] = round($totalWeight / max(1, $totalTixSold));
                $aggregateItems[$itemKey]['tn_events'] = $numDates;

            } else {
                $aggregateItems[$itemKey] = [
                    'event' => $item['event_name'],
                    'tix_sold_in_date_range' => $item['tix_sold_in_date_range'],
                    'avg_sold_price_in_date_range' => $item['avg_sold_price_in_date_range'],
                    'tn_events' => $item['dates']
                ];
            }
        }

        $this->info(count($aggregateItems) . ' Aggregated Items Found, Checking for Matches' );
        $lookups = \App\EventLookup::orderBy('is_auto','ASC')->get();

        /* Reset listing ticketnetwork data */
        $listings = \App\Listing::all();
        $listings->each->resetTicketNetwork();
        $this->info("Reset ticket network stats.");

        foreach($aggregateItems as $item) {
            if ( $item['tix_sold_in_date_range'] > 100 ) {
                // $this->info($item['event'] . " has " . $item['tix_sold_in_date_range'] . " at price of " . $item['avg_sold_price_in_date_range']);
            }

            /* Check for matching events via lookup table */
            foreach ($lookups as $lookup) {
                if ($lookup->match_name == $item['event'] && $lookup->confidence >= 100) {
                    $listings = \App\Listing::where( "event_name", $lookup->event_name )->get();
                    if (count($listings) > 0) {
                        $this->info( "Found " . count( $listings ) . " Matching " . $item['event'] );
                        foreach ($listings as $listing) {
                            $listing->updateTicketNetworkStats($item['tix_sold_in_date_range'], $item['avg_sold_price_in_date_range'], $item['tn_events'], true);
                        }
                    }

                    /* Go to next item if this was a manual lookup so we don't match auto lookup events */
                    if (!$lookup->is_auto) {
                        $this->info("Continuing on manual lookup match.");
                        continue 2;
                    }
                }
            }
        }
    }
}
