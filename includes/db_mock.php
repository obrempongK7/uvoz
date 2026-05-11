<?php
class DB {
    public static function query($sql, $params = []) { return []; }
    public static function first($sql, $params = []) {
        if (str_contains($sql, "FROM users")) return ["id"=>1, "username"=>"testuser", "status"=>"active"];
        return null;
    }
    public static function count($t, $w='1', $p=[]) { return 0; }
    public static function exec($s, $p=[]) { return 0; }
    public static function conn() { return new class { public function lastInsertId() { return 1; } }; }
}
function getUserWallet($id) { return ["points_balance"=>1240]; }
function getUserPlan($id) { return ["name"=>"Premium"]; }
function getPlatformSettings() { return ["app_name"=>"Uvoz"]; }
function auth() { return ["id"=>1, "username"=>"testuser"]; }
