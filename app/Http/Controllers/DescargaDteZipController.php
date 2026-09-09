<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DescargaDteZipController extends Controller
{
    public function __invoke(string $token): BinaryFileResponse
    {
        $key = 'dte-export:' . $token;
        $job = Cache::get($key);
        $user = Auth::user();

        abort_unless(
            $job
            && !empty($job['listo'])
            && (int) $job['user_id'] === (int) $user->id
            && (int) $job['empresa_id'] === (int) $user->empresa,
            404
        );

        $base = realpath(storage_path('app/dte-exports/' . (int) $user->id));
        $path = realpath($job['path']);
        abort_unless($base && $path && str_starts_with($path, $base . DIRECTORY_SEPARATOR) && is_file($path), 404);

        Cache::forget($key);

        return response()->download($path, $job['nombre'], [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
