<?php

namespace Coderstm\Traits;

trait JsonCompressible
{
    protected function uncompress(string $value)
    {
        try {
            return json_decode(gzuncompress($value), true);
        } catch (\Throwable $e) {
            return json_decode($value, true);
        }
    }
}
