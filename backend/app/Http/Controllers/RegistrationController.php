<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "name" => ["required", "string", "max:255"],
            "email" => ["required", "string", "email", "max:255",'unique:users,email'],
            "password" => ["required", "string", "min:6", "confirmed"],
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $user = new User();
        $user->uuid = Str::uuid();
        $user->name = $request->input("name");
        $user->email = $request->input("email");
        $user->email_verified_at = now();
        $user->password = Hash::make($request->input("password"));

        $user->save();

        $token = $user->createToken("token")->plainTextToken;

        $data = [
            "user"=> $user,
            "token" => $token
        ];

        return response()->json($data,201);
    }
}
