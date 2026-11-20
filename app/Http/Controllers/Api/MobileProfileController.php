<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $request->user()->update(['name' => $data['name']]);

        return response()->json([
            'user' => $request->user()->fresh()->only(['id', 'name', 'email']),
        ]);
    }
}
