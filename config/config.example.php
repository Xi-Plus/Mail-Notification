<?php

$C['FBpageid'] = 'page_id';
$C['FBpagetoken'] = 'page_token';
$C['FBWHtoken'] = 'Webhooks_token';
$C['FBAPI'] = 'https://graph.facebook.com/v2.8/';

$C["DBhost"] = 'localhost';
$C['DBname'] = 'dbname';
$C['DBuser'] = 'user';
$C['DBpass'] = 'pass';
$C['DBTBprefix'] = 'tnfsh_notification_';

$C['UnreadLimit'] = 86400;
$C['UnreadLimitText'] = "24小時";

$C['LogKeep'] = 86400*7;

$C['google_apl_client_libraries_path'] = __DIR__ . '/../function/google-api-php-client/vendor/autoload.php';

$C["allowsapi"] = array("cli");

$G["db"] = new PDO ('mysql:host='.$C["DBhost"].';dbname='.$C["DBname"].';charset=utf8', $C["DBuser"], $C["DBpass"]);
