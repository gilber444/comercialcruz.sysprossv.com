<?php

namespace App\Http\Livewire;

use App\Models\Empresas;
use App\Models\Sale;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersController extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $name,
        $phone,
        $email,
        $status,
        $image,
        $password,
        $fileLoeaded,
        $search,
        $selected_id,
        $pageTitle,
        $componentName,
        $profile,
        $filename,
        $usuario,
        $showPassword = false,
        $empresa,
        $empresas,
        $sucursal,
        $sucursales;
    private $pagination = 10;

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Usuarios';

        $user = Auth::user();

        if ($user->profile == 'Super') {
            // Si el usuario es super, obtiene todas las empresas
            $this->empresas = Empresas::all();
        } else {
            // Si no es super, solo obtiene su empresa
            $this->empresas = Empresas::where('id', $user->empresa)->get();
            $this->empresa = $user->empresa;
        }

        $this->sucursales = Sucursales::all();
    }

    public function render()
    {
        if (strLen($this->search) > 0) {
            if (auth()->user()->profile == 'Super') {
                $data = User::where('name', 'like', '%' . $this->search . '%')
                    ->select('*')
                    ->orderBy('name', 'asc')
                    ->paginate($this->pagination);
            } else {
                $data = User::where('name', 'like', '%' . $this->search . '%')
                    ->where('profile', '<>', 'Super')
                    ->select('*')
                    ->orderBy('name', 'asc')
                    ->paginate($this->pagination);
            }
        } elseif (auth()->user()->profile == 'Super') {
            $data = User::select('*')
                ->orderBy('name', 'asc')
                ->paginate($this->pagination);
        } else {
            $data = User::where('profile', '<>', 'Super')
                ->select('*')
                ->orderBy('name', 'asc')
                ->paginate($this->pagination);
        }
        $roles = Role::orderBy('name', 'asc')->get();
        if (auth()->user()->profile != 'Super') {
            $roles = $roles->filter(function ($role) {
                return $role->name != 'Super';
            });
        }
        return view('livewire.users.users', [
            'data' => $data,
            'roles' => $roles,
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function resetUI()
    {
        $this->name = '';
        $this->usuario = '';
        $this->email = '';
        $this->password = '';
        $this->phone = '';
        $this->image = '';
        $this->status = 'Elegir';
        $this->search = '';
        $this->profile = 'Elegir';
        $this->selected_id = 0;
        $this->empresa = 'Elegir';
        $this->sucursal = 'Elegir';
        $this->resetValidation();
        $this->resetPage();
    }

    public function Edit(User $user)
    {
        $this->selected_id = $user->id;
        $this->name = $user->name;
        $this->phone = $user->phone;
        $this->profile = $user->profile;
        $this->status = $user->status;
        $this->email = $user->email;
        $this->password = $user->password2;
        $this->usuario = $user->user;
        $this->empresa = $user->empresa;
        $this->sucursal = $user->sucursal;

        $this->emit('show-modal', 'open');
    }
    protected $listeners = [
        'deleteRow' => 'destroy',
        'resetUI' => 'resetUI',
    ];

    public function Store()
    {
        $rules = [
            'name' => 'required|min:3',
            'email' => 'required|unique:users|email',
            'status' => 'required|not_in:Elegir',
            'profile' => 'required|not_in:Elegir',
            'password' => 'required|min:3',
            'empresa' => 'required|not_in:Elegir',
            'sucursal' => 'required|not_in:Elegir',
        ];

        $messages = [
            'name.required' => 'Ingrese el Nombre',
            'name.min' => 'el nombre de usuario debe tener al menos 3 caracteres',
            'email.required' => 'Ingrese el Email',
            'email.email' => 'Ingresa un email Valido',
            'email.unique' => 'El email ya esta registrado',
            'status.required' => 'Selecciona el estatus del usuario',
            'status.not_in' => 'Selecciona un estatus diferente de Elegir',
            'profile.required' => 'Selecciona el perfil/role del usuario',
            'profile.not_in' => 'Selecciona un perfil/role diferente de Elegir',
            'password.required' => 'Ingrese el Password',
            'password.min' => 'El password debe tener al menos 3 caracteres',
            'empresa.required' => 'Selecciona la empresa',
            'empresa.not_in' => 'Selecciona una empresa diferente de Elegir',
            'sucursal.required' => 'Selecciona la sucursal',
            'sucursal.not_in' => 'Selecciona una sucursal diferente de Elegir',
        ];

        $this->validate($rules, $messages);

        $user = User::create([
            'name' => $this->name,
            'user' => $this->usuario,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile' => $this->profile,
            'status' => $this->status,
            'password' => bcrypt($this->password),
            'password2' => $this->password,
            'empresa' => $this->empresa,
            'sucursal' => $this->sucursal,
        ]);

        $user->syncRoles($this->profile);

        if ($this->image) {
            $customFileName = uniqid() . '_.' . $this->image->extension();
            $this->image->storeAs('public/users', $customFileName);
            $user->image = $customFileName;
            $user->save();
        }

        $this->resetUI();
        $this->emit('item-added', 'Usuario Registrado');
    }

    public function Update(User $user)
    {
        $rules = [
            'email' => 'required|email',
            'name' => 'required|min:3',
            'status' => 'required|not_in:Elegir',
            'profile' => 'required|not_in:Elegir',
            'password' => 'required|min:3',
            'empresa' => 'required|not_in:Elegir',
            'sucursal' => 'required|not_in:Elegir',
        ];

        $messages = [
            'name.required' => 'Ingrese el Nombre',
            'name.min' => 'el nombre de usuario debe tener al menos 3 caracteres',
            'email.required' => 'Ingrese el Email',
            'email.email' => 'Ingresa un email Valido',
            'status.required' => 'Selecciona el estatus del usuario',
            'status.not_in' => 'Selecciona un estatus diferente de Elegir',
            'profile.required' => 'Selecciona el perfil/role del usuario',
            'profile.not_in' => 'Selecciona un perfil/role diferente de Elegir',
            'password.required' => 'Ingrese el Password',
            'password.min' => 'El password debe tener al menos 3 caracteres',
            'empresa.required' => 'Selecciona la empresa',
            'empresa.not_in' => 'Selecciona una empresa diferente de Elegir',
            'sucursal.required' => 'Selecciona la sucursal',
            'sucursal.not_in' => 'Selecciona una sucursal diferente de Elegir',
        ];

        $this->validate($rules, $messages);

        $user = User::find($this->selected_id);
        $user->update([
            'name' => $this->name,
            'user' => $this->usuario,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'profile' => $this->profile,
            'password' => bcrypt($this->password),
            'password2' => $this->password,
            'empresa' => $this->empresa,
            'sucursal' => $this->sucursal,
        ]);

        $user->syncRoles($this->profile);

        if ($this->image) {
            $customFileName = uniqid() . '_.' . $this->image->extension();
            $this->image->storeAS('public/users', $customFileName);
            $imagetemp = $user->image;
            $user->image = $customFileName;
            $user->save();

            if ($imagetemp != null) {
                if (file_exists('storage/users/' . $imagetemp)) {
                    unlink('storage/users/' . $imagetemp);
                }
            }
        }
        $this->resetUI();
        $this->emit('item-updated', 'Usuario Actualizado');
    }

    public function destroy(User $user)
    {
        if ($user) {
            $user->delete();
            $this->resetUI();
            $this->emit('item-deleted', 'Usuario Eliminado');
            // }
        }
    }
    ///simulador del symlik o enlace simbolico para el storage/////
    public function renderImagen($filename)
    {
        $path = 'public/users/' . $filename;
        if (!Storage::exists($path)) {
            abort(404);
        }
        return response()->file(storage_path("app/{$path}"));
    }

    public function togglePasswordVisibility()
    {
        $this->showPassword = !$this->showPassword;
    }
}
