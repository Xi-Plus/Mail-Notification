<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

require(__DIR__.'/function/log.php');
require(__DIR__.'/function/curl.php');
require(__DIR__.'/function/sendmessage.php');
require(__DIR__.'/function/mailfilter.php');

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `fbmessage` = 0 ORDER BY `time` ASC");
$sth->execute();
$newss = $sth->fetchAll(PDO::FETCH_ASSOC);
$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}user` WHERE `fbmessage` = 1");
$sth->execute();
$users = $sth->fetchAll(PDO::FETCH_ASSOC);

$sthmsg = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}msgqueue` (`tmid`, `message`, `time`, `hash`) VALUES (:tmid, :message, :time, :hash)");
$sthok = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}news` SET `fbmessage` = '1' WHERE `hash` = :hash");
foreach ($newss as $news) {
	if (preg_match("/^(.+) <(.+)>$/", $news["from"], $m)) {
		$fromemail = $m[2];
	} else {
		$fromemail = false;
	}
	if ($fromemail === false || !MailFilter($fromemail)) {
		$msg = "有一封來自 ".$news["from"]." 的郵件已被過濾器攔截，如果您認為這有誤，請回報";
	} else {
		$msg = "#".$news["idx"]."\n".
			$news["from"]."\n".
			$news["subject"];
	}

	foreach ($users as $user) {
		$hash = md5(json_encode(array("tmid"=>$user["tmid"], "message"=>$msg, "time"=>$news["time"])));
		$sthmsg->bindValue(":tmid", $user["tmid"]);
		$sthmsg->bindValue(":message", $msg);
		$sthmsg->bindValue(":time", $news["time"]);
		$sthmsg->bindValue(":hash", $hash);
		$res = $sthmsg->execute();
		if ($res === false) {
			WriteLog("[fbmsg][error][insque] tmid=".$user["tmid"]." msg=".$msg);
		}
	}
	$sthok->bindValue(":hash", $news["hash"]);
	$res = $sthok->execute();
	if ($res === false) {
		WriteLog("[fbmsg][error][updnew] hash=".$news["hash"]);
	}
}

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}msgqueue` ORDER BY `time` ASC");
$sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);
$sth = $G["db"]->prepare("DELETE FROM `{$C['DBTBprefix']}msgqueue` WHERE `hash` = :hash");
foreach ($row as $msg) {
	$res = SendMessage($msg["tmid"], $msg["message"]);
	if ($res) {
		$sth->bindValue(":hash", $msg["hash"]);
		$res = $sth->execute();
		if ($res === false) {
			WriteLog("[fbmsg][error][delque] hash=".$msg["hash"]);
		}
	}
}
