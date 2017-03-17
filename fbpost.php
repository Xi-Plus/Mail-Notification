<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

require(__DIR__.'/function/log.php');
require(__DIR__.'/function/mailfilter.php');

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `fbpost` = 0 ORDER BY `time` DESC");
$sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);

if (count($row) == 0) {
	exit("No new\n");
}

foreach ($row as $news) {
	if (preg_match("/^(.+) <(.+)>$/", $news["from"], $m)) {
		$fromemail = $m[2];
	} else {
		$fromemail = false;
	}
	if ($fromemail === false || !MailFilter($fromemail)) {
		$msg = false;
	} else {
		$msg = "#".$news["idx"]."\n".
			$news["from"]."\n".
			$news["subject"]."\n\n".
			"向粉專傳送訊息 /show ".$news["idx"]." 查看郵件內容";
	}

	if ($msg !== false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v2.8/me/feed");
		curl_setopt($ch, CURLOPT_POST, true);
		$post = array(
			"message" => $msg,
			"access_token" => $C['FBpagetoken']
		);
		curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($post));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		$res = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($res, true);
	}
	
	if ($msg !== false && isset($res["error"])) {
		WriteLog("[fbpos][error] res=".json_encode($res));
	} else {
		$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}news` SET `fbpost` = '1' WHERE `hash` = :hash");
		foreach ($row as $temp) {
			$sth->bindValue(":hash", $temp["hash"]);
			$sth->execute();
		}
	}
}
