<?php
namespace budisteikul\toursdk\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use budisteikul\toursdk\Helpers\TaskHelper;
use budisteikul\toursdk\Helpers\WiseHelper;
use budisteikul\toursdk\Helpers\LogHelper;

use budisteikul\toursdk\Models\Shoppingcart;

use Illuminate\Support\Facades\Mail;
use budisteikul\toursdk\Mail\BookingConfirmedMail;

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
                if(isset($quote->error))
                {
                    return response('ERROR', 200)->header('Content-Type', 'text/plain');
                }

                $transfer = $tw->postCreateTransfer($quote->id,$data->customerTransactionId);
                if(isset($transfer->error))
                {
                    return response('ERROR', 200)->header('Content-Type', 'text/plain');
                }

                $fund = $tw->postFundTransfer($transfer->id);
                

                return response('OK', 200)->header('Content-Type', 'text/plain');
            }
            return response('ERROR', 200)->header('Content-Type', 'text/plain');
        }

        if($data->app=="mail")
        {
            $shoppingcart = Shoppingcart::where('session_id',$data->session_id)->where('confirmation_code',$data->confirmation_code)->first();
            $email = $shoppingcart->shoppingcart_questions()->select('answer')->where('type','mainContactDetails')->where('question_id','email')->first()->answer;
            if($email!="")
            {
                Mail::to($email)->send(new BookingConfirmedMail($shoppingcart));
                //Mail::to(env("MAIL_PUSHOVER"))->send(new BookingConfirmedMail($shoppingcart));
            }

            
            curl_setopt_array($ch = curl_init(), array(
                CURLOPT_URL => "https://api.pushover.net/1/messages.json",
                CURLOPT_POSTFIELDS => array(
                "token" => env("PUSHOVER_KEY"),
                "user" => env("PUSHOVER_USER"),
                "title" => 'Booking Confirmed '. $shoppingcart->confirmation_code,
                "message" => '',
                ),
            ));
            curl_exec($ch);
            curl_close($ch);

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        return response('ERROR', 200)->header('Content-Type', 'text/plain');
    }
}
?>

