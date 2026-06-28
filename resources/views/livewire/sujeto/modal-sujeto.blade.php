<div wire:ignore.self class="modal fade" id="modalSearchProduct" tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <div class="input-group input-group-merge">

                    <span class="input-group-text">

                        <i class="fas fa-search"></i>

                    </span>

                    <input type="text" wire:model='search' id="modal-search-input" placeholder="Buscar por nombre, codigo de barras o categorias" class="form-control" onfocus="">

                </div>

            </div>

            <div class="modal-body">

                <div class="row p-2">

                    <div class="table-responsive text-nowrap">

                        <table class="table table-hover table-sm">

                            <thead>

                                <th class="text-center">CODIGO BARRA</th>

                                <th class="text-center">DESCRIPCION</th>

                                <th class="text-center">MEDIDA</th>

                                <th class="text-center">CANTIDAD</th>

                                <th class="text-center">COSTO</th>

                                <th class="text-center">

                                </th>

                            </thead>

                            <tbody>

                                @forelse ( $products as $product )

                                <tr>

                                    <td class="text-center"><h6>{{ $product->codebar }}</h6></td>

                                    <td>

                                        <div class="text-left">

                                            <h6><b>{{ $product->Rproductos->nombreProducto }}</b></h6>

                                        </div>

                                    </td>                                 

                                    <td class="text-center">

                                        <h6>{{ $product->presentacion }}</h6>

                                    </td>

                                    <td class="text-center">

                                        <h6>{{ $product->cantidad }}</h6>

                                    </td>

                                    <td class="text-end">

                                        <h6> $ {{ number_format($product->costosiva, 2) }}</h6>

                                    </td>

                                    <td class="tex

                                    t-center">

                                        <button wire:click.prevent="Add({{$product->id}})" class="btn btn-primary"><i class="fas fa-cart-arrow-down mr-1"></i> Agregar</button>

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

<script>

    document.addEventListener('DOMContentLoaded', function(){

        var inputElement = document.getElementById("modal-search-input");

        // Enfoca automáticamente el input cuando la página se carga

        inputElement.focus();

    })

</script>

