<?php

namespace App\BL\Util;

class StringUtil
{
    /**
     * Shaves all diacritics and converts to lower case
     * @param string $str
     * @return string formated string
     */
    public static function shave(string $str): string
    {
        $transliterator = \Transliterator::createFromRules(
            ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;',
            \Transliterator::FORWARD
        );
        return $transliterator->transliterate($str);
    }
}
