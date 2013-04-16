<?php
/**
 * Gettext MO 文件解析
 * MO文件解析规则参见GNU中gettext的相关说明
 * http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files
 *
 * @auther jekhy(info@jekhy.com)
 */
function analyseMO($mofile)
{
    $mo = file_get_contents($mofile);
    $magicNumber = trim(reset(unpack("h*", substr($mo, 0, 4))));

    // N:big endian, V:little endian
    $endian = $magicNumber == "de120495" ? "V" : "N";
    $header = (object)unpack(implode("/", array(
        "{$endian}revision",
        "{$endian}total",
        "{$endian}originals_lenghts_addr",
        "{$endian}translations_lenghts_addr",
        "{$endian}hash_length","{$endian}hash_addr"
    )), substr($mo, 4, 24));

    $data = array();
    for ($i = 0; $i < $header->total; $i++) {
        $o = (object)unpack("{$endian}len/{$endian}offset",
            substr($mo, $header->originals_lenghts_addr + $i * 8, 8));
        $t = (object)unpack("{$endian}len/{$endian}offset",
            substr($mo, $header->translations_lenghts_addr + $i * 8, 8));
        $data[$i] = (object)array(
            "original" => substr($mo, $o->offset, $o->len),
            "translation" => substr($mo, $t->offset, $t->len),
        );
    }

    $result = (object)array(
        "header" => $header,
        "data" => $data,
    );
    return $result;
}
