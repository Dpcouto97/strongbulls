<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\Request;

class CustomLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        return redirect()->intended('/home');
    }
}
