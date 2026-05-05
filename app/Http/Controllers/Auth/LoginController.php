<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresas;
use App\Models\Ubicaciones;
use App\Providers\RouteServiceProvider;
use App\Services\DailyTokenService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username()
    {
        return 'user'; // Especifica que el campo 'user' se utilizará como el nombre de usuario para el inicio de sesión
    }

    public function login(Request $request)
    {
        $request->validate([
            'user' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('user', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->status === 'ACTIVE') {
                $ubicaciones = Ubicaciones::where('usuario', $user->id)->where('estado', 'Activo')->get();

                if ($ubicaciones->count() > 0) {
                    // Obtener la primera ubicación activa
                    $primeraUbicacion = $ubicaciones->first();

                    // Asignar los valores a las variables de sesión
                    session(['empresa' => $primeraUbicacion->empresa]);
                    session(['sucursal' => $primeraUbicacion->sucursal]);
                    session(['caja' => $primeraUbicacion->caja]);
                } else {
                    // Manejo en caso de que no haya ubicaciones activas
                    session()->forget(['empresa', 'sucursal', 'caja']);
                }

                $empresa = Empresas::find($user->empresa);

                if ($empresa->certificado) {
                    app(DailyTokenService::class)->ensureForToday();
                }


                if ($user->hasRole('Cajeros')) {
                    return redirect()->route('actividad');
                }
                return redirect()->intended($this->redirectPath());

            } else {
                Auth::logout();
                return back()->withErrors([
                    'user' => 'Tu cuenta no está activa.',
                ])->withInput($request->only('user'));
            }
        }

        return back()->withErrors([
            'user' => 'Credenciales inválidas.',
        ])->withInput($request->only('user'));
    }
}
