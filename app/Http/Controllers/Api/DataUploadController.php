<?php

namespace App\Http\Controllers\Api;

use App\Console\Commands\ImportTicketDataMaster;
use App\Reference;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ImportDataMaster;
use Illuminate\Support\Facades\Log;

class DataUploadController extends Controller
{

    public function __construct()
    {
        $this->middleware( "auth" );
    }

    public function upload(Request $request)
    {
        // save file
        $path = $request->file('data-master')->storeAs('', 'data-master.csv');

        // file was saved successfully so run the import
        if( $path !== null ) {
            // run import
            $result = ImportDataMaster::import();

            return [
                'message' => $result . ' rows were successfully processed.',
                'status'  => 'success',
            ];

        // file could not be saved so send error.
        } else {
            throw new \Exception('The file could not be saved');
        }
    }
}

