<?php

namespace App\Http\Livewire;

use App\Models\AperturaInventario;
use App\Models\HojaInventario;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;



class NuevaHojaController extends Component
{
    use WithPagination;

    public $pageTitle, $componentName;
    private $pagination = 20;

    public $fecha, $hora, $correlativo, $sucursal, $sucursalNombre;

    public $username, $password, $aperturaActiva;

    public function mount()
    {
        $this->pageTitle = 'Nueva';
        $this->componentName = 'Hoja de inventario';

        $this->fecha = now()->toDateString();
        $this->hora  = now()->format('H:i:s');

        $user = Auth::user();

        $this->aperturaActiva = AperturaInventario::where('empresa', $user->empresa)
            //->where('sucursal', $user->sucursal)
            ->where('estado', 'Abierto')
            ->first();
        $sucursal = Sucursales::where('id', $this->aperturaActiva->sucursal)->first();
        $this->sucursal = $this->aperturaActiva->sucursal;
        $this->sucursalNombre = $sucursal->nombre;
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        return view('livewire.hoja_inventarios.head-hoja')
            ->extends('layouts.theme.app')
            ->section('content');
    }

    // =====================================================
    // VALIDACIÓN (incluye sucursal y fecha)
    // =====================================================
    protected function rules()
    {
        return [
            'sucursal' => 'required|not_in:0',
            'fecha'    => 'required|date',
            'username' => 'required|string',
            'password' => 'required|string',
            'correlativo' => 'required|string',
        ];
    }

    protected function messages()
    {
        return [
            'sucursal.required' => 'La sucursal es requerida',
            'sucursal.not_in'   => 'Elegí una sucursal válida',
            'fecha.required'    => 'La fecha es requerida',
            'fecha.date'        => 'Fecha inválida',
            'username.required' => 'El usuario es requerido',
            'password.required' => 'La contraseña es requerida',
            'correlativo.required' => 'El correlativo es requerido',
        ];
    }

    // =====================================================
    // VALIDAR CREDENCIALES Y CREAR/CONTINUAR HOJA
    // =====================================================
    public function Validar()
    {
        $this->validate($this->rules(), $this->messages());

        // Usuario autorizado (tu lógica)
        $validador = User::where(function ($query) {
            $query->where('user', $this->username)
                ->orWhere('email', $this->username);
        })
            ->whereIn('profile', ['Super', 'Administrador', 'Gerente', 'BODEGA', 'Contabilidad', 'Contador', 'Cajeros'])
            ->first();

        if (!$validador || !Hash::check($this->password, $validador->password)) {
            $this->emit('item-error', 'Credenciales inválidas. Verifique su usuario y contraseña');
            return;
        }

        // Si credenciales OK -> crear o continuar
        $this->StoreOrContinue($validador);
    }

    private function StoreOrContinue(User $validador)
    {
        $usuario = Auth::user();

        // Buscar hoja activa/pendiente para esa sucursal y empresa
        $hojaActiva = HojaInventario::where('empresa', $usuario->empresa)
            ->where('sucursal', $this->sucursal)
            ->where('estado', 'Activa') // compatibilidad
            ->latest('id')
            ->first();

        /*if ($hojaActiva) {
            // Continuar hoja existente
            $this->reset(['username', 'password']);
            $this->dispatchBrowserEvent('close-validar-modal');

            $this->emit('item-ok', 'Ya existe una hoja activa. Continuando...');
            return redirect()->route('editar-hoja', ['hojaId' => $hojaActiva->id]);
        }*/

        // Crear nueva hoja
        $hora = now()->format('H:i:s');

        $hoja = HojaInventario::create([
            // compatibilidad si en listados aún usás fecha/hora
            'apertura_id'  => $this->aperturaActiva->id,
            'fecha'        => $this->fecha,
            'hora'         => $hora,

            // nuevos campos para inventario en vivo
            'fecha_inicio' => $this->fecha,
            'hora_inicio'  => $hora,
            'fecha_fin'    => null,
            'hora_fin'     => null,
            'correlativo'  => $this->correlativo,

            // IDs de usuarios
            'responsable'  => $validador->id,  // el que valida
            'user'         => $usuario->id,    // el que está operando

            'empresa'      => $usuario->empresa,
            'sucursal'     => $this->sucursal,

            // nuevo estado recomendado
            'estado'       => 'Activa', // si querés seguir con Pendiente ponelo aquí
        ]);

        $this->reset(['username', 'password']);
        $this->dispatchBrowserEvent('close-validar-modal');

        $this->emit('item-added', 'Hoja creada con éxito');
        return redirect()->route('editar-hoja', ['hojaId' => $hoja->id]);
    }

    public function preValidar()
    {
        $this->validate([
            'sucursal' => 'required|not_in:0',
            'fecha'    => 'required|date',
            'correlativo' => 'required|string',
        ], [
            'sucursal.not_in' => 'Elegí una sucursal válida',
            'fecha.required'  => 'La fecha es requerida',
            'correlativo.required' => 'El correlativo es requerido',
        ]);
    }
}
