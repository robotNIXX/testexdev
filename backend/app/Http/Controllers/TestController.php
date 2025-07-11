<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test()
    {
        return response()->json([
            'message' => 'Laravel API is working!',
            'environment' => app()->environment(),
            'timestamp' => now()->toISOString()
        ]);
    }

    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString()
        ]);
    }
} 