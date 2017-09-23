<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportTicketData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import raw ticket data.';

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
        $data = file_get_contents(__DIR__ . "/Data/ticketdata.csv");
        $lines = explode("\n", $data);

        $this->info(count($lines) . " Entries Found For Importing");

        foreach ($lines as $line) {
            $item = [];
            $item['source'] = 'ticketdata';
            $line = trim($line);

            list($item['category'], $item['upcoming_events'], $item['avg_sale_price'], $item['avg_quantity'], $item['yesterday_sales'],$item['total_sales'], $item['total_listed'], $item['avg_listed'], $item['past_events'], $item['avg_sale_price_past'], $item['avg_quantity_past'], $item['total_sales_past'],$item['volume_past'] ) = explode("|", $line);


            if ( empty( $item['category'] ) ) {
                echo "x";
                continue;
            }

            $ticketItem = array_map(function($val) {
                $val = trim(str_replace(['$',','], '', $val));
                if (empty($val) || $val == '#DIV/0!' || $val == "-") {
                    $val = 0;
                }
                return $val;
            }, $item);

            $ticketItem['category_slug'] = str_slug($ticketItem['category']);
            $ticketItem['name'] = $ticketItem['category'];
            $ticketItem['name_slug'] = str_slug($ticketItem['category']);

            $record = \App\Data::firstOrCreate(['category_slug' => $ticketItem['category_slug']], $ticketItem);
            if ($record->wasRecentlyCreated) {
                echo ".";
            } else {
                echo "|";
            }

        }
    }
}
