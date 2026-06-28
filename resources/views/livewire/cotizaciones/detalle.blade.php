<div class="col-sm-12 col-md-9 border rounded p-3 mb-3">
    <div class="table-responsive text-nowrap">
        <table class="table table-hover table-sm">
            <thead>
                <th></th>
                <th class="text-center">Cant.</th>
                <th class="text-center">Descripcion</th>
                <th class="text-center">Medida</th>
                <th class="text-center">Precio</th>
                <th class="text-center">Des.</th>
                <th class="text-center">Total</th>
            </thead>
            <tbody>
                @foreach ($cart as $item)
                    <tr style="font-size: 85%">
                        <td style="width: 5%;">
                            <button wire:click.prevent='removeItem({{$item->id}})' type="button" class="btn btn-sm btn-label">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                        </td>
                        <td style="width: 10%;">
                            <input type="text" class="form-control" placeholder="{{$item->quantity}}" wire:model='can.{{$item->id}}' wire:keydown.enter="CantiUpdate({{$item->id}}, {{ $item->codebar }} )" value="{{$item->quantity}} ">
                        </td>
                        <td style="width: 55%;">{{ $item->name }}</td>
                        <td class="text-center">
                            <select class="form-control" wire:model="uni.{{ $item->id }}" wire:change="UpdateUni({{ $item->id }})">
                                @php
                                    $unidades = \App\Models\Precios::join('medidas as d', 'd.id', '=', 'precios.medida')
                                    ->where('precios.producto', $item->producto)
                                    ->select('d.id', 'd.unidad as medida')
                                    ->groupBy('d.id')
                                    ->groupBy('d.unidad')
                                    ->get();
                                @endphp
                                @foreach ($unidades as $u)
                                    <option value="{{ $u->id }}">{{ $u->medida }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <select class="form-control" wire:model="pri.{{ $item->id }}" wire:change="UpdatePrice({{ $item->id }})">
                                @php
                                    $precios = \App\Models\Precios::where('producto', $item->producto)->where('medida', $item->uni)->get();
                                @endphp
                                @foreach ($precios as $p)
                                    <option value="{{ $p->id }}">{{ number_format($p->pvventa, 2) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">{{ number_format($item->descuento, 2) }}</td>
                        <td class="text-end">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
