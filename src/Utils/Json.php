<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Json
{
    public static function parseRequest(Request $request)
    {
        $content = $request->getContent();

        if (!empty($content) && '{' === substr($content, 0, 1) && '}' === substr($content, -1)) {
            try {
                return self::decode($request->getContent());
            } catch (\Throwable $e) {
                throw new BadRequestHttpException('Invalid JSON payload.');
            }
        }

        return $request->request->all();
    }

    public static function decode(string $json)
    {
        if (!\defined('JSON_THROW_ON_ERROR')) {
            $result = @json_decode($json, true, 512);

            if (\JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(sprintf('Error while decoding JSON: "%s".', json_last_error_msg()));
            }

            return $result;
        }

        try {
            return json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Error while decoding JSON: "%s".', $e->getMessage()), $e->getCode(), $e);
        }
    }

    public static function encode($value, $options = 0): string
    {
        if (!\defined('JSON_THROW_ON_ERROR')) {
            $result = @json_encode($value, $options);

            if (\JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(sprintf('Error while encoding JSON: "%s".', json_last_error_msg()));
            }

            return $result;
        }

        return json_encode($value, \JSON_THROW_ON_ERROR | $options);
    }
}
