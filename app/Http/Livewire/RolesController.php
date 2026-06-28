<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Livewire\WithPagination;
use App\Models\User;
use DB;


class RolesController extends Component
{
    use WithPagination;

    public $roleName, $search, $selected_id, $pageTitle, $modalAction, $componentName;
    private $pagination = 10;

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Roles';
        $this->dispatchBrowserEvent('keydown', [
            'keys' => ['alt', 's'],
            'action' => 'store',
        ]);

        $this->dispatchBrowserEvent('keydown', [
            'keys' => ['alt', 's'],
            'action' => 'edit',
        ]);
    }

    public function render()
    {
        if (strlen($this->search) > 0) {
            if (auth()->user()->profile == 'Super') {
                $roles = Role::where('name', 'like', '%' . $this->search . '%')
                             ->paginate($this->pagination);
            } else {
                $roles = Role::where('name', 'like', '%' . $this->search . '%')
                             ->where('name', '<>', 'Super')
                             ->paginate($this->pagination);
            }
        } else {
            if (auth()->user()->profile == 'Super') {
                $roles = Role::orderBy('name', 'asc')
                             ->paginate($this->pagination);
            } else {
                $roles = Role::orderBy('name', 'asc')
                             ->where('name', '<>', 'Super')
                             ->paginate($this->pagination);
            }
        }

        return view('livewire.roles.roles', [
            'roles' => $roles
        ])
        ->extends('layouts.theme.app')
        ->section('content');

    }

    public function Store()
    {
        $rules = ['roleName' => 'required|min:2|unique:roles,name'];

        $messages = [
            'roleName.required' => 'El nombre del rol es requerido',
            'roleName.unique' => 'El Rol ya existe',
            'roleName.min' => 'El Rol debe tener al menos dos caracteres'
        ];
        $this->validate($rules, $messages);

        Role::create(['name' => $this->roleName]);

        $this->emit('item-added', 'Se registro el rol con exito');
        $this->resetUI();
    }

    public function Edit(Role $role)
    {
        $this->selected_id = $role->id;
        $this->roleName = $role->name;

        $this->emit('show-modal','Editar Rol');
    }

    public function Update()
    {
        $rules = ['roleName' => 'required|min:2'];

        $messages = [
            'roleName.required' => 'El nombre del rol es requerido',
            'roleName.min' => 'El Rol debe tener al menos dos caracteres'
        ];
        $this->validate($rules, $messages);

        $role = Role::find($this->selected_id);
        $role->name = $this->roleName;
        $role->save();

        $this->emit('item-updated', 'Se actualizo el rol con exito');
        $this->resetUI();
    }

    protected $listeners = [
        'deleteRow' => 'Destroy',
        'store' => 'Store',
        'edit' => 'Edit',
        'keydown.alt.s' => 'Store',
        'keydown.alt.s' => 'Update',
    ];

    public function Destroy($id)
    {
        $permissionsCount = Role::find($id)->permissions->count();

        if($permissionsCount > 0)
        {
            $this->emit('item-error', 'No se puede eliminar el rol porque tiene permisos asociados');
            return;
        }

        Role::find($id)->delete();
        $this->emit('item-deleted', 'Se elimino el rol con exito');
        $this->resetUI();
    }

    public function resetUI()
    {
        $this->roleName = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
    }


}
