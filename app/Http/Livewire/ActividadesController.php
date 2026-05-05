<?php

namespace App\Http\Livewire;

use App\Models\Actividades;
use App\Models\Parametros;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ActividadesController extends Component
{

    public function mount()
    {
        $this->checkActiveActivityForToday();
    }

    public function render()
    {
        $user = Auth::user();

        if($user->profile === 'Super' || $user->profile === 'Administrador')
        {
            $cajas = Parametros::with('sucursales')->get();
        }
        else
        {
            $cajas = Parametros::with('sucursales')->where('sucursal', $user->sucursal)->get();
        }
        
        return view('livewire.actividades.actividades', ['data' => $cajas])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Validad($id)
    {
        $caja = Parametros::with('sucursales')->find($id);

        $b = Actividades::where('caja', $caja->id)
        ->where('sucursal', $caja->sucursales->id)
        ->whereDate('created_at', Carbon::today())
        ->where('status', 'Activo')
        ->first();

        $user = Auth::user();

        if($b)
        {
            if($b->user == $user->id)
            {
                session(['empresa' => $caja->sucursales->empresa]);
                session(['sucursal' => $caja->sucursal]);
                session(['caja' => $caja->id]);
                session(['actividad' => $b->id]);

                return redirect()->route('pos');
            }
            else
            {
                $this->emit('item-error', 'Esta caja ya fue aperturada por otro usuario y no se puede utilizar');

            }
        }
        else
        {
            $a = Actividades::create([
                'user' => $user->id,
                'empresa' => $caja->sucursales->empresa,
                'sucursal' => $caja->sucursal,
                'caja' => $caja->id,
                'status'=> 'Activo'
            ]);

                session(['empresa' => $caja->sucursales->empresa]);
                session(['sucursal' => $caja->sucursal]);
                session(['caja' => $caja->id]);
                session(['actividad' => $a->id]);
                return redirect()->route('pos');
        }

    }

    private function checkActiveActivityForToday()
    {
        $user = Auth::user();
        $today = now()->startOfDay();

        $activeActivity = Actividades::where('user', $user->id)
            ->where('status', 'Activo')
            ->whereDate('created_at', now()->toDateString())
            ->first();

        if ($activeActivity) {
            $caja = Parametros::with('sucursales')->find($activeActivity->caja);
            $this->setUserSession($caja, $activeActivity->id);
            return redirect()->route('pos');
        }
    }

    private function setUserSession($caja, $activityId)
    {
        session([
            'empresa' => $caja->sucursales->empresa,
            'sucursal' => $caja->sucursal,
            'caja' => $caja->id,
            'actividad' => $activityId
        ]);
    }
}
