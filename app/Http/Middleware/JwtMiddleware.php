<?php

namespace App\Http\Middleware;

use App\Helpers\JsonResponseHelper;
use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\InvalidClaimException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


class JwtMiddleware
{
  public function handle(Request $request, Closure $next)
  {

    // dd("stop");
    try {
       $token = JWTAuth::getToken();
      if (!$token) {
        return JsonResponseHelper::standardResponse(401, null, 'Token missing');
      }
     $user = JWTAuth::setToken($token)->authenticate();
      if (!$user) {
        return JsonResponseHelper::standardResponse(
          401,
          null,
          'Unauthorized'
        );
        // return response()->json(['error' => ''], 401);
      }
      // dd([$user]);
      logger('JwtMiddleware running...');
      error_log("bootstrap/app.php is loading...");

      // Attach user to request
      $request->merge(['user' => $user]);
    } catch (JWTException $e) {
      return JsonResponseHelper::standardResponse(
        401,
        null,
        'Invalid or expired token'
      );
    } catch (TokenExpiredException $e) {
      return JsonResponseHelper::standardResponse(
        401,
        null,
        'Token expired'
      );
    } catch (TokenInvalidException $e) {
      return JsonResponseHelper::standardResponse(
        401,
        null,
        'Token invalid'
      );
    } catch (InvalidClaimException $e) {
      return JsonResponseHelper::standardResponse(
        401,
        null,
        'Token invalid'
      );
    } catch (JWTException $e) {
      return JsonResponseHelper::standardResponse(
        401,
        null,
        'Invalid or missing token'
      );
    } catch (UnauthorizedHttpException $e) {
      return JsonResponseHelper::standardResponse(
        401,
        null,
        'Invalid or missing token'
      );
    }

    return $next($request);
  }
}
