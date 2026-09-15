<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        return response()->json($user->toArray(),200);
    }
}