<?php

namespace App\Traits;

trait HasLogoBase64
{
    private function logoBase64($imageName)
    {
        $path = public_path('logo/' . $imageName);
        if (!file_exists($path)) {
            return asset('logo/' . $imageName);
        }
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
