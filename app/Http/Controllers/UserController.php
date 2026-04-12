<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;

use App\Models\User;

use Illuminate\Http\Request;


class UserController extends Controller
{
    public function get(Request $request){
        $iin = trim((string) $request->iin);

        $user = User::select(
            'last_name',
            'first_name',
            'given_name',
            'data'
        )
        ->where('iin', '=', $iin)
        ->first();

        if(isset($user)){
            if(isset($user->data)){
                $user->data = json_decode(Crypt::decryptString($user->data));
            }
        }

        return response()->json([
            'user' => $user
        ], 200);
    }
}
