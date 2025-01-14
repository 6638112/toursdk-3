<?php
namespace budisteikul\toursdk\Helpers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Cookie\CookieJar;


class BokunHelper {


    public static function env_bokunBookingChannel()
    {
   		return env("BOKUN_BOOKING_CHANNEL");
    }
    public static function env_bokunCurrency()
    {
   		return env("BOKUN_CURRENCY");
    }
    public static function env_bokunLang()
    {
   		return env("BOKUN_LANG");
    }
    public static function env_bokunEnv()
    {
   		return env("BOKUN_ENV");
    }
    public static function env_bokunAccessKey()
    {
   		return env("BOKUN_ACCESS_KEY");
    }
    public static function env_bokunSecretKey()
    {
   		return env("BOKUN_SECRET_KEY");
    }

    public static function cookie_test()
    {
    	//https://vertikaltrip.bokuntest.com/bookings/activity-json?id=7424&lang=en
    }

    public static function get_cookie()
    {
    	
		$subdomain = 'vertikaltrip';
    	$cookieJar = Cache::remember('_bokunCookie3_'. $subdomain ,7776000, function() 
		{
			$subdomain = 'vertikaltrip';
			$headers = [
                'content-type' => 'application/json',
            ];

        	$client = new \GuzzleHttp\Client(['cookies' => true, 'headers' => $headers,'http_errors' => false]);
        	$response = $client->request('POST','https://'.$subdomain.'.bokuntest.com/extranet/login',
            [   
                'timeout' => 30,
                'form_params' => [
                    'email' => env('BOKUN_EMAIL'),
                    'password' => env('BOKUN_PASSWORD'),
                    'mfaCode' => null,
                    'from' => null
                ]
            ]);

            
        	$cookieJar = $client->getConfig('cookies');
        	return $cookieJar;
		});

    	$sessionid = $cookieJar->getCookieByName('PLAY_SESSION')->getValue();
    	
    	$headers = [
                'content-type' => 'application/json',
                'X-Bokun-Currency' => 'IDR',
                'X-Bokun-Productlang' => 'en',
                'cookie' => 'PLAY_SESSION=eyJhbGciOiJIUzI1NiJ9.eyJkYXRhIjp7InVzZXJuYW1lIjoiZ3VpZGVAdmVydGlrYWx0cmlwLmNvbSIsImJva3VuLWNvbnRleHQiOiJFWFRSQU5FVCIsInZlbmRvcklkIjoiMTEwNyIsImNvdW50ZXIiOiIxNTgiLCJzYWx0IjoiJDJhJDEwJDBGWmI4TVJVcVZvMmlqcTRxRS43bmUifSwiZXhwIjoxNjc4MDkwMDc4LCJuYmYiOjE2NzAzMTQwNzgsImlhdCI6MTY3MDMxNDA3OH0.tu5zStxj1vtg_RYn1Qy75miYVacgCSbexSvUSzZvVhA'
            ];

		$client = new \GuzzleHttp\Client(['headers' => $headers]);
    	$response = $client->request('GET', 'https://'.$subdomain.'.bokuntest.com/bookings/activity-json?id=7424&lang=en');
		$contents = $response->getBody()->getContents();
		return $contents;
		
    }

    public static function bokunAPI_connect($path, $method = 'GET', $data = "")
    {
    		if(self::env_bokunEnv()=="production")
			{
				$endpoint = "https://api.bokun.io";
			}
			else
			{
				$endpoint = "https://api.bokuntest.com";
			}

			$currency = self::env_bokunCurrency();
        	$lang = self::env_bokunLang();
        	$param = '?currency='.$currency.'&lang='.$lang;
        	$date = gmdate('Y-m-d H:i:s');
        	$bokun_accesskey = self::env_bokunAccessKey();
        	$bokun_secretkey = self::env_bokunSecretKey();

			$string_signature = $date.$bokun_accesskey.$method.$path.$param;
        	$sha1_signature =  hash_hmac("sha1",$string_signature, $bokun_secretkey, true);
        	$base64_signature = base64_encode($sha1_signature);

        	$headers = [
          		'Accept' => 'application/json',
          		'X-Bokun-AccessKey' => $bokun_accesskey,
          		'X-Bokun-Date' => $date,
          		'X-Bokun-Signature' => $base64_signature,
		  		'X-Bokun-Channel' => self::env_bokunBookingChannel(),
        	];

        	$client = new \GuzzleHttp\Client(['headers' => $headers,'http_errors' => false]);

        	if($method=="POST")
			{
				$response = $client->request($method,$endpoint.$path.$param,
    			[	
    				'json' => $data
    			]);
			}
			else
			{
				$response = $client->request($method,$endpoint.$path.$param);
			}

			$contents = $response->getBody()->getContents();
			return $contents;
    }

    

    public static function bokunWidget_connect($path, $method = 'GET', $data = "")
	{

			if(self::env_bokunEnv()=="production")
			{
				$endpoint = "https://widgets.bokun.io";
			}
			else
			{
				$endpoint = "https://widgets.bokuntest.com";
			}
			
			$headers = [
		  		'x-bokun-channel' => self::env_bokunBookingChannel(),
		  		'content-type' => 'application/json',
        	];

      		$client = new \GuzzleHttp\Client(['headers' => $headers,'http_errors' => false]);

      		if($method=="POST")
			{
				$response = $client->request($method,$endpoint.$path,
    			[	
    				'json' => $data
    			]);
			}
			else
			{
				
				$response = $client->request($method,$endpoint.$path);

			}

			$contents = $response->getBody()->getContents();
			return $contents;
	}

	

	public static function set_mainContactQuestion($sessionId)
	{
		$currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();
        $bookingChannel = self::env_bokunBookingChannel();

		$data = '{"answers":[{"questionId":"firstName","values":["VERTIKAL"]},{"questionId":"lastName","values":["TRIP"]},{"questionId":"email","values":["guide@vertikaltrip.com"]},{"questionId":"phoneNumber","values":["+62 85743112112"]}]}';

		$data = json_decode($data);

		$value = self::bokunWidget_connect('/widgets/'.$bookingChannel.'/checkout/mainContactAnswers?sessionId='.$sessionId.'&lang='.$lang.'&currency='.$currency,'POST', $data);
        $value = json_decode($value);
        return $value;
	}
    public static function get_confirmBooking($sessionId)
	{
		self::set_mainContactQuestion($sessionId);
        $currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();
        $bookingChannel = self::env_bokunBookingChannel();

        $data = '{"checkoutOption":"CUSTOMER_NO_PAYMENT"}';
        $data = json_decode($data);
        $value = self::bokunWidget_connect('/widgets/'.$bookingChannel.'/checkout?sessionId='.$sessionId.'&lang='.$lang.'&currency='.$currency,'POST', $data);
        $value = json_decode($value);
        return $value->booking->confirmationCode;
	}

	public static function get_cancelProductBooking($product_confirmation_code)
    {
    	//$data = '{"note": "test","notify": false,"refund": false,"refundAmount": 0,"remainInvoiced": false}';
        //$data = json_decode($data);
        //$value = self::bokunAPI_connect('/booking.json/cancel-product-booking/'.$product_confirmation_code,'POST', $data);
        //$value = json_decode($value);
        return '';
    }

    
    public static function get_currency($currency="")
	{
        if($currency=="") $currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();
        $bookingChannel = self::env_bokunBookingChannel();

        $value = Cache::remember('_bokunCurrency_'. $currency .'_'. $lang,7200, function() use ($currency,$lang,$bookingChannel)
		{
    		return self::bokunWidget_connect('/widgets/'.$bookingChannel.'/config/conversionRate?lang='.$lang.'&currency='.$currency);
		});
		$value = json_decode($value);
		return number_format($value->displayCurrencyRateToDollar->conversionRate,6,'.',',');
	}

	public static function get_removepromocode($sessionId)
	{
		$currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();
        $bookingChannel = self::env_bokunBookingChannel();

        $value = self::bokunWidget_connect('/widgets/'. $bookingChannel .'/checkout/promoCode?lang='. $lang .'&currency='.$currency.'&sessionId='. $sessionId,'DELETE');
		$value = json_decode($value);
		return $value;
	}

	public static function get_applypromocode($sessionId,$id)
	{
		$currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();
        $bookingChannel = self::env_bokunBookingChannel();

        $id = strtolower($id);
        $value = self::bokunWidget_connect('/widgets/'. $bookingChannel .'/checkout/promoCode/'. $id .'?lang='. $lang .'&currency='.$currency.'&sessionId='. $sessionId,'POST');
		$value = json_decode($value);
		return $value;
	}

	public static function get_removeactivity($sessionId,$id)
	{
		$currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();
        $bookingChannel = self::env_bokunBookingChannel();

		$value = self::bokunWidget_connect('/widgets/'. $bookingChannel .'/shoppingCart/activity/remove/'. $id .'?lang='. $lang .'&currency='.$currency.'&sessionId='. $sessionId,'DELETE');
		$value = json_decode($value);
		
		return $value;
	}

	

	public static function get_questionshoppingcart($id)
	{
		$currency = self::env_bokunCurrency();
		$lang = self::env_bokunLang();
		$bookingChannel = self::env_bokunBookingChannel();

		$value = self::bokunWidget_connect('/widgets/'.$bookingChannel.'/checkout/cartBookingOptions?lang='.$lang.'&currency='.$currency.'&sessionId='. $id);
		$value = json_decode($value);
		return $value;
	}

	public static function get_addshoppingcart($sessionId,$data)
	{
		$currency = self::env_bokunCurrency();
		$lang = self::env_bokunLang();
		$bookingChannel = self::env_bokunBookingChannel();

		$value = self::bokunWidget_connect('/widgets/'. $bookingChannel .'/shoppingCart/activity/add?lang='. $lang .'&currency='.$currency.'&sessionId='. $sessionId,'POST',$data);
		
		$value = json_decode($value);
		return $value;
	}

	public static function get_invoice($data)
	{
		$currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();
        
		$value = json_decode(self::bokunWidget_connect('/snippets/activity/invoice-preview?currency='.$currency.'&lang='.$lang,'POST',$data));
		return $value;
	}

	public static function get_calendar_new($activityId,$year="",$month="")
	{
		
		$currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();
        $bookingChannel = self::env_bokunBookingChannel();

        if($year=="") $year = -1;
        if($month=="") $month = -1;
        
        
        $data = '{"guidedLanguages":[],"pricingCategories":[]}';
        $data = json_decode($data);

		$value = self::bokunWidget_connect('/widgets/'.$bookingChannel.'/activity/'.$activityId.'/'.$year.'/'.$month.'?lang='.$lang.'&currency='.$currency,'POST',$data);
		
		$value = json_decode($value);
		return $value->calendar;
	}

	public static function get_calendar($activityId,$year="",$month="")
	{
		$currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();

        if($year=="") $year = date('Y');
        if($month=="") $month = date('m');
        
        
        $value = Cache::remember('_bokunCalendar_'. $currency .'_'. $lang .'_'. $year .'_'. $month .'_'. $activityId ,60, function() use ($activityId,$currency,$lang,$year,$month) {
    		return self::bokunWidget_connect('/snippets/activity/'.$activityId.'/calendar/json/'.$year.'/'.$month .'?lang='.$lang.'&currency='.$currency);
		});
		

		//$value = self::bokunWidget_connect('/snippets/activity/'.$activityId.'/calendar/json/'.$year.'/'.$month .'?lang='.$lang.'&currency='.$currency);

		$value = json_decode($value);
		return $value;
	}

	public static function get_product($activityId)
	{
		$currency = self::env_bokunCurrency();
		$lang = self::env_bokunLang();
		$bookingChannel = self::env_bokunBookingChannel();
		$value = Cache::rememberForever('_bokunProductById_'. $currency .'_'. $lang .'_'.$activityId, function() use ($activityId,$currency,$lang,$bookingChannel) {
    		return self::bokunWidget_connect('/widgets/'.$bookingChannel.'/activity/'.$activityId.'?lang='.$lang.'&currency='.$currency);
		});
		$value = json_decode($value);
		return $value->activity;
	}

	public static function get_product_pickup($activityId)
	{
		$currency = self::env_bokunCurrency();
        $lang = self::env_bokunLang();
        $bookingChannel = self::env_bokunBookingChannel();

		$value = Cache::remember('_bokunProductPickup_'. $currency .'_'. $lang .'_'. $activityId,7200, function() use ($activityId,$lang,$bookingChannel) {
    		return self::bokunWidget_connect('/widgets/'.$bookingChannel.'/activity/'.$activityId.'/pickupPlaces?selectedLang='.$lang);
		});
		$value = json_decode($value);
		return $value;
	}
	
}
?>
