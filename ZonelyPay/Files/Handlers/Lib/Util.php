<?php
namespace Handlers\Lib;
class Util {
    public static function jsonResponse($arr, $code=200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        exit;
    }
    public static function readJson($path) {
        if (!file_exists($path)) return null;
        $raw = file_get_contents($path);
        $j = json_decode($raw, true);
        return is_array($j) ? $j : null;
    }
    public static function writeJson($path, $data) {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }
    public static function hmac($payload, $secret) {
        return hash_hmac('sha256', $payload, $secret);
    }
    public static function normUrl($u) {
        $u = trim((string)$u);
        if ($u === '') return '';
        if (!preg_match('~^https?://~i', $u)) $u = 'https://' . $u;
        return $u;
    }
    public static function maskPan($pan) {
        $n = preg_replace('/\D+/', '', (string)$pan);
        if (strlen($n) < 10) return '****';
        return substr($n,0,6) . str_repeat('*', max(0, strlen($n)-10)) . substr($n,-4);
    }
    public static function money($v) {
        return number_format((float)$v, 2, '.', '');
    }
}
