<?php declare(strict_types=1);

namespace Sunfox\Smartee;

use Latte;

class Helpers extends Latte\Helpers
{
    /**
     * @param string $var
     * @return string
     */
    public static function expandDottedVar($var)
    {
        $varParts = explode('.', $var);
        if (count($varParts) > 1) {
            $var = array_shift($varParts);
            if ($var === '$smarty' && $varParts[0] === 'foreach') {
                $var .= "['foreach'][{$varParts[1]}]->{$varParts[2]}";
            } else {
                foreach ($varParts as $part) {
                    $var .= "['$part']";
                }
            }
        }

        return $var;
    }

    /**
     * @param string $args
     * @return array
     */
    public static function parseMacroNodeArgs($args, array $allowdKeys = null)
    {
        $keys = $allowdKeys ? '[' . implode('|', $allowdKeys) . ']' : '\w';
        preg_match_all('#(' . $keys . '+)=([^\s}]+)#i', $args, $m);

        $params = [];
        foreach ($m[1] as $k => $match) {
            $params[$match] = self::startsWith($m[2][$k], '$')
                ? $m[2][$k] : preg_replace('#^[\'"]*([^\'"]+)[\'\"]*$#', '\\1', $m[2][$k]);
        }

        return $params;
    }
}
