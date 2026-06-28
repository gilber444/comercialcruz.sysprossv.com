<div class="app-brand demo">
    <a href="{{ route('home') }}" class="app-brand-link">
        <span class="app-brand-logo demo">
            @php
                $empresa = DB::table('empresas')->first();
            @endphp
            @if ($empresa)
                <img src="public/logo/{{ $empresa->image; }}" width="120px" class="responsive">
            @endif
        </span>

    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
        <i class="bx bx-chevron-left bx-sm align-middle"></i>
    </a>
</div>

<div class="menu-inner-shadow"></div>
