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

$C['google_apl_client_libraries_path'] = __DIR__ . '/../function/google-api-php-client/vendor/autoload.php';

$C["allowsapi"] = array("cli");

$G["db"] = new PDO ('mysql:host='.$C["DBhost"].';dbname='.$C["DBname"].';charset=utf8', $C["DBuser"], $C["DBpass"]);

$M["nottext"] = "僅接受文字訊息";
$M["notcommand"] = "無法辨識的訊息\n".
	"本粉專由機器人自動運作\n".
	"啟用訊息通知輸入 /start\n".
	"顯示所有命令輸入 /help";
$M["/start"] = "已啟用訊息通知\n".
	"欲取消請輸入 /stop";
$M["/start_too_many_arg"] = "參數個數錯誤\n".
	"此指令不需要參數";
$M["/stop"] = "已停用訊息通知\n".
	"欲重新啟用請輸入 /start";
$M["/stop_too_many_arg"] = "參數個數錯誤\n".
	"此指令不需要參數";
$M["/help"] = "可用命令\n".
	"/start 啟用訊息通知\n".
	"/stop 停用訊息通知\n".
	"/help 顯示所有命令";
$M["fail"] = "指令失敗";
$M["wrongcommand"] = "無法辨識命令\n".
	"輸入 /help 取得可用命令";
