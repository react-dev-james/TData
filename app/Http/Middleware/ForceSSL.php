<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;

class ForceSSL
{
    /**
     * Force request to SSL when on production environment.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle( $request, Closure $next )
    {
        if ( !app()->environment( 'local' ) && !app()->environment( 'devel' )) {
            // for Proxies
            Request::setTrustedProxies( [ $request->getClientIp() ] );

            if ( !$request->isSecure() ) {
                return redirect()->secure( $request->getRequestUri() );
            }
        }

        return $next( $request );
    }
}