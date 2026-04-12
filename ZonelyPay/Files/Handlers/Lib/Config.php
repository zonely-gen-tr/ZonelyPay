<?php

namespace Handlers\Lib;

class Config
{
    public static function load($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("config.yml not found at " . $path);
        }
        $yaml = file_get_contents($path);
        return self::parse($yaml);
    }
    private static function parse($yaml)
    {
        $lines = preg_split('/\r?\n/', $yaml);
        $root = [];
        $stack = [[&$root, -1]];
        foreach ($lines as $raw) {
            $line = rtrim($raw);
            if ($line === '' || preg_match('/^\s*#/', $line)) continue;
            $indent = 0;
            while ($indent < strlen($line) && $line[$indent] === ' ') $indent++;
            $content = substr($line, $indent);
            if (strpos($content, '- ') === 0) {
                $valPart = substr($content, 2);
                $val = self::cast(self::trimQuotes($valPart));
                while (!empty($stack) && $stack[count($stack) - 1][1] >= $indent) array_pop($stack);
                $parent = &$stack[count($stack) - 1][0];
                if (!is_array($parent) || self::isAssoc($parent)) {
                    $parent = [];
                }
                $parent[] = $val;
                $refIndex = count($parent) - 1;
                $stack[] = [&$parent[$refIndex], $indent];
                continue;
            }
            if (preg_match('/^([^:]+):\s*(.*)$/', $content, $m)) {
                $key = trim($m[1]);
                $valStr = $m[2];
                while (!empty($stack) && $stack[count($stack) - 1][1] >= $indent) array_pop($stack);
                $parent = &$stack[count($stack) - 1][0];
                if ($valStr === '') {
                    $parent[$key] = [];
                    $stack[] = [&$parent[$key], $indent];
                } else {
                    $val = self::cast(self::trimQuotes($valStr));
                    $parent[$key] = $val;
                    $stack[] = [&$parent[$key], $indent];
                }
            }
        }
        return $root;
    }
    private static function trimQuotes($s)
    {
        $s = trim($s);
        if ((substr($s, 0, 1) === '"' && substr($s, -1) === '"') || (substr($s, 0, 1) === "'" && substr($s, -1) === "'")) {
            return substr($s, 1, -1);
        }
        return $s;
    }
    private static function cast($v)
    {
        if ($v === 'true') return true;
        if ($v === 'false') return false;
        if ($v === 'null' || $v === '~') return null;
        if (is_numeric($v)) {
            if (strpos($v, '.') !== false) return (float)$v;
            return (int)$v;
        }
        return $v;
    }
    private static function isAssoc($arr)
    {
        if (!is_array($arr)) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
