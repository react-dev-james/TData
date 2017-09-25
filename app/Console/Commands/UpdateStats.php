<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update stats for listings.';

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
        //$listings = \App\Listing::where("id",6387)->get();
        $listings = \App\Listing::all();
        foreach ($listings as $listing) {
            $roi = $this->calcRoi($listing);
            if ($roi != 0) {
                $this->info( "ROI for " . $listing->event_name . " is " . $roi . "%" );
            }

            \App\Stat::updateOrCreate(['listing_id' => $listing->id],[
                'roi_sh' => $roi,
                'listing_id' => $listing->id
            ]);

        }
    }

    public function calcRoi(\App\Listing $listing )
    {
        $data = $listing->data->first();
        if (!$data || ($data->total_sales + $data->total_sales_past) === 0) {
            return 0;
        }

        if ( intval( $listing->high_ticket_price ) === 0) {
            return 0;
        }

        $total = ($data->avg_sale_price * $data->total_sales) + ($data->avg_sale_price_past * $data->total_sales_past);
        $roi = ($total / ($data->total_sales + $data->total_sales_past) * 0.925) / (intval($listing->high_ticket_price) * 1.15 + 5);
        $roi = round(($roi - 1) * 100);
        return $roi;

    }

}
