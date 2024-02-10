<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use App\Services\User\CreateOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Notifications\ResetPasswordNotification;

class OtpController extends Controller
{
    
    public function generatePinForgetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required',
        ]);
        
      
        try {

            $user = User::where('email', "ayeni.ayobami21@gmail.com")->first();
            $new_otp = new CreateOtpService($validated);
            $new_otp = $new_otp->run();

            $user_mail_content_array = array(
                "sender" => "PR",
                "code" => $new_otp->otp,
                "link" => "",

              );

            $user->notify(new ResetPasswordNotification($user_mail_content_array));
           
            return response()->json(['status' => true, 'data' => $new_otp,  'message' => 'Mail sent successfully'], 201);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['status' => false,  'message' => $exception->getMessage()], 500);
        }
    }

    public function VerifyOTP(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required',
            'code' => 'required',
        ]);
        
        try {
            $model = Otp::where('email', '=', $validated['email']) 
            ->where('otp', '=', $validated['code'])
            ->where('is_verified', '=', 0)
            ->firstOrFail();
            if($model->created_at->addMinutes(30)->isPast()) {
                return response()->json(['status' => false,  'message' => "This OTP has expired"], 500);
            }
            $model->is_verified = 1;
            $model->save();

            return response()->json(['status' => true, 'data' => $model, 'message' => 'Verified successfully'], 200);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['status' => false,  'message' => "This OTP has expired or does not exist"], 500);
        }
    }
}
