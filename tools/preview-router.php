<?php
/** Router for the loopback-only PHP development server. */
declare(strict_types=1);
$root='/tmp/isn-cinematic-preview-20261106';
$path=rawurldecode((string)parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH));
if (preg_match('~(?:^|/)(?:\.|wp-config\.php|wp-cron\.php|database)(?:/|$)|\.sqlite(?:$|[.-])~i',$path)) {http_response_code(404);exit;}
$file=realpath($root.$path);
if($path!=='/' && $file && str_starts_with($file,$root.'/') && is_file($file)){return false;}
$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['SCRIPT_FILENAME']=$root.'/index.php';
require $root.'/index.php';
