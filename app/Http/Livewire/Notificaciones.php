<?php

namespace App\Http\Livewire;

use App\Models\Notificacion;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Notificaciones extends Component
{
    public $notificaciones;
    public $contador = 0;

    protected $listeners = ['nuevaNotificacion' => 'cargarNotificaciones'];

    public function mount() {
        $this->cargarNotificaciones();
    }

    public function cargarNotificaciones() {
        $this->notificaciones = Notificacion::where('user', Auth::id())
            ->where('leido', false)
            ->orderBy('created_at', 'desc')
            ->get();

        // Contador de notificaciones no leídas
        $this->contador = $this->notificaciones->where('leido', false)->count();
    }

    public function marcarComoLeido($id) {
        $notificacion = Notificacion::find($id);
        if ($notificacion) {
            $notificacion->update(['leido' => true]);
            $this->cargarNotificaciones();
        }
    }

    public function render()
    {
        return view('livewire.notificaciones');
    }
}
