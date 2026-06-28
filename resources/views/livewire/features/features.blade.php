<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b>{{ $componentName }} | {{ $pageTitle }}</b></h5>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Version</th>
                <th>Descripcion</th>
                <th class="text-center">Activo</th>
                <th class="text-center">Produccion</th>
                <th class="text-center">Fecha</th>
            </thead>
            <tbody>
                @foreach ($features as $feature)
                <tr>
                    <td class="text-center"><span class="badge bg-info">{{ $feature->version }}</span></td>
                    <td>{{ $feature->descripcion }}</td>
                    <td class="text-center">
                        <a href="javascript:void(0);" wire:click="toggleActivo({{ $feature->id }})" class="btn btn-sm {{ $feature->activo ? 'btn-success' : 'btn-secondary' }}">
                            {{ $feature->activo ? 'SI' : 'NO' }}
                        </a>
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0);" wire:click="toggleProduccion({{ $feature->id }})" class="btn btn-sm {{ $feature->produccion ? 'btn-primary' : 'btn-warning' }}">
                            {{ $feature->produccion ? 'LIBERADO' : 'PRUEBAS' }}
                        </a>
                    </td>
                    <td class="text-center">{{ $feature->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $features->links() }}
</div>
@include('common.notis')
