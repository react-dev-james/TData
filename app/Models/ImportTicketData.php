<?php
Namespace App\Models;

use Illuminate\Support\Facades\Request;
use App\DataMaster;

class ImportTicketData
{
    public static function import()
    {

        /* delete old lines */


        if (($handle = fopen("test.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
                echo "<p> $num fields in line $row: <br /></p>\n";
                $row++;
                for ($c=0; $c < $num; $c++) {
                    echo $data[$c] . "<br />\n";
                }
            }
            fclose($handle);
        }


        // set line counter
        $line_number = 1;

        // open file
        //$data = file_get_contents(storage_path('app/Master_Table.csv'), 'r');
        $handle = fopen(storage_path('app/Master_Table.csv'), 'r');

        //$lines = explode("\r", $data);

       /// echo count($lines) . " Entries Found For Importing";

        // process it line by line
        foreach( $line = fgetcsv($handle, 2000, ","))
        {
            echo $line_number . "\n";

            print_r($line);

            if($line_number > 20) break;
            /*
            $ticketItem = [];

            // assign line data to array
            list(
                $ticketItem['category'],
                $ticketItem['total_events'],
                $ticketItem['total_sold'],
                $ticketItem['total_vol'],
                $ticketItem['weighted_avg'],
                $ticketItem['tot_per_event'],
                $ticketItem['td_events'],
                $ticketItem['td_tix_sold'],
                $ticketItem['td_vol'],
                $ticketItem['tn_events'],
                $ticketItem['tn_tix_sold'],
                $ticketItem['tn_vol'],
                $ticketItem['tn_avg_sale'],
                $ticketItem['levi_events'],
                $ticketItem['levi_tix_sold'],
                $ticketItem['levi_vol'],
                $ticketItem['si_events'],
                $ticketItem['si_tix_sold'],
                $ticketItem['si_vol'],
                $ticketItem['sfc_roi'],
                $ticketItem['sfc_roi_dollar'],
                $ticketItem['sfc_cogs']
             ) = preg_split("/[\t]/", $line);

            // assign category
            $category = $ticketItem['category'];

            // if there is no category or if it is the header line, then move to next line
            if ( empty($category) || ($line_number ===1 && stripos($category, 'category') !== false) ) {
                continue;
            }

            // clean up all the fields
            $ticketItem = array_map(function($val) {
                // trim and remove dollar signs
                $val = trim(str_replace(['$'], '', $val));

                // check for empty and invalid values adn set to 0
                if (empty($val) || $val == '#DIV/0!' || $val == "-") {
                    $val = 0;
                }

                // set NaN to null
                if( strtolower($val) === 'nan') {
                    $val = null;
                }

                return $val;
            }, $ticketItem);

            // set slug
            $ticketItem['category_slug'] = str_slug($category);

            // insert if new item or else update
            $record = DataMaster::updateOrCreate(['category_slug' => $ticketItem['category_slug']], $ticketItem);

            // response of if it was created or updated
            if ($record->wasRecentlyCreated) {
                echo ".";
            } else {
                echo "|";
            }
            */
            // increment line number
            $line_number++;
        }

        // close file
        fclose($handle);
    }
}