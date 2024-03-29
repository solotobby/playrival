<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;

use Mailjet\LaravelMailjet\Facades\Mailjet;
use Mailjet\Resources;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|unique:users|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|between:4,32|confirmed',
        ]);

        try {

            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => "21781662197",
                'password' => $validated['password'],
            ]);
            $user->assignRole('user');
            $token = $user->createToken('payrival')->accessToken;
            $registered_user = User::with(['roles'])->findorfail($user->id);
            $data['user'] = $registered_user;
            $data['token'] = $token;



            return response()->json(['status' => true, 'data' => $data,  'message' => 'Registration successfully'], 201);
        } catch (Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $user = User::with(['roles'])->where('email', $validated['email'])->first();
            if ($user) {

                if (Hash::check(trim($validated['password']), $user->password)) {
                    $user['token'] = $user->createToken('playrival')->accessToken;
                    return response()->json(['data' => $user], 200);
                } else {
                    return response(['message' => 'Email or Password Incorrect'], 401);
                }
            } else {
                return response(['message' => 'Email or Password Incorrect'], 401);
            }
        } catch (Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:teams|max:255',
            // 'phone' => 'required|string|size:13|unique:users',
        ]);

        try {
            $user = Auth::user();
            $team = Team::where('user_id', $user->id)->first();
            $team->name = $validated['name'];
            $team->save();

            return response()->json(['status' => true, 'data' => $team,  'message' => 'Bio updated successfully'], 201);
        } catch (Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'old_password' => 'required',
            'password' => 'required|between:4,32|confirmed',
        ]);

        try {
            $user = Auth::user();
            $info = User::where('id', $user->id)->first();
            if ($info) {

                if (Hash::check(trim($validated['old_password']), $info->password)) {
                    $info->password = $validated['password'];
                    $info->save();
                    return response()->json(['status' => true, 'data' => $info,  'message' => 'Password Changed'], 201);
                } else {
                    return response(['message' => 'Email or Password Incorrect'], 401);
                }
            } else {
                return response(['message' => 'Email or Password Incorrect'], 401);
            }
        } catch (Exception $exception) {
            return response()->json(['status' => false,  'error' => $exception->getMessage(), 'message' => 'Error processing request'], 500);
        }
    }


    public function logout(Request $request)
    {
        $user = Auth::user();
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }

    public function resetPassword(Request $request){
     
        $this->validate($request, [
            'password' => 'required|between:4,32|confirmed',
            'email' => 'required|exists:users,email'
        ]);

        try {
            $authUser = User::where('email', $request->email)->firstOrFail();
            $authUser->password = $request->password;
            $authUser->save();
          
            return response()->json(['status' => true, 'message' => 'Password Successfully Reset', 'data' => $authUser ], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => false,  'message' => $exception->getMessage()], 500);
        }

    }

}
