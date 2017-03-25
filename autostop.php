<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

require(__DIR__.'/function/log.php');
require(__DIR__.'/function/curl.php');
require(__DIR__.'/function/sendmessage.php');

$time = date("Y-m-d H:i:s", time()-$C['UnreadLimit']);
$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}user` WHERE `fbmessage` = 1 AND `lastread` < :lastread");
$sth->bindValue(":lastread", $time);
$sth->execute();
$users = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $user) {
	SendMessage($user["tmid"], "系統偵測到您已經有{$C['UnreadLimitText']}沒有讀取訊息，已自動將您的訂閱取消\n".
		"欲重新接收通知輸入 /start");
}

$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `fbmessage` = 0 WHERE `lastread` < :lastread");
$sth->bindValue(":lastread", $time);
$sth->execute();
