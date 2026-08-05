<?php

/**
 * Created by IntelliJ IDEA.
 * User: andres
 * Date: 1/14/15
 * Time: 6:01 PM.
 */

namespace SIUToba\rest\seguridad\autenticacion\oauth2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Contracts\Cache\ItemInterface;

class oauth_token_decoder_web extends oauth_token_decoder
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzle_client;

    /**
     * @var tokeninfo_translation_helper
     */
    protected $tokeninfo_translation_helper;

    /**
     * @param \GuzzleHttp\Client $guzzle_client un cliente guzzle inicializado con la URL para pedir los tokens.
     *                                          La URL debe estar completa, lo único que se agrega en el pedido realizado es el parámetro del token
     */
    public function __construct(Client $guzzle_client)
    {
        $this->guzzle_client = $guzzle_client;
    }

    private function get_cache_key_for_token($token)
    {
        return $token;
    }

    /**
     * @param string $token un token oauth
     *
     * @return token_info
     */
    public function decode($token)
    {
        $cache_key = $this->get_cache_key_for_token($token);

        //Intenta recuperar la info del token (cacheada u original)
        $valor_cacheado =  $this->cache->get($cache_key, function (ItemInterface $item) {
            try {
                $item->expireAfter(3600);
                $res = $this->guzzle_client->get("?access_token=$token");
            } catch (ClientException $e) {
                // si falló el cliente por alguna razón (500, 400, etc) se retorna nulo
                return;
            }

            $tokeninfo = $this->tokeninfo_translation_helper->translate_token_info($res->json());
            return $tokeninfo;
        });

        //Si hubo error capturado, liquido la entrada para que no cachee null
        if (null === $valor_cacheado) {
            $this->cache->delete($cache_key);
        }

        return $valor_cacheado;
    }

    /**
     * @param \SIUToba\rest\seguridad\autenticacion\oauth2\tokeninfo_translation_helper $tokeninfo_translation_helper
     */
    public function set_tokeninfo_translation_helper($tokeninfo_translation_helper)
    {
        $this->tokeninfo_translation_helper = $tokeninfo_translation_helper;
    }
}
