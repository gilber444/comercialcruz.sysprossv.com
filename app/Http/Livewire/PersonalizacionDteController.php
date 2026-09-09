<?php

namespace App\Http\Livewire;

use App\Models\Empresas;
use App\Services\DtePaletteService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PersonalizacionDteController extends Component
{
    public $empresaId;
    public $empresas = [];
    public $colorPrimario;
    public $colorEncabezado;
    public $colorTexto;
    public $colorTextoSuave;
    public $colorBorde;
    public $colorExito;
    public $colorPeligro;
    public $colorFondo;

    public function mount(DtePaletteService $paletas): void
    {
        $user = Auth::user();
        $query = Empresas::query()->orderBy('empresa');

        if ($user->profile !== 'Super') {
            $query->whereKey($user->empresa);
        }

        $this->empresas = $query->get(['id', 'empresa', 'razon', 'image']);
        $this->empresaId = (int) ($user->profile === 'Super'
            ? optional($this->empresas->first())->id
            : $user->empresa);

        $this->cargarPaleta($paletas);
    }

    public function updatedEmpresaId(): void
    {
        $this->autorizarEmpresa();
        $this->cargarPaleta(app(DtePaletteService::class));
    }

    public function guardar(DtePaletteService $paletas): void
    {
        $this->autorizarEmpresa();
        $this->validate($this->reglas(), $this->mensajes());
        $paletas->guardar((int) $this->empresaId, $this->paletaFormulario());
        $this->emit('item-updated', 'Personalizacion de documentos guardada con exito');
    }

    public function restablecer(DtePaletteService $paletas): void
    {
        $this->autorizarEmpresa();
        $this->asignarPaleta($paletas->restablecer((int) $this->empresaId));
        $this->emit('item-updated', 'Se restablecieron los colores predeterminados');
    }

    public function render()
    {
        $empresaSeleccionada = collect($this->empresas)->firstWhere('id', (int) $this->empresaId);

        return view('livewire.empresa.personalizacion-dte', compact('empresaSeleccionada'))
            ->extends('layouts.theme.app')
            ->section('content');
    }

    private function cargarPaleta(DtePaletteService $paletas): void
    {
        $this->asignarPaleta($paletas->obtener($this->empresaId ? (int) $this->empresaId : null));
    }

    private function asignarPaleta(array $paleta): void
    {
        $this->colorPrimario = $paleta['primario'];
        $this->colorEncabezado = $paleta['encabezado'];
        $this->colorTexto = $paleta['texto'];
        $this->colorTextoSuave = $paleta['texto_suave'];
        $this->colorBorde = $paleta['borde'];
        $this->colorExito = $paleta['exito'];
        $this->colorPeligro = $paleta['peligro'];
        $this->colorFondo = $paleta['fondo'];
    }

    private function paletaFormulario(): array
    {
        return [
            'primario' => $this->colorPrimario,
            'encabezado' => $this->colorEncabezado,
            'texto' => $this->colorTexto,
            'texto_suave' => $this->colorTextoSuave,
            'borde' => $this->colorBorde,
            'exito' => $this->colorExito,
            'peligro' => $this->colorPeligro,
            'fondo' => $this->colorFondo,
        ];
    }

    private function autorizarEmpresa(): void
    {
        $user = Auth::user();
        $permitida = Empresas::whereKey((int) $this->empresaId)
            ->when($user->profile !== 'Super', fn ($query) => $query->whereKey($user->empresa))
            ->exists();

        abort_unless($permitida, 403);
    }

    private function reglas(): array
    {
        $regla = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'colorPrimario' => $regla,
            'colorEncabezado' => $regla,
            'colorTexto' => $regla,
            'colorTextoSuave' => $regla,
            'colorBorde' => $regla,
            'colorExito' => $regla,
            'colorPeligro' => $regla,
            'colorFondo' => $regla,
        ];
    }

    private function mensajes(): array
    {
        return [
            '*.required' => 'Seleccione todos los colores.',
            '*.regex' => 'El color debe tener formato hexadecimal, por ejemplo #33475B.',
        ];
    }
}
