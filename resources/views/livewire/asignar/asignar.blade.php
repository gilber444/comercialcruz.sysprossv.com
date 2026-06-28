<div class="card">
    <div class="card-header">
        <h5 class="card-title m-0 me-2"><b> {{ $componentName }} </b></h5>
        <hr class="my-2">
        <div class="d-flex align-items-center justify-content-between">
            <div class="form-group mr-5 col-6">
                <select wire:model='role' class="form-control">
                    <option value="Elegir" selected >Seleccione el Rol</option>
                    @foreach ($roles as $role )
                    <option value="{{$role->id}}">{{$role->name}}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click.prevent='SyncAll()' type="button" class="btn btn-primary">Sincronizar Todos</button>

            <button onclick='Revocar()' type="button" class="btn btn-primary">Revocar Todos</button>
        </div>
        <div class="form-group col-6 mt-3">
            <input type="text" wire:model="search" class="form-control" placeholder="Buscar Permiso...">
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover table-sm">
                <thead>
                    <th class="table-th text-dark text-center">ID</th>
                        <th class="table-th text-center">PERMISO</th>
                        <th class="table-th text-center">ROLES CON EL PERMISO</th>
                </thead>
                <tbody>
                    @foreach ($permisos as $permiso)
                    <tr>
                        <td class="text-center"><h6>{{$permiso->id}}</h6></td>
                        <td class="text-center">
                            <div class="n-chk">
                                <label class="new-control new-checkbox checkbox-primary">
                                  <input type="checkbox" wire:change="SyncPermiso($('#p' + {{ $permiso->id}}).is(':checked'), '{{ $permiso->name}}')" class="new-control-input" {{ $permiso->checked == 1 ? 'checked' : '' }} id="p{{ $permiso->id}}" value="{{ $permiso->id}}">
                                  <span class="new-control-indicator"></span><h6>{{ $permiso->name}}</h6>
                                </label>
                            </div>
                        </td>
                        <td class="text-center">
                            <h6>{{ \App\Models\User::permission($permiso->name)->count() }}</h6>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{$permisos->links()}}
        </div>
    </div>
</div>
@include('common.notis')
