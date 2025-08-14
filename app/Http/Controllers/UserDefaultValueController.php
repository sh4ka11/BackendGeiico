<?php

namespace App\Http\Controllers;

use App\Models\UserDefaultValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserDefaultValueController extends Controller
{
    public function index()
    {
        $userDefaults = UserDefaultValue::where('user_id', Auth::id())
            ->get();
        
        return response()->json($userDefaults);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'field_name' => 'required|string|max:255',
            'field_value' => 'required|string|max:255'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $default = UserDefaultValue::updateOrCreate(
            ['user_id' => Auth::id(), 'field_name' => $request->field_name],
            ['field_value' => $request->field_value]
        );
        
        return response()->json($default);
    }
}
