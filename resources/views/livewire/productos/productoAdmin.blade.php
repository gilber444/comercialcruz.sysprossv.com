<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2"><b> {{ $componentName }} | {{ $pageTitle }} </b></h5>
            <div class="dropdown">
                @can('Productos_Create')
                    <a href="{{ route('productos') }}" class="btn btn-label-primary btn-rounded btn-sm"> <i
                            class="fa-solid fa-plus"></i> Agregar</a>
                @endcan
            </div>
        </div>
        <hr class="my-2">
        @include('common.searchbox')
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <th class="text-center">Codigo Barra</th>
                <th class="text-center">Producto</th>
                <th class="text-center">Categoria</th>
                <th class="text-center">Familia</th>
                <th class="text-center">Medida</th>
                <th class="text-center">Existencia</th>
                <th class="text-center">Actions</th>
            </thead>
            <tbody>
                @php
                    $page = request('page', $productos->currentPage());
                    $search = request('search');
                @endphp
                @foreach ($productos as $producto)
                    <tr id="producto-{{ $producto->id }}"
                        @if ($producto->activo == 0) class="table-dark"
                    @elseif(session('producto_seleccionado') == $producto->id) class="table-success" @endif>
                        <td class="text-center"><b>{{ $producto->codebar3 }}</b></td>
                        <td>{{ $producto->nombreProducto }}</td>
                        <td>{{ $producto->categoria }}</td>
                        <td>{{ $producto->familia }}</td>
                        <td>{{ $producto->medida }}</td>
                        <td>{{ $producto->Rinventario->whereNotIn('sucursal', [9, 12])->sum('existencia') }}</td>
                        <td class="text-center">
                            @can('Productos_Update')
                                <a class="btn btn-label-warning btn-sm" href="#"
                                    onclick="guardarEstadoProducto('{{ $producto->id }}', '{{ $page }}', '{{ $search }}')">
                                    <i class="fa-solid fa-edit"></i>Editar
                                </a>
                            @endcan

                            @can('Productos_Destroy')
                                <a class="btn btn-label-danger  btn-sm" href="javascript:void(0);"
                                    onclick="Confirm('{{ $producto->id }}')"><i class="fa-solid fa-trash"></i> Eliminar</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    {{ $productos->links() }}
</div>
@include('common.notis')
@if (session('producto_seleccionado'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const id = "{{ session('producto_seleccionado') }}";
            const row = document.getElementById('producto-' + id);
            if (row) {
                row.classList.add('table-success');
                row.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });
    </script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.focus();

            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    Livewire.dispatch('volverAListaConSeleccion');
                }
            });
        }

        function setupRowNavigation() {
            const rows = document.querySelectorAll('tbody tr');
            let selectedRow = null;

            rows.forEach((row, index) => {
                if (row.classList.contains('table-success')) {
                    selectedRow = index;
                }
            });

            document.addEventListener('keydown', function handler(e) {
                if (rows.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (selectedRow === null) {
                        selectedRow = 0;
                    } else if (selectedRow < rows.length - 1) {
                        selectedRow++;
                    }
                    highlightRow(rows, selectedRow);
                }

                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (selectedRow > 0) {
                        selectedRow--;
                    }
                    highlightRow(rows, selectedRow);
                }

                if (e.key === 'Enter' && selectedRow !== null) {
                    e.preventDefault();
                    const row = rows[selectedRow];
                    const id = row.id.replace('producto-', '');
                    const page = "{{ request('page', $productos->currentPage()) }}";
                    const search = "{{ request('search') }}";

                    guardarEstadoProducto(id, page, search);
                }
            });
        }

        function highlightRow(rows, index) {
            rows.forEach((row, i) => {
                row.classList.toggle('table-primary', i === index);
            });
            rows[index]?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        // Ejecuta la navegación inicial
        setupRowNavigation();

        // Re-ejecuta después de cualquier render de Livewire
        Livewire.hook('message.processed', () => {
            setupRowNavigation();
        });
    });
</script>
<script>
    function guardarEstadoProducto(id, page, search) {
        Livewire.emit('guardarEstadoSeleccion', {
            id: id,
            page: page,
            search: search
        });

        setTimeout(() => {
            const url = `{{ url('/editarProduct') }}/${id}?search=${encodeURIComponent(search)}&page=${page}`;
            window.location.href = url;
        }, 300);
    }
</script>
