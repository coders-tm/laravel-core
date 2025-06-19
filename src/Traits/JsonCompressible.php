<?php

namespace Coderstm\Traits;

trait JsonCompressible
{
    /**
     * Decompress and decode JSON data.
     *
     * @param string $value
     * @return mixed
     */
    protected function uncompress(string $value)
    {
        try {
            return json_decode(gzuncompress($value), true);
        } catch (\Exception $e) {
            // If the data is not compressed, directly decode it
            return json_decode($value, true);
        }
    }
}
