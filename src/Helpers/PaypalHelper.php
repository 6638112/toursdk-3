<?php
namespace budisteikul\toursdk\Helpers;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Payments\AuthorizationsCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

class PaypalHelper {
	
	public static function client()
  {
    return new PayPalHttpClient(self::environment());
  }
	
	public static function paypalApiEndpoint()
  {
    if(self::env_paypalEnv()=="production")
    {
      $endpoint = "https://api.paypal.com";
    }
    else
    {
      $endpoint = "https://api.sandbox.paypal.com";
    }
    return $endpoint;
  }

  public static function env_paypalEnv()
  {
        return env("PAYPAL_ENV");
  }

  public static function env_paypalClientId()
  {
  		return env("PAYPAL_CLIENT_ID");
  }

  public static function env_paypalClientSecret()
  {
  		return env("PAYPAL_CLIENT_SECRET");
  }

  public static function environment()
  {
        $clientId = self::env_paypalClientId();
        $clientSecret = self::env_paypalClientSecret();

		    if(self::env_paypalEnv()=="production")
			  {
        		return new ProductionEnvironment($clientId, $clientSecret);
			  }
			  else
			  {
				    return new SandboxEnvironment($clientId, $clientSecret);
  			}
  }
	
  public static function getOrder($id)
  {
		  $client = self::client();
		  $response = $client->execute(new OrdersGetRequest($id));
		  return $response->result->purchase_units[0]->amount->value;
  }
	
	public static function createPayment($data)
	{
      	$value = number_format((float)$data->transaction->amount, 2, '.', '');
      	$name = 'Invoice No : '. $data->transaction->confirmation_code;
     	$currency = $data->transaction->currency;
    	
      	$request = new OrdersCreateRequest();
		$request->prefer('return=representation');
    	$request->body = self::buildRequestBodyCreateOrder($value,$name,$currency);
    	$client = self::client();
    	$data_json = $client->execute($request);

      	$status_json = new \stdClass();
      	$response_json = new \stdClass();
      
      	$status_json->id = '1';
      	$status_json->message = 'success';
        
      	$response_json->status = $status_json;
      	$response_json->data = $data_json;

		return $response_json;
	}

	public static function buildRequestBodyCreateOrder($value,$name,$currency)
    {
  		if(env('PAYPAL_INTENT')=="CAPTURE")
  		{
  			$intent = "CAPTURE";
  		}
  		else
  		{
  			$intent = "AUTHORIZE";
  		}

        return array(
            'intent' => $intent,
            'application_context' =>
                array(
                    'shipping_preference' => 'NO_SHIPPING'
                ),
            'purchase_units' =>
                array(
                    0 =>
                        array(
						'description' => $name,
                            'amount' =>
                                array(
                                    'currency_code' => $currency,
                                    'value' => $value
                                )
                        )
                )
        );
    }
	
	public static function captureAuth($id)
    {
        $request = new AuthorizationsCaptureRequest($id);
    	$request->body = self::buildRequestBodyCapture();
    	$client = self::client();
    	$response = $client->execute($request);
	  	return $response->result->status;
	}
	
	public static function buildRequestBodyCapture()
  	{
    		return "{}";
  	}
	
	public static function voidPaypal($id)
    {
			$PAYPAL_CLIENT = self::env_paypalClientId();
			$PAYPAL_SECRET = self::env_paypalClientSecret();

			if(self::env_paypalEnv()=="production")
			{
				$PAYPAL_OAUTH_API         = self::paypalApiEndpoint() .'/v1/oauth2/token/';
				$PAYPAL_AUTHORIZATION_API = self::paypalApiEndpoint() .'/v2/payments/authorizations/';
			}
			else
			{
				$PAYPAL_OAUTH_API         = self::paypalApiEndpoint() .'/v1/oauth2/token/';
				$PAYPAL_AUTHORIZATION_API = self::paypalApiEndpoint() .'/v2/payments/authorizations/';
			}
			
			$basicAuth = base64_encode($PAYPAL_CLIENT.':'.$PAYPAL_SECRET);
    		$headers = [
          		'Accept' => 'application/json',
          		'Authorization' => 'Basic '.$basicAuth,
			];
			$client = new \GuzzleHttp\Client(['headers' => $headers]);
    		$response = $client->request('POST', $PAYPAL_OAUTH_API,[
				'form_params' => [
        			'grant_type' => 'client_credentials',
    			]
			]);
			
			$data = json_decode($response->getBody(), true);
			$access_token = $data['access_token'];
			
			$headers = [
          		'Accept' => 'application/json',
          		'Authorization' => 'Bearer '.$access_token,
        		];
			$client = new \GuzzleHttp\Client(['headers' => $headers]);
    		$response = $client->request('POST', $PAYPAL_AUTHORIZATION_API . $id.'/void');
			
    }
}
?>