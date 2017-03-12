<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

date_default_timezone_set("Asia/Taipei");
require(__DIR__.'/function/curl.php');
require(__DIR__.'/function/log.php');
require(__DIR__.'/function/sendmessage.php');

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}input` ORDER BY `time` ASC");
$res = $sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($row as $data) {
	$sth = $G["db"]->prepare("DELETE FROM `{$C['DBTBprefix']}input` WHERE `hash` = :hash");
	$sth->bindValue(":hash", $data["hash"]);
	$res = $sth->execute();
}
function GetTmid() {
	global $C, $G;
	$res = cURL($C['FBAPI']."me/conversations?fields=participants,updated_time&access_token=".$C['FBpagetoken']);
	$updated_time = file_get_contents("data/updated_time.txt");
	$newesttime = $updated_time;
	while (true) {
		if ($res === false) {
			WriteLog("[follow][error][getuid]");
			break;
		}
		$res = json_decode($res, true);
		if (count($res["data"]) == 0) {
			break;
		}
		foreach ($res["data"] as $data) {
			if ($data["updated_time"] <= $updated_time) {
				break 2;
			}
			if ($data["updated_time"] > $newesttime) {
				$newesttime = $data["updated_time"];
			}
			foreach ($data["participants"]["data"] as $participants) {
				if ($participants["id"] != $C['FBpageid']) {
					$sth = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}user` (`uid`, `tmid`, `name`) VALUES (:uid, :tmid, :name)");
					$sth->bindValue(":uid", $participants["id"]);
					$sth->bindValue(":tmid", $data["id"]);
					$sth->bindValue(":name", $participants["name"]);
					$res = $sth->execute();
					break;
				}
			}
		}
		$res = cURL($res["paging"]["next"]);
	}
	file_put_contents("data/updated_time.txt", $newesttime);
}
foreach ($row as $data) {
	$input = json_decode($data["input"], true);
	foreach ($input['entry'] as $entry) {
		foreach ($entry['messaging'] as $messaging) {
			$mmid = "m_".$messaging['message']['mid'];
			$res = cURL($C['FBAPI'].$mmid."?fields=from&access_token=".$C['FBpagetoken']);
			$res = json_decode($res, true);
			$uid = $res["from"]["id"];

			$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}user` WHERE `uid` = :uid");
			$sth->bindValue(":uid", $uid);
			$sth->execute();
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			if ($row === false) {
				GetTmid();
				$sth->execute();
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				if ($row === false) {
					WriteLog("[follow][error][uid404] uid=".$uid);
					continue;
				} else {
					WriteLog("[follow][info][newuser] uid=".$uid);
				}
			}
			$tmid = $row["tmid"];
			if (!isset($messaging['message']['text'])) {
				SendMessage($tmid, $M["nottext"]);
				continue;
			}
			$msg = $messaging['message']['text'];
			if ($msg[0] !== "/") {
				SendMessage($tmid, $M["notcommand"]);
				continue;
			}
			$msg = str_replace("\n", " ", $msg);
			$msg = preg_replace("/\s+/", " ", $msg);
			$cmd = explode(" ", $msg);
			switch ($cmd[0]) {
				case '/start':
					if (isset($cmd[1])) {
						SendMessage($tmid, $M["/start_too_many_arg"]);
						continue;
					}
					$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `fbmessage` = '1' WHERE `tmid` = :tmid");
					$sth->bindValue(":tmid", $tmid);
					$res = $sth->execute();
					if ($res) {
						SendMessage($tmid, $M["/start"]);
					} else {
						WriteLog("[follow][error][start][upduse] uid=".$uid);
						SendMessage($tmid, $M["fail"]);
					}
					break;
				
				case '/stop':
					if (isset($cmd[1])) {
						SendMessage($tmid, $M["/stop_too_many_arg"]);
						continue;
					}
					$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `fbmessage` = '0' WHERE `tmid` = :tmid");
					$sth->bindValue(":tmid", $tmid);
					$res = $sth->execute();
					if ($res) {
						SendMessage($tmid, $M["/stop"]);
					} else {
						WriteLog("[follow][error][stop][upduse] uid=".$uid);
						SendMessage($tmid, $M["fail"]);
					}
					break;

				case '/show':
					if (isset($cmd[1])) {
						if (preg_match("/^\d+$/", $cmd[1]) == 0) {
							SendMessage($tmid, "第1個參數錯誤\n".
								"必須是一個正整數");
							continue;
						}
						$n = (int)$cmd[1];
						if ($n == 0) {
							SendMessage($tmid, "第1個參數錯誤\n".
								"必須是一個正整數");
							continue;
						}
						if (isset($cmd[2])) {
							SendMessage($tmid, "參數個數錯誤\n".
								"此指令必須給出一個參數為通知的編號");
							continue;
						}
						$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `idx` = :idx");
						$sth->bindValue(":idx", $n);
					} else {
						$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` ORDER BY `idx` DESC LIMIT 1");
					}
					$res = $sth->execute();
					$news = $sth->fetch(PDO::FETCH_ASSOC);
					if ($res) {
						if ($news === false) {
							SendMessage($tmid, "找不到此編號");
							continue;
						}
						if (preg_match("/^(.+) <(.+)>$/", $news["from"], $m)) {
							$fromemail = $m[2];
						} else {
							$fromemail = false;
						}
						require(__DIR__.'/function/mailfilter.php');
						if ($fromemail === false || !MailFilter($fromemail)) {
							$msg = "#".$news["idx"]."\n".
								"此來自 ".$news["from"]." 的郵件已被過濾器攔截，因此無法查看，如果您認為這有誤，請回報";
							SendMessage($tmid, $msg);
						} else {
							$msg = "#".$news["idx"]."\n".
								$news["from"]."\n".
								$news["subject"]."\n".
								"----------------------------------------\n".
								$news["content"]."\n";
							SendMessage($tmid, $msg);
						}
					} else {
						WriteLog("[follow][error][start][selnew] uid=".$uid);
						SendMessage($tmid, "指令失敗");
					}
					break;

				case '/help':
					SendMessage($tmid, $M["/help"]);
					break;
				
				default:
					SendMessage($tmid, $M["wrongcommand"]);
					break;
			}
		}
	}
}
