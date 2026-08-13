<?php

/**
 * Created by IntelliJ IDEA.
 * User: andres
 * Date: 1/14/15
 * Time: 6:00 PM.
 */

namespace SIUToba\rest\seguridad\autenticacion\oauth2;

use Symfony\Contracts\Cache\CacheInterface;

abstract class oauth_token_decoder
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    public function set_cache_manager(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $token un token oauth
     *
     * @return token_info
     */
    abstract public function decode($token);
}
