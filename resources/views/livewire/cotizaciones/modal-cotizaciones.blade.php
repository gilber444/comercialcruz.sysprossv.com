<div wire:ignore.self class="modal fade" id="modalSearchPosProduct" tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <div class="input-group input-group-merge">

                    <span class="input-group-text">

                        <i class="fas fa-search"></i>

                    </span>

                    <input type="text" wire:model='search' id="modal-search-input" placeholder="Buscar por nombre, codigo de barras o categorias" class="form-control">

                </div>

            </div>

            <div class="modal-body">

                <div class="row p-2">

                    <div class="table-responsive">

                        <table class="table table-hover table-sm">

                            <thead>

                                <th></th>

                                <th class="text-center">CAJA</th>

                                <th class="text-center">PAQUETE</th>

                                <th class="text-center">PRODUCTO</th>

                                <th class="text-center">ALTERNATIVO</th>

                                <th class="text-center">DESCRIPCION</th>

                                <th class="text-center">MEDIDA</th>

                            </thead>

                            <tbody>

                                @forelse ($products as $product)

                                    <tr style="font-size: 80%">

                                        <td>{{ $product->barcode }}

                                            <!--<span>

                                            <img src="{{-- asset('storage/products/' . $product->imagen) --}}" alt="img" height="50" width="50" class="rounded">

                                            </span>-->

                                        </td>

                                        <td class="text-center">

                                            <a class="text-black" href="javascript:void(0);" wire:click="Add('{{$product->codebar1}}')">{{ $product->codebar1 }}</a>

                                        </td>

                                        <td class="text-center">

                                            <a class="text-black" href="javascript:void(0);" wire:click="Add('{{ $product->codebar2 }}')">{{ $product->codebar2 }}</a>

                                        </td>

                                        <td class="text-center">

                                            <a class="text-black" href="javascript:void(0);" wire:click="Add('{{ $product->codebar3 }}')">{{ $product->codebar3 }}</a>

                                        </td>

                                        <td class="text-center">

                                            <a class="text-black" href="javascript:void(0);" wire:click="Add('{{ $product->codealternativo }}')">{{ $product->codealternativo }}</a>

                                        </td>

                                        <td>

                                            <a class="text-black" href="javascript:void(0);" wire:click="Add('{{ $product->id }}')"><b>{{ $product->nombreProducto }}</b></a>



                                        </td>

                                        <td class="text-center">

                                           {{ $product->medida }}

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

    document.addEventListener('livewire:load', function () {

        Livewire.on('wire:init', () => {

    setTimeout(() => {

        var inputElement = document.getElementById("modal-search-input");

        if (inputElement) {

            inputElement.focus();

        }

    }, 100); // 100 milisegundos de retraso

});

    });

</script>

