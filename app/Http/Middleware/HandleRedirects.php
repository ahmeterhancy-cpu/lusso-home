<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Panelden tanımlanan 301/302 yönlendirmeleri (eski adresler, kaldırılan ürünler). */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->is('admin*', 'livewire*')) {
            $map = Redirect::map();
            if ($map) {
                $path = Redirect::normalize($request->getPathInfo());
                if (isset($map[$path])) {
                    [$to, $status, $id] = $map[$path];
                    Redirect::whereKey($id)->update(['hits' => \DB::raw('hits + 1'), 'last_hit_at' => now()]);

                    return redirect()->to(str_starts_with($to, 'http') ? $to : url($to), $status);
                }
            }
        }

        return $next($request);
    }
}
