<?php

namespace App\Services\User;

use App\Models\Otp;
use App\Services\BaseServiceInterface;


class CreateOtpService implements BaseServiceInterface
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function run()
    {
        return \DB::transaction(function () {
                $new_event = Otp::create([
                    'email' => $this->data['email'],
                    'otp' =>$this->generateOtp(),
                    'is_verified' =>false,
                ]);

            return $new_event;
        });
    }



    private function generateOtp()
    {
        $digits = 6;
        $otp= str_pad(rand(0, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT);
        return $otp;
    }
  
}