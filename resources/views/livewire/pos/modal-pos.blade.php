<div wire:ignore.self class="modal fade" id="modalSearchPosProduct" tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <div class="input-group input-group-merge">

                    <span class="input-group-text">

                        <i class="fas fa-search"></i>

                    </span>

                    <input type="text" wire:model='search' id="searchInput"

                        placeholder="Buscar por nombre, codigo de barras o categorias" class="form-control">

                </div>

            </div>

            <div class="modal-body">

                <div class="row p-2">

                    <div class="table-responsive">

                        <table class="table table-hover table-sm">

                            <thead>

                                <th></th>

                                <th class="text-center">CODIGO DE BARRA</th>

                                <th class="text-center">DESCRIPCION</th>

                                <th class="text-center">MEDIDA</th>

                                <th class="text-center">PRECIO</th>

                                <th class="text-center">EXISTENCIA</th>

                            </thead>

                            <tbody>

                                @forelse ($products as $product)

                                    <tr tabindex="0" style="font-size: 90%">

                                        <td>{{ $product->codebar3 }}</td>

                                        <td class="text-center">

                                            <a class="text-secondary" href="javascript:void(0);"

                                                data-codebar="{{ $product->id }}">{{ $product->codebar }}</a>

                                        </td>

                                        <td>

                                            <a class="text-secondary" href="javascript:void(0);"

                                                data-codebar="{{ $product->id }}"><b>{{ $product->nombreProducto }}</b></a>

                                        </td>

                                        <td class="text-center">

                                            {{ $product->presentacion }}

                                        </td>

                                        <td class="text-center">

                                            $ {{ number_format($product->pvventa, 2) }}

                                        </td>

                                        <td class="text-center">

                                            {{ $product->existencia}}

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="8" class="text-center">SIN RESULTADOS</td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-label-primary" data-bs-dismiss="modal">Cerrar Ventana</button>

            </div>

        </div>

    </div>

</div>

<style>

    tr:focus {

        outline: 2px solid #969cff;

        background-color: #e7e7ff;

    }

</style>

