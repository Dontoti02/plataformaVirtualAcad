<?php

namespace Modules\Shared\Services;

use MiladRahimi\Jwt\Generator;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Parser;
use RuntimeException;

class JWTService
{
    # time in days
    protected static $EXPIRATION_TIME = 1;

    public static function sign($payload, $remember = false): string
    {
        $issuedAt = time();

        $days = self::$EXPIRATION_TIME;

        if ($remember) {
            $days = 7;
        }

        $expirationTime = $issuedAt + 60 * 60 * 24 * ($days);

        $signer = new HS256(self::key());
        $generator = new Generator($signer);

        $jwt = $generator->generate([
            ...$payload,
            'exp' => $expirationTime
        ]);

        return $jwt;
    }


    public static function valid(string $token)
    {
        try {
            $signer = new HS256(self::key());
            $parser = new Parser($signer);

            $claims = $parser->parse($token);

            return $claims;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * El secreto sale del .env (JWT_SECRET) y es distinto en cada institución.
     *
     * Antes estaba escrito en este archivo: el mismo en todos los institutos y
     * legible por cualquiera con acceso al código, que podía fabricar un token
     * válido para cualquier usuario. La librería acepta hasta una clave vacía,
     * así que la comprobación tiene que hacerse aquí.
     */
    private static function key(): HmacKey
    {
        $secret = (string) config('app.jwt_secret');

        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET no está configurado o tiene menos de 32 caracteres.');
        }

        return new HmacKey($secret);
    }
}
