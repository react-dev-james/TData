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
        //$listings = \App\Listing::where("id",4744)->get();
        $listings = \App\Listing::all();
        foreach ($listings as $listing) {
            /* Adjust sales and cost pricing */
            $this->adjustSalesAndCosts($listing);

            /* Calculate ROI for listing */
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

    public function adjustSalesAndCosts(\App\Listing $listing) {

        $canadaAdjustment = 0.8;
        $daysOfWeek = [
            'Sunday' => 0.89,
            'Monday' => 0.85,
            'Tuesday' => 0.86,
            'Wednesday' => 0.86,
            'Thursday' => 0.97,
            'Friday' =>  1.02,
            'Saturday' => 1.15
        ];

        if ($listing->country == 'CA' && !$listing->adjusted) {
            $this->info( "Adjusting ticket price for Canadian tickets");
            $listing->low_ticket_price = round($listing->low_ticket_price * $canadaAdjustment);
            $listing->high_ticket_price = round($listing->high_ticket_price * $canadaAdjustment);
            $listing->avg_ticket_price = round($listing->avg_ticket_price * $canadaAdjustment);
            $listing->adjusted = true;
            $listing->save();
        }

        /* Exit early if there is no associated data with this listing */
        $data = $listing->data->first();
        if (!$data || $data->adjusted) {
            return;
        }

        $dayAdjustment = 1;
        if (isset($daysOfWeek[$listing->event_day])) {
            $dayAdjustment = $daysOfWeek[$listing->event_day];
            $this->info( "Adjusting sale price by " . $dayAdjustment );
            $data->avg_sale_price = round($data->avg_sale_price * $dayAdjustment);
            $data->avg_sale_price_past = round($data->avg_sale_price_past * $dayAdjustment);
            $data->adjusted = true;
            $data->save();
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
