<?php
namespace budisteikul\toursdk\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use budisteikul\toursdk\Helpers\TaskHelper;
use budisteikul\toursdk\Helpers\WiseHelper;
use budisteikul\toursdk\Helpers\LogHelper;

class TaskController extends Controller
{
	public function task(Request $request)
    {
    	LogHelper::log_webhook($request->getContent());
        
        $json = $request->getContent();
        
        TaskHelper::delete($json);

		$data = json_decode($json);

        if($data->app=="wise")
        {
            if($data->token==env('WISE_TOKEN'))
            {
                $tw = new WiseHelper();
                $quote = $tw->postCreateQuote($data->amount,$data->currency);
                $transfer = $tw->postCreateTransfer($quote->id);
                $fund = $tw->postFundTransfer($transfer->id);
                return response('OK', 200)->header('Content-Type', 'text/plain');
            }
            return response('ERROR', 200)->header('Content-Type', 'text/plain');
        }

        return response('ERROR', 200)->header('Content-Type', 'text/plain');
    }
}
?>

