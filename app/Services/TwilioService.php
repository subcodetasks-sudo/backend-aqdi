<?php

namespace App\Services;
use Twilio\Rest\Client;

class TwilioService
{
    protected $twilio;

    public function __construct()
    {
        $this->twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
    }

    public function sendSms($to, $message)
    {
        try {
            $message = $this->twilio->messages->create($to, 
                [
                    'from' => env('TWILIO_PHONE'),  
                    'body' => $message 
                ]
            );
    
            return $message->sid;
        } catch (\Twilio\Exceptions\RestException $e) {
             return response()->json(['error' => 'Could not send SMS.', 'details' => $e->getMessage()], 500);
        } catch (\Exception $e) {
             return response()->json(['error' => 'An error occurred.', 'details' => $e->getMessage()], 500);
        }
    
    }



    
}
    