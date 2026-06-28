<footer class="content-footer footer bg-footer-theme">
    <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
      <div class="mb-2 mb-md-0">
        ©
        <script>
          document.write(new Date().getFullYear());
        </script>
        , Syspros by
        <a href="https://www.sysprossv.com" class="footer-link fw-bolder" target="_blank">Ing. Gilber Edgardo Reyes Muñoz</a>
      </div>
      <div>
        <a href="#" class="footer-link me-4">Sistema Gestion de Ventas y Empresarial para
            @php
            $empresa = DB::table('empresas')->first();
            @endphp
            @if ($empresa)
                {{$empresa->razon}}
            @endif

        </a>
        <a href="#" class="footer-link d-none d-sm-inline-block">V 2.0 LV</a>
      </div>
    </div>
</footer>
