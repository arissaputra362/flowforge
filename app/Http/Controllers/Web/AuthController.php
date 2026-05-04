<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest\RegisterRequest;
use App\Http\Requests\AuthRequest\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function registerIndex()
    {
        \Log::debug("register form");
        return view('auth.register');
    }

    public function registerStore(RegisterRequest $request)
    {
        \Log::debug("register store", $request->validated());
        $result = $this->authService->register($request->validated());

        \Log::debug("Response register");
        \Log::debug(json_encode($result));
        if($result['is_success']){
            auth()->login($result['data']['user']);
            session(['api_token' => $result['data']['token']]);
            return redirect()->route('dashboard');
        }

        return redirect()->back()->withErrors([ $result['data']['message'] ?? 'Register failed. Something went wrong.'])->withInput();
    }

    public function loginIndex()
    {
        \Log::debug("login form");
        return view('auth.login');
    }

    public function loginStore(LoginRequest $request)
    {
        \Log::debug("login store", $request->validated());
        $result = $this->authService->login($request->validated());

        \Log::debug("Response login");
        \Log::debug(json_encode($result));
        if($result['is_success']){
            auth()->login($result['data']['user']);
            session(['api_token' => $result['data']['token']]);
            
            return redirect()->route('dashboard');
        }

        return redirect()->back()->withErrors([ $result['data']['message'] ?? 'Login failed. Something went wrong.'])->withInput();
    }

    public function logout(Request $request){
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
