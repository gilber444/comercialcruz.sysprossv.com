<ul class="menu-inner">
    <!-- Dashboards -->
    <li class="menu-item {{ Request::is('home') ? 'active' : '' }}">
        <a href="{{ route('home') }}" class="menu-link">
            <i class="menu-icon fa-solid fa-home"></i>
            <div data-i18n="Inicio">Inicio</div>
        </a>
    </li>
    <!--Configuraciones-->
    @can('Configuraciones_Index')
        <li
            class="menu-item {{ Request::is('roles') ? 'active' : '' }} {{ Request::is('permisos') ? 'active' : '' }} {{ Request::is('users') ? 'active' : '' }} {{ Request::is('asignar') ? 'active' : '' }} {{ Request::is('ubicaciones') ? 'active' : '' }} {{ Request::is('bancos') ? 'active' : '' }} {{ Request::is('bancos') ? 'active' : '' }} {{ Request::is('mensajerias') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons fa-solid fa-gears"></i>
                <div data-i18n="Configuraciones">Configuraciones</div>
            </a>
            <ul class="menu-sub">
                @can('User_Index')
                    <li class="menu-item {{ Request::is('users') ? 'active' : '' }}">
                        <a href="{{ route('users') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-user-group"></i>
                            <div data-i18n="Users">Users</div>
                        </a>
                    </li>
                @endcan
                @can('RolesPermisos_Index')
                    <li
                        class="menu-item {{ Request::is('roles') ? 'active' : '' }} {{ Request::is('permisos') ? 'active' : '' }} {{ Request::is('asignar') ? 'active' : '' }} ">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-check-shield"></i>
                            <div data-i18n="Roles & Permisos">Roles & Permisos</div>
                        </a>
                        <ul class="menu-sub">
                            @can('Roles_Index')
                                <li class="menu-item {{ Request::is('roles') ? 'active' : '' }}">
                                    <a href="{{ route('roles') }}" class="menu-link">
                                        <div data-i18n="Roles">Roles</div>
                                    </a>
                                </li>
                            @endcan
                            @can('Permisos_Index')
                                <li class="menu-item {{ Request::is('permisos') ? 'active' : '' }}">
                                    <a href="{{ route('permisos') }}" class="menu-link">
                                        <div data-i18n="Permission">Permission</div>
                                    </a>
                                </li>
                            @endcan
                            @can('Asignar_Index')
                                <li class="menu-item {{ Request::is('asignar') ? 'active' : '' }}">
                                    <a href="{{ route('asignar') }}" class="menu-link">
                                        <div data-i18n="Asignar">Asignar</div>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan
                {{-- @can('Ubicaciones_Index')
                    <li class="menu-item {{ Request::is('ubicaciones') ? 'active' : '' }}">
                        <a href="{{ route('ubicaciones') }}" class="menu-link">
                            <i class="menu-icon tf-icons fa-solid fa-location-dot"></i>
                            <div data-i18n="Ubicaciones">Ubicaciones</div>
                        </a>
                    </li>
                @endcan --}}
                @can('TiposPagos_Index')
                    <li class="menu-item {{ Request::is('tipoPagos') ? 'active' : '' }}">
                        <a href="{{ route('tipoPagos') }}" class="menu-link">
                            <i class="menu-icon tf-icons fa-solid fa-cash-register"></i>
                            <div data-i18n="Formas de Pago">Formas de Pago</div>
                        </a>
                    </li>
                @endcan
                @can('Bancos_Index')
                    <li class="menu-item {{ Request::is('bancos') ? 'active' : '' }}">
                        <a href="{{ route('bancos') }}" class="menu-link">
                            <div data-i18n="Bancos">Bancos</div>
                        </a>
                    </li>
                @endcan
                @can('Mensajerias_Index')
                    <li class="menu-item {{ Request::is('mensajerias') ? 'active' : '' }}">
                        <a href="{{ route('mensajerias') }}" class="menu-link">
                            <div data-i18n="Mensajeria">Mensajeria</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcan
    @can('AdminEmpresa_Index')
        <!--Admin Empresa-->
        <li
            class="menu-item {{ Request::is('empresa') ? 'active' : '' }} {{ Request::is('sucursales') ? 'active' : '' }} {{ Request::is('parametros') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon fa-solid fa-building"></i>
                <div data-i18n="Admin Empresa">Admin Empresa</div>
            </a>
            <ul class="menu-sub">
                @can('Empresa_Index')
                    <li class="menu-item {{ Request::is('empresa') ? 'active' : '' }}">
                        <a href="{{ route('empresa') }}" class="menu-link">
                            <div data-i18n="Empresa">Empresa</div>
                        </a>
                    </li>
                @endcan
                @can('Sucursales_Index')
                    <li class="menu-item {{ Request::is('sucursales') ? 'active' : '' }}">
                        <a href="{{ route('sucursales') }}" class="menu-link">
                            <div data-i18n=" Sucursales">Sucursales</div>
                        </a>
                    </li>
                @endcan
                @can('Parametros_Index')
                    <li class="menu-item {{ Request::is('parametros') ? 'active' : '' }}">
                        <a href="{{ route('parametros') }}" class="menu-link">
                            <div data-i18n="Parametros">Parametros</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcan
    <!--Admin Catalagos -->
    @can('AdminCatalagos_Index')
        <li
            class="menu-item {{ Request::is('departamentos') ? 'active' : '' }} {{ Request::is('municipios') ? 'active' : '' }} {{ Request::is('actividad_economica') ? 'active' : '' }} {{ Request::is('pais') ? 'active' : '' }} {{ Request::is('distritos') ? 'active' : '' }} {{ Request::is('ambiente_destinos') ? 'active' : '' }} {{ Request::is('tipo_documentos') ? 'active' : '' }} {{ Request::is('modelo_facturacion') ? 'active' : '' }} {{ Request::is('tipo_transmision') ? 'active' : '' }} {{ Request::is('tipo_contingencias') ? 'active' : '' }} {{ Request::is('retencion') ? 'active' : '' }} {{ Request::is('tipo_generacion_documento') ? 'active' : '' }} {{ Request::is('tipo_establecimiento') ? 'active' : '' }} {{ Request::is('servicio_medico') ? 'active' : '' }} {{ Request::is('tipo_item') ? 'active' : '' }} {{ Request::is('unidad_medida') ? 'active' : '' }} {{ Request::is('tributos') ? 'active' : '' }} {{ Request::is('condicion_operacion') ? 'active' : '' }} {{ Request::is('forma_pago') ? 'active' : '' }} {{ Request::is('plazos') ? 'active' : '' }} {{ Request::is('documentos_asociados') ? 'active' : '' }} {{ Request::is('identificacion_receptor') ? 'active' : '' }} {{ Request::is('tipo_invalidacion') ? 'active' : '' }} {{ Request::is('titulo_remiten_bienes') ? 'active' : '' }} {{ Request::is('donacion') ? 'active' : '' }} {{ Request::is('recinto_fiscal') ? 'active' : '' }} {{ Request::is('regimen') ? 'active' : '' }} {{ Request::is('tipo_persona') ? 'active' : '' }} {{ Request::is('transporte') ? 'active' : '' }} {{ Request::is('incoterms') ? 'active' : '' }} {{ Request::is('domicilio_fiscal') ? 'active' : '' }} {{ Request::is('clase_documento') ? 'active' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class='menu-icon tf-icons fa-solid fa-book'></i>
                <div data-i18n="Catalogos DTE">Catalogos DTE</div>
            </a>
            <ul class="menu-sub">
                @can('AmbienteDestino_Index')
                    <li class="menu-item {{ Request::is('ambiente_destinos') ? 'active' : '' }}">
                        <a href="{{ route('ambiente_destinos') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-earth-americas"></i>
                            <div data-i18n="Ambiente Destino">Ambiente Destino</div>
                        </a>
                    </li>
                @endcan
                @can('TipoDocumentos_Index')
                    <li class="menu-item {{ Request::is('tipo_documentos') ? 'active' : '' }}">
                        <a href="{{ route('tipo_documentos') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-file"></i>
                            <div data-i18n="Tipo Documento">Tipo Documento</div>
                        </a>
                    </li>
                @endcan
                @can('ModeloFacturacion_Index')
                    <li class="menu-item {{ Request::is('modelo_facturacion') ? 'active' : '' }}">
                        <a href="{{ route('modelo_facturacion') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-file-invoice"></i>
                            <div data-i18n="Modelo Facturacion">Modelo Facturacion</div>
                        </a>
                    </li>
                @endcan
                @can('TipoTransmision_Index')
                    <li class="menu-item {{ Request::is('tipo_transmision') ? 'active' : '' }}">
                        <a href="{{ route('tipo_transmision') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-right-left"></i>
                            <div data-i18n="Tipo Transmision">Tipo Transmision</div>
                        </a>
                    </li>
                @endcan
                @can('TipoContingencia_Index')
                    <li class="menu-item {{ Request::is('tipo_contingencias') ? 'active' : '' }}">
                        <a href="{{ route('tipo_contingencias') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-toolbox"></i>
                            <div data-i18n="Tipo Contingencia">Tipo Contingencia</div>
                        </a>
                    </li>
                @endcan
                @can('Retencion_Index')
                    <li class="menu-item {{ Request::is('retencion') ? 'active' : '' }}">
                        <a href="{{ route('retencion') }}" class="menu-link">
                            <i class="menu-icon fa-brands fa-creative-commons-nc"></i>
                            <div data-i18n="Retencion IVA">Retencion IVA</div>
                        </a>
                    </li>
                @endcan
                @can('TipoGeneracion_Index')
                    <li class="menu-item {{ Request::is('tipo_generacion_documento') ? 'active' : '' }}">
                        <a href="{{ route('tipo_generacion_documento') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-worm"></i>
                            <div data-i18n="Generacion Documento">Generacion Documento</div>
                        </a>
                    </li>
                @endcan
                @can('TipoEstablecimiento_Index')
                    <li class="menu-item {{ Request::is('tipo_establecimiento') ? 'active' : '' }}">
                        <a href="{{ route('tipo_establecimiento') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-building"></i>
                            <div data-i18n="Tipo Establecimientos">Tipo Establecimientos</div>
                        </a>
                    </li>
                @endcan
                @can('ServicioMedico_Index')
                    <li class="menu-item {{ Request::is('servicio_medico') ? 'active' : '' }}">
                        <a href="{{ route('servicio_medico') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-laptop-medical"></i>
                            <div data-i18n="Servicio Medico">Servicio Medico</div>
                        </a>
                    </li>
                @endcan
                @can('TipoItem_Index')
                    <li class="menu-item {{ Request::is('tipo_item') ? 'active' : '' }}">
                        <a href="{{ route('tipo_item') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-info"></i>
                            <div data-i18n="Tipo Item">Tipo Item</div>
                        </a>
                    </li>
                @endcan
                @can('Departamentos_Index')
                    <li class="menu-item {{ Request::is('departamentos') ? 'active' : '' }}">
                        <a href="{{ route('departamentos') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-layer-group"></i>
                            <div data-i18n="Departamentos">Departamentos</div>
                        </a>
                    </li>
                @endcan
                @can('Municipios_Index')
                    <li class="menu-item {{ Request::is('municipios') ? 'active' : '' }}">
                        <a href="{{ route('municipios') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-map"></i>
                            <div data-i18n="Municipios">Municipios</div>
                        </a>
                    </li>
                @endcan
                @can('Distritos_Index')
                    <li class="menu-item {{ Request::is('distritos') ? 'active' : '' }}">
                        <a href="{{ route('distritos') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-map-pin"></i>
                            <div data-i18n="Distritos">Distritos</div>
                        </a>
                    </li>
                @endcan
                @can('UnidadMedida_Index')
                    <li class="menu-item {{ Request::is('unidad_medida') ? 'active' : '' }}">
                        <a href="{{ route('unidad_medida') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-bars"></i>
                            <div data-i18n="Unidad Medidas">Unidad Medidas</div>
                        </a>
                    </li>
                @endcan
                @can('Tributos_Index')
                    <li class="menu-item {{ Request::is('tributos') ? 'active' : '' }}">
                        <a href="{{ route('tributos') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-suitcase"></i>
                            <div data-i18n="Tributos">Tributos</div>
                        </a>
                    </li>
                @endcan
                @can('CondicionOperacion_Index')
                    <li class="menu-item {{ Request::is('condicion_operacion') ? 'active' : '' }}">
                        <a href="{{ route('condicion_operacion') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-shield-halved"></i>
                            <div data-i18n="Condicion Operacion">Condicion Operacion</div>
                        </a>
                    </li>
                @endcan
                @can('FormaPago_Index')
                    <li class="menu-item {{ Request::is('forma_pago') ? 'active' : '' }}">
                        <a href="{{ route('forma_pago') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-pager"></i>
                            <div data-i18n="Forma Pago">Forma Pago</div>
                        </a>
                    </li>
                @endcan
                @can('Plazos_Index')
                    <li class="menu-item {{ Request::is('plazos') ? 'active' : '' }}">
                        <a href="{{ route('plazos') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-calendar"></i>
                            <div data-i18n="Plazos">Plazos</div>
                        </a>
                    </li>
                @endcan
                @can('ActividadEconomica_Index')
                    <li class="menu-item {{ Request::is('actividad_economica') ? 'active' : '' }}">
                        <a href="{{ route('actividad_economica') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-magnifying-glass-chart"></i>
                            <div data-i18n="Actividad Economica">Actividad Economica</div>
                        </a>
                    </li>
                @endcan
                @can('Pais_Index')
                    <li class="menu-item {{ Request::is('pais') ? 'active' : '' }}">
                        <a href="{{ route('pais') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-globe"></i>
                            <div data-i18n="Pais">Pais</div>
                        </a>
                    </li>
                @endcan
                @can('DocumentoAsociado_Index')
                    <li class="menu-item {{ Request::is('documentos_asociados') ? 'active' : '' }}">
                        <a href="{{ route('documentos_asociados') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-file"></i>
                            <div data-i18n="Documentos Asociados">Documentos Asociados</div>
                        </a>
                    </li>
                @endcan
                @can('IdentificacionReceptor_Index')
                    <li class="menu-item {{ Request::is('identificacion_receptor') ? 'active' : '' }}">
                        <a href="{{ route('identificacion_receptor') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-address-card"></i>
                            <div data-i18n="Identificacion Receptor">Identificacion Receptor</div>
                        </a>
                    </li>
                @endcan
                @can('DocumentoContigencia_Index')
                    <li class="menu-item {{ Request::is('documento_contingencia') ? 'active' : '' }}">
                        <a href="{{ route('documento_contingencia') }}" class="menu-link">
                            <div data-i18n="Documento Contingencia">Documento Contingencia</div>
                        </a>
                    </li>
                @endcan
                @can('TipoInvalidacion_Index')
                    <li class="menu-item {{ Request::is('tipo_invalidacion') ? 'active' : '' }}">
                        <a href="{{ route('tipo_invalidacion') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-triangle-exclamation"></i>
                            <div data-i18n="Tipo Invalidacion">Tipo Invalidacion</div>
                        </a>
                    </li>
                @endcan
                @can('TituloRemiteBienes_Index')
                    <li class="menu-item {{ Request::is('titulo_remiten_bienes') ? 'active' : '' }}">
                        <a href="{{ route('titulo_remiten_bienes') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-door-closed"></i>
                            <div data-i18n="Remision de Bienes">Remision de Bienes</div>
                        </a>
                    </li>
                @endcan
                @can('Donacion_Index')
                    <li class="menu-item {{ Request::is('donacion') ? 'active' : '' }}">
                        <a href="{{ route('donacion') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-hand-holding-dollar"></i>
                            <div data-i18n="Tipo de Donacion">Tipo de Donacion</div>
                        </a>
                    </li>
                @endcan
                @can('RecintoFiscal_Index')
                    <li class="menu-item {{ Request::is('recinto_fiscal') ? 'active' : '' }}">
                        <a href="{{ route('recinto_fiscal') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-building-columns"></i>
                            <div data-i18n="Recinto Fiscal">Recinto Fiscal</div>
                        </a>
                    </li>
                @endcan
                @can('Regimen_Index')
                    <li class="menu-item {{ Request::is('regimen') ? 'active' : '' }}">
                        <a href="{{ route('regimen') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-registered"></i>
                            <div data-i18n="Regimen">Regimen</div>
                        </a>
                    </li>
                @endcan
                @can('TipoPersona_Index')
                    <li class="menu-item {{ Request::is('tipo_persona') ? 'active' : '' }}">
                        <a href="{{ route('tipo_persona') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-user-tie"></i>
                            <div data-i18n="Tipo Persona">Tipo Persona</div>
                        </a>
                    </li>
                @endcan
                @can('Transporte_Index')
                    <li class="menu-item {{ Request::is('transporte') ? 'active' : '' }}">
                        <a href="{{ route('transporte') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-truck-arrow-right"></i>
                            <div data-i18n="Transporte">Transporte</div>
                        </a>
                    </li>
                @endcan
                @can('Incoterms_Index')
                    <li class="menu-item {{ Request::is('incoterms') ? 'active' : '' }}">
                        <a href="{{ route('incoterms') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-asterisk"></i>
                            <div data-i18n="INCOTERMS">INCOTERMS</div>
                        </a>
                    </li>
                @endcan
                @can('DomicilioFiscal_Index')
                    <li class="menu-item {{ Request::is('domicilio_fiscal') ? 'active' : '' }}">
                        <a href="{{ route('domicilio_fiscal') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-map-location-dot"></i>
                            <div data-i18n="Domicilio Fiscal">Domicilio Fiscal</div>
                        </a>
                    </li>
                @endcan
                @can('ClaseDocumento_Index')
                    <li class="menu-item {{ Request::is('clase_documento') ? 'active' : '' }}">
                        <a href="{{ route('clase_documento') }}" class="menu-link">
                            <div data-i18n="Clase Documento">Clase Documento</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcan
    <!-- Admin Productos -->
    @can('Admin_Productos')
        <li
            class="menu-item {{ Request::is('medidas') ? 'active' : '' }} {{ Request::is('categorias') ? 'active' : '' }} {{ Request::is('familia') ? 'active' : '' }} {{ Request::is('productoAdmin') ? 'active' : '' }} {{ Request::is('editarProduct') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon fa-brands fa-product-hunt"></i>
                <div data-i18n="Admin Productos">Admin Productos</div>
            </a>
            <ul class="menu-sub">
                @can('Productos_Index')
                    <li
                        class="menu-item {{ Request::is('productoAdmin') ? 'active' : '' }} {{ Request::is('editarProduct') ? 'active' : '' }}">
                        <a href="{{ route('productoAdmin') }}" class="menu-link">
                            <i class="menu-icon fa-brands fa-product-hunt"></i>
                            <div data-i18n="Productos">Productos</div>
                        </a>
                    </li>
                @endcan
                @can('Medidas_Index')
                    <li class="menu-item {{ Request::is('medidas') ? 'active' : '' }}">
                        <a href="{{ route('medidas') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-menu"></i>
                            <div data-i18n="Unidad de Medida">Unidad de Medida</div>
                        </a>
                    </li>
                @endcan
                @can('Categorias_Index')
                    <li class="menu-item {{ Request::is('categorias') ? 'active' : '' }}">
                        <a href="{{ route('categorias') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-certificate"></i>
                            <div data-i18n="Categorias de Productos">Categorias</div>
                        </a>
                    </li>
                @endcan
                @can('Familias_Index')
                    <li class="menu-item {{ Request::is('familia') ? 'active' : '' }}">
                        <a href="{{ route('familia') }}" class="menu-link">
                            <i class="menu-icon fa-solid fa-f"></i>
                            <div data-i18n="Familia de Productos">Familia de Productos</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcan
    <!-- Admin Inventarios -->
    @can('Admin_Inventarios')
        @php
            $user = Auth::user();

            // Consulta basada en el perfil del usuario
            $productosBajoMinimo = DB::table('inventarios')
                ->join('productos as p', 'inventarios.producto', '=', 'p.id')
                ->when($user->profile != 'Super' && $user->profile != 'Administrador', function ($query) use ($user) {
                    return $query->where('inventarios.sucursal', $user->sucursal);
                })
                ->whereColumn('inventarios.existencia', '<=', 'p.minimo')
                ->count();
        @endphp
        <li
            class="menu-item
            {{ Request::is('existencias') ? 'active' : '' }}
            {{ Request::is('solicitudes') ? 'active' : '' }}
            {{ Request::is('solicitudesVer') ? 'active' : '' }}
            {{ Request::is('kardex') ? 'active' : '' }}
            {{ Request::is('ajustes') ? 'active' : '' }}
            {{ Request::is('hoja_inventarios') ? 'active' : '' }}
        ">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon fa-solid fa-cart-flatbed-suitcase"></i>
                <div data-i18n="Admin Inventarios">Admin Inventarios</div>
                @if ($user->profile == 'Super' || $user->profile == 'Administrador')
                    @if ($productosBajoMinimo > 0)
                        <span class="badge rounded-pill bg-danger ms-auto">{{ $productosBajoMinimo }}</span>
                    @endif
                @endif
            </a>
            <ul class="menu-sub">
                @can('Existencias_Index')
                    <li class="menu-item {{ Request::is('existencias') ? 'active' : '' }}">
                        <a href="{{ route('existencias') }}" class="menu-link">
                            <div data-i18n="Existencias">Existencias</div>
                            @if ($user->profile == 'Super' || $user->profile == 'Administrador')
                                @if ($productosBajoMinimo > 0)
                                    <span class="badge rounded-pill bg-danger ms-auto">{{ $productosBajoMinimo }}</span>
                                @endif
                            @endif
                        </a>
                    </li>
                @endcan
                @can('Inventario_Index')
                    <li class="menu-item {{ Request::is('hoja_inventarios') ? 'active' : '' }}">
                        <a href="{{ route('hoja_inventarios') }}" class="menu-link">
                            <div data-i18n="Hoja de Inventario">Hoja de Inventario</div>
                        </a>
                    </li>
                @endcan
                @can('Solicitudes_Create')
                    <li class="menu-item {{ Request::is('solicitudes') ? 'active' : '' }}">
                        <a href="{{ route('solicitudes') }}" class="menu-link">
                            <div data-i18n="Nueva Solicitud">Nueva Solicitud</div>
                        </a>
                    </li>
                @endcan
                @can('Solicitudes_Index')
                    <li class="menu-item {{ Request::is('solicitudesVer') ? 'active' : '' }}">
                        <a href="{{ route('solicitudesVer') }}" class="menu-link">
                            <div data-i18n="Solicitudes Realizadas">Solicitudes Realizadas</div>
                        </a>
                    </li>
                @endcan
                @can('Ajustes_Index')
                    <li class="menu-item {{ Request::is('ajustes') ? 'active' : '' }}">
                        <a href="{{ route('ajustes') }}" class="menu-link">
                            <div data-i18n="Ajustes">Ajustes</div>
                        </a>
                    </li>
                @endcan
                @can('CompararInventario_Index')
                    <li class="menu-item {{ Request::is('comparar-inventario') ? 'active' : '' }}">
                        <a href="{{ route('comparar-inventario') }}" class="menu-link">
                            <div data-i18n="Comparar Inventario">Comparar Inventario</div>
                        </a>
                    </li>
                @endcan
                @can('Kardex_Index')
                    <li class="menu-item {{ Request::is('kardex') ? 'active' : '' }}">
                        <a href="{{ route('kardex') }}" class="menu-link">
                            <div data-i18n="Generar Kardex">Generar Kardex</div>
                        </a>
                    </li>
                @endcan
                @can('Kardex_Index')
                    <li class="menu-item {{ Request::is('conciliacion-inventario') ? 'active' : '' }}">
                        <a href="{{ route('conciliacion-inventario') }}" class="menu-link">
                            <div data-i18n="Conciliacion Inventario">Conciliacion Inventario</div>
                        </a>
                    </li>
                @endcan
                @can('ReconciliarSincroId_Index')
                    <li class="menu-item {{ Request::is('reconciliar-sincro-id') ? 'active' : '' }}">
                        <a href="{{ route('reconciliar-sincro-id') }}" class="menu-link">
                            <div data-i18n="Reconciliar Sincro ID">Reconciliar Sincro ID</div>
                        </a>
                    </li>
                @endcan
            </ul> 
        </li>
        <!-- kardex -->
        <!-- ajuste de inventario -->
        <!-- movimientos -->
    @endcan
    <!-- Compras -->
    @can('ComprasAdmin_Index')
        <li
            class="menu-item {{ Request::is('compras') ? 'active' : '' }} {{ Request::is('nueva-compra') ? 'active' : '' }} {{ Request::is('editarCompra/') ? 'active' : '' }} {{ Request::is('proveedores') ? 'active' : '' }}  {{ Request::is('precompra') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon fa-solid fa-cart-shopping"></i>
                <div data-i18n="Admin Compras">Admin Compras</div>
            </a>
            <ul class="menu-sub">
                @can('Precompra_Index')
                    <li class="menu-item {{ Request::is('precompra') ? 'active' : '' }}">
                        <a href="{{ route('precompra') }}" class="menu-link">
                            <div data-i18n="Precompra Recepcion JSON">IPrecompra Recepcion JSON</div>
                        </a>
                    </li>
                @endcan
                @can('Compras_Create')
                    <li class="menu-item {{ Request::is('nueva-compra') ? 'active' : '' }}">
                        <a href="{{ route('nueva-comra') }}" class="menu-link">
                            <div data-i18n="Igresar Compras">Ingresar Compras</div>
                        </a>
                    </li>
                @endcan
                @can('Compras_Index')
                    <li
                        class="menu-item {{ Request::is('compras') ? 'active' : '' }} {{ Request::is('editarCompra/') ? 'active' : '' }}">
                        <a href="{{ route('compras') }}" class="menu-link">
                            <div data-i18n="Ver Compras">Ver Compras</div>
                        </a>
                    </li>
                @endcan
                @can('Proveedores_Index')
                    <li class="menu-item {{ Request::is('proveedores') ? 'active' : '' }} ">
                        <a href="{{ route('proveedores') }}" class="menu-link">
                            <div data-i18n="Admin Proveedores">Admin Proveedores</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcan
    @can('VentasAdmin_Index')
        <li
            class="menu-item {{ Request::is('cotizaciones') ? 'active' : '' }} {{ Request::is('cortes') ? 'active' : '' }} {{ Request::is('clientes') ? 'active' : '' }} {{ request::is('remesas') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon fa-solid fa-basket-shopping"></i>
                <div data-i18n="Admin Ventas">Admin Ventas</div>
            </a>
            <ul class="menu-sub">
                @can('Cotizaciones_Index')
                    <li class="menu-item {{ Request::is('cotizaciones') ? 'active' : '' }}">
                        <a href="{{ route('cotizaciones') }}" class="menu-link">
                            <div data-i18n="Cotizaciones">Cotizaciones</div>
                        </a>
                    </li>
                @endcan
                @can('Cortez_Index')
                    <li class="menu-item {{ Request::is('cortes') ? 'active' : '' }}">
                        <a href="{{ route('cortes') }}" class="menu-link">
                            <div data-i18n="Cortes Z">Cortes Z</div>
                        </a>
                    </li>
                @endcan
                @can('Clientes_Index')
                    <li class="menu-item {{ Request::is('clientes') ? 'active' : '' }}">
                        <a href="{{ route('clientes') }}" class="menu-link">
                            <div data-i18n="Admin Clientes">Admin Clientes</div>
                        </a>
                    </li>
                @endcan
                @can('Remesas_Index')
                    <li class="menu-item {{ request::is('remesas') ? 'active' : '' }}">
                        <a href="{{ url('remesas') }}" class="menu-link">
                            <div data-i18n="Remesas">Remesas</div>
                        </a>
                    </li>
                @endcan
                {{-- @can('NotaCredito_Index')
                    <li class="menu-item {{ Request::is('nota_credito') ? 'active' : '' }} ">
                        <a href="{{ route('nota_credito') }}" class="menu-link">
                            <div data-i18n="Notas Credito">Notas Credito</div>
                        </a>
                    </li>
                @endcan
                @can('NuevaNotaCredito_Index')
                    <li class="menu-item {{ Request::is('nueva_nota_credito') ? 'active' : '' }} ">
                        <a href="{{ route('nueva_nota_credito') }}" class="menu-link">
                            <div data-i18n="Nueva Nota Credito">Nueva Nota Credito</div>
                        </a>
                    </li>
                @endcan
                @can('SujetoExcluidos_Index')
                    <li class="menu-item {{ Request::is('sujeto-excluidos') ? 'active' : '' }}">
                        <a href="{{ url('sujeto-excluidos') }}" class="menu-link">
                            <div data-i18n="Sujeto Excluido">Sujeto Excluido</div>
                        </a>
                    </li>
                @endcan --}}
            </ul>
        </li>
    @endcan
    @can('Pos_Index')
        <li class="menu-item {{ Request::is('actividad') ? 'active' : '' }}">
            <a href="{{ route('actividad') }}" class="menu-link">
                <i class="menu-icon fa-solid fa-basket-shopping"></i>
                <div data-i18n="Facturar">Facturar</div>
            </a>
        </li>
    @endcan
    @can('AdminTransmision_Index')
        <li
            class="menu-item {{ Request::is('firmador') ? 'active' : '' }} {{ Request::is('tocken') ? 'active' : '' }} {{ Request::is('ap') ? 'active' : '' }} {{ Request::is('dtes') ? 'active' : '' }} {{ request::is('invalidaciones') ? 'active' : '' }} {{ request::is('lote') ? 'active' : '' }} {{ request::is('contingencia') ? 'active' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon fa-brands fa-nfc-symbol"></i>
                <div data-i18n="Transmision">Transmision</div>
            </a>
            <ul class="menu-sub">
                @can('Firmador_Index')
                    <li class="menu-item {{ Request::is('firmador') ? 'active' : '' }}">
                        <a href="{{ route('firmador') }}" class="menu-link">
                            <div data-i18n="Firmador DTE">Firmador DTE</div>
                        </a>
                    </li>
                @endcan
                @can('Tocken_Index')
                    <li class="menu-item {{ Request::is('tocken') ? 'active' : '' }}">
                        <a href="{{ route('tocken') }}" class="menu-link">
                            <div data-i18n="Tocken DTE">Tocken DTE</div>
                        </a>
                    </li>
                @endcan
                @can('Apis_Index')
                    <li class="menu-item {{ Request::is('ap') ? 'active' : '' }}">
                        <a href="{{ route('ap') }}" class="menu-link">
                            <div data-i18n="Apis">Apis</div>
                        </a>
                    </li>
                @endcan
                @can('DTE_Index')
                    <li class="menu-item {{ Request::is('dtes') ? 'active' : '' }}">
                        <a href="{{ route('dtes') }}" class="menu-link">
                            <div data-i18n="DTE Generados">DTE Generados</div>
                        </a>
                    </li>
                @endcan
                @can('Invalidaciones_Index')
                    <li class="menu-item {{ request::is('invalidaciones') ? 'active' : '' }}">
                        <a href="{{ route('invalidaciones') }}" class="menu-link">
                            <div data-i18n="Invalidaciones DTE">Invalidaciones DTE</div>
                        </a>
                    </li>
                @endcan
                @can('Lote_Index')
                    <li class="menu-item {{ request::is('lote') ? 'active' : '' }}">
                        <a href="{{ route('lote') }}" class="menu-link">
                            <div data-i18n="Envio por Lote DTE">Envio por Lote DTE</div>
                        </a>
                    </li>
                @endcan
                @can('Contingencias_Index')
                    <li class="menu-item {{ request::is('contingencia') ? 'active' : '' }}">
                        <a href="{{ route('contingencia') }}" class="menu-link">
                            <div data-i18n="Contingencias DTE">Contingencias DTE</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcan
    @can('ReportsAdmin_Index')
        <li
            class="menu-item
            {{ request::is('reportsVenta') ? 'active' : '' }}
            {{ request::is('reportsCompra') ? 'active' : '' }}
            {{ request::is('reportsInventario') ? 'active' : '' }} {{ request::is('reportsInventarioCategorias') ? 'active' : '' }}
            {{ request::is('reportCortesZ') ? 'active' : '' }}
            {{ request::is('reportArqueos') ? 'active' : '' }}
            {{ request::is('reportsUtilidad') ? 'active' : '' }}
            {{ request::is('reportsVentasSintetizado') ? 'active' : '' }}
            {{ request::is('reportsUtilidadSintetizado') ? 'active' : '' }}
        ">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon fa-solid fa-file-contract"></i>
                <div data-i18n="Admin Reports">Admin Reports</div>
            </a>
            <ul class="menu-sub">
                @can('ReportVentas_Index')
                    <li class="menu-item {{ request::is('reportsVenta') ? 'active' : '' }}">
                        <a href="{{ url('reportsVenta') }}" class="menu-link">
                            <div data-i18n="Reporte de Ventas">Reporte de Ventas</div>
                        </a>
                    </li>
                @endcan
                @can('ReportVentas_Index')
                    <li class="menu-item {{ request::is('reportsVentasSintetizado') ? 'active' : '' }}">
                        <a href="{{ url('reportsVentasSintetizado') }}" class="menu-link">
                            <div data-i18n="Report. Ventas Sintetizado">Report. Ventas Sintetizado</div>
                        </a>
                    </li>
                @endcan
                @can('ReportUtilidad_Index')
                    <li class="menu-item {{ request::is('reportsUtilidad') ? 'active' : '' }}">
                        <a href="{{ url('reportsUtilidad') }}" class="menu-link">
                            <div data-i18n="Reporte de Utilidad">Reporte de Utilidad</div>
                        </a>
                    </li>
                @endcan
                @can('ReportUtilidadSintetizado_Index')
                    <li class="menu-item {{ request::is('reportsUtilidadSintetizado') ? 'active' : '' }}">
                        <a href="{{ url('reportsUtilidadSintetizado') }}" class="menu-link">
                            <div data-i18n="Report. Utilidad Sintetizado">Report. Utilidad Sintetizado</div>
                        </a>
                    </li>
                @endcan
                @can('ReportCompras_Index')
                    <li class="menu-item {{ request::is('reportsCompra') ? 'active' : '' }}">
                        <a href="{{ url('reportsCompra') }}" class="menu-link">
                            <div data-i18n="Reporte de Compras">Reporte de Compras</div>
                        </a>
                    </li>
                @endcan
                @can('ReporteInventario_Index')
                    <li class="menu-item {{ request::is('reportsInventario') ? 'active' : '' }}">
                        <a href="{{ url('reportsInventario') }}" class="menu-link">
                            <div data-i18n="Reporte Inventario">Reporte Inventario</div>
                        </a>
                    </li>
                @endcan
                @can('ReporteInventarioCategorias_Index')
                    <li class="menu-item {{ request::is('reportsInventarioCategorias') ? 'active' : '' }}">
                        <a href="{{ url('reportsInventarioCategorias') }}" class="menu-link">
                            <div data-i18n="Reporte Inventario X Categorias">Reporte Inventario X Categorias</div>
                        </a>
                    </li>
                @endcan
                @can('ReportArqueos_Index')
                    <li class="menu-item {{ request::is('reportArqueos') ? 'active' : '' }}">
                        <a href="{{ url('reportArqueos') }}" class="menu-link">
                            <div data-i18n="Reporte de Arqueos">Reporte de Arqueos</div>
                        </a>
                    </li>
                @endcan
                @can('ReportCortesZ_Index')
                    <li class="menu-item {{ request::is('reportCortesZ') ? 'active' : '' }}">
                        <a href="{{ url('reportCortesZ') }}" class="menu-link">
                            <div data-i18n="Reporte de Cortes Z">Reporte Reporte de Cortes Z</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcan
    @can('ContabilidadFinanzasAdmin_Index')
        <li
            class="menu-item {{ request::is('libroVentasConsumidor') ? 'active' : '' }} {{ request::is('cuentas_pagar') ? 'active' : '' }} {{ request::is('pagos') ? 'active' : '' }} {{ request::is('cuentas_cobrar') ? 'active' : '' }} {{ request::is('abonos') ? 'active' : '' }} {{ request::is('libroVentasConsumidor') ? 'active' : '' }} {{ request::is('libroVentasContribuyente') ? 'active' : '' }} {{ request::is('libroInvalidaciones') ? 'active' : '' }}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon fa-solid fa-calculator"></i>
                <div data-i18n="Finanzas & Contabilidad">Fiananzas & Contabilidad</div>
            </a>
            <ul class="menu-sub">
                @can('LibroVentasConsumidor_Index')
                    <li class="menu-item {{ request::is('libroVentasConsumidor') ? 'active' : '' }}">
                        <a href="{{ url('libroVentasConsumidor') }}" class="menu-link">
                            <div data-i18n="Libro Ventas Consumidor Final">Libro Ventas Consumidor Final</div>
                        </a>
                    </li>
                @endcan
                @can('LibroVentasContribuyente_Index')
                    <li class="menu-item {{ request::is('libroVentasContribuyente') ? 'active' : '' }}">
                        <a href="{{ url('libroVentasContribuyente') }}" class="menu-link">
                            <div data-i18n="Libro Ventas Contribuyente">Libro Ventas Contribuyente</div>
                        </a>
                    </li>
                @endcan
                @can('LibroInvalidaciones_Index')
                    <li class="menu-item {{ request::is('libroInvalidaciones') ? 'active' : '' }}">
                        <a href="{{ url('libroInvalidaciones') }}" class="menu-link">
                            <div data-i18n="Libro Invalidaciones">Libro Invalidaciones</div>
                        </a>
                    </li>
                @endcan
                @can('CuentasPagar_Index')
                    <li class="menu-item {{ request::is('cuentas_pagar') ? 'active' : '' }}">
                        <a href="{{ url('cuentas_pagar') }}" class="menu-link">
                            <div data-i18n="Cuentas por Pagar">Cuentas por Pagar</div>
                        </a>
                    </li>
                @endcan
                @can('Pagos_Index')
                    <li class="menu-item {{ request::is('pagos') ? 'active' : '' }}">
                        <a href="{{ url('pagos') }}" class="menu-link">
                            <div data-i18n="Pagos Realizados">Pagos Realizados</div>
                        </a>
                    </li>
                @endcan
                @can('CuentasCobrar_Index')
                    <li class="menu-item {{ request::is('cuentas_cobrar') ? 'active' : '' }} ">
                        <a href="{{ url('cuentas_cobrar') }}" class="menu-link">
                            <div data-i18n="Cuentas por Cobrar">Cuentas por Cobrar</div>
                        </a>
                    </li>
                @endcan
                @can('Abonos_Index')
                    <li
                        class="menu-item {{ request::is('abonos') ? 'active' : '' }} {{ request::is('abonos') ? 'active' : '' }}">
                        <a href="{{ url('abonos') }}" class="menu-link">
                            <div data-i18n="Abonos Realizados">Abonos Realizados</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcan
    <!-- Layouts --
        <li class="menu-item">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-layout"></i>
            <div data-i18n="Layouts">Layouts</div>
          </a>

          <ul class="menu-sub">
            <li class="menu-item">
              <a href="layouts-without-menu.html" class="menu-link">
                <i class="menu-icon tf-icons bx bx-menu"></i>
                <div data-i18n="Without menu">Without menu</div>
              </a>
            </li>
            <li class="menu-item">
              <a href="../vertical-menu-template/" class="menu-link" target="_blank">
                <i class="menu-icon tf-icons bx bx-vertical-center"></i>
                <div data-i18n="Vertical">Vertical</div>
              </a>
            </li>
            <li class="menu-item">
              <a href="layouts-fluid.html" class="menu-link">
                <i class="menu-icon tf-icons bx bx-fullscreen"></i>
                <div data-i18n="Fluid">Fluid</div>
              </a>
            </li>
            <li class="menu-item">
              <a href="layouts-container.html" class="menu-link">
                <i class="menu-icon tf-icons bx bx-exit-fullscreen"></i>
                <div data-i18n="Container">Container</div>
              </a>
            </li>
            <li class="menu-item">
              <a href="layouts-blank.html" class="menu-link">
                <i class="menu-icon tf-icons bx bx-square-rounded"></i>
                <div data-i18n="Blank">Blank</div>
              </a>
            </li>
          </ul>
        </li>-->
</ul>
