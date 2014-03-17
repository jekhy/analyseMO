<?php
/**
 * Gettext MO 文件解析
 * MO文件解析规则参见GNU中gettext的相关说明
 * http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files
 *
 * @auther jekhy(info@jekhy.com)
 */

/**
 * 分析MO文件 
 *
 * @param string $moFile MO 文件路径
 *
 * @return StdClass
 */
function analyseMO($mofile)
{
    $mo = file_get_contents($mofile);
    $magicNumbers = unpack("h*", substr($mo, 0, 4));
    $magicNumber = trim(reset($magicNumbers));

    // N:big endian, V:little endian
    $endian = $magicNumber == "de120495" ? "N" : "V";
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

/**
 * 根据MO文件生成PO文件
 *
 * @param string $moPath MO文件路径
 * @param string $poPath PO文件路径
 *
 * @return void
 */
function mo2po($moPath, $poPath)
{
    $r = analyseMO('./common.mo');
    $output = "";
    foreach ($r->data as $i => $row) {
        $id  = $row->original;
        $str = $row->translation;
        if ($i == 0) {
            $str = '';
        }
        $output .= "msgid \"{$id}\"\n";
        $output .= "msgstr \"{$str}\"\n";
        if ($i == 0) {
            $headInfo = array_map("addslashes", explode("\n", trim($row->translation)));
            $output .= '"' . implode("\\n\"\n\"", $headInfo) . "\\n\"\n";
        }
        $output .= "\n";
    }
    file_put_contents($poPath, $output);
}

// usage:
// mo2po('input.mo', 'output.po');
