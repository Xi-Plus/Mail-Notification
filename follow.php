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
			$sid = $messaging['sender']['id'];
			$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}user` WHERE `sid` = :sid");
			$sth->bindValue(":sid", $sid);
			$sth->execute();
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			if ($row === false) {
				GetTmid();
				$mmid = "m_".$messaging['message']['mid'];
				$res = cURL($C['FBAPI'].$mmid."?fields=from&access_token=".$C['FBpagetoken']);
				$res = json_decode($res, true);
				$uid = $res["from"]["id"];
				$sthsid = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `sid` = :sid WHERE `uid` = :uid");
				$sthsid->bindValue(":sid", $sid);
				$sthsid->bindValue(":uid", $uid);
				$sthsid->execute();

				$sth->execute();
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				if ($row === false) {
					WriteLog("[follow][error][uid404] sid=".$sid." uid=".$uid);
					continue;
				} else {
					WriteLog("[follow][info][newuser] sid=".$sid." uid=".$uid);
				}
			}
			$tmid = $row["tmid"];
			$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `lastread` = :lastread WHERE `tmid` = :tmid");
			$sth->bindValue(":lastread", "2038-01-19 03:04:17");
			$sth->bindValue(":tmid", $tmid);
			$res = $sth->execute();
			if ($res === false) {
				WriteLog("[follow][error][updlr] tmid=".$tmid);
			}
			if (isset($messaging['read'])) {
				continue;
			}
			if (!isset($messaging['message']['text'])) {
				SendMessage($tmid, "僅接受文字訊息");
				continue;
			}
			$msg = $messaging['message']['text'];
			if ($msg[0] !== "/") {
				SendMessage($tmid, "無法辨識的訊息\n".
					"本粉專由機器人自動運作\n".
					"啟用訊息通知輸入 /start\n".
					"顯示所有命令輸入 /help");
				continue;
			}
			$msg = str_replace("\n", " ", $msg);
			$msg = preg_replace("/\s+/", " ", $msg);
			$cmd = explode(" ", $msg);
			switch ($cmd[0]) {
				case '/start':
					if (isset($cmd[1])) {
						SendMessage($tmid, "參數個數錯誤\n".
							"此指令不需要參數");
						continue;
					}
					$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `fbmessage` = '1' WHERE `tmid` = :tmid");
					$sth->bindValue(":tmid", $tmid);
					$res = $sth->execute();
					if ($res) {
						SendMessage($tmid, "已啟用訊息通知\n".
							"欲取消請輸入 /stop");
					} else {
						WriteLog("[follow][error][start][upduse] uid=".$uid);
						SendMessage($tmid, "指令失敗");
					}
					break;
				
				case '/stop':
					if (isset($cmd[1])) {
						SendMessage($tmid, "參數個數錯誤\n".
							"此指令不需要參數");
						continue;
					}
					$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `fbmessage` = '0' WHERE `tmid` = :tmid");
					$sth->bindValue(":tmid", $tmid);
					$res = $sth->execute();
					if ($res) {
						SendMessage($tmid, "已停用訊息通知\n".
							"欲重新啟用請輸入 /start");
					} else {
						WriteLog("[follow][error][stop][upduse] uid=".$uid);
						SendMessage($tmid, "指令失敗");
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
						if ($fromemail === false || MailFilter($fromemail) == 0) {
							$msg = "#".$news["idx"]."\n".
								"此來自 ".$news["from"]." 的郵件已被過濾器自動攔截，暫時無法查看，如果您認為這有誤，請回報";
							SendMessage($tmid, $msg);
						} else if (MailFilter($fromemail) == -1) {
							$msg = "#".$news["idx"]."\n".
								"此郵件已被封鎖，因此無法查看，如果您認為這有誤，請回報";
							SendMessage($tmid, $msg);
						} else {
							$msg = "#".$news["idx"]."\n".
								$news["from"]."\n".
								$news["subject"]."\n".
								"----------------------------------------\n".
								$news["content"];
							SendMessage($tmid, $msg);
						}
					} else {
						WriteLog("[follow][error][start][selnew] uid=".$uid);
						SendMessage($tmid, "指令失敗");
					}
					break;

				case '/search':
					if (!isset($cmd[1])) {
						SendMessage($tmid, "參數個數錯誤\n".
							"此指令必須給出一個參數為搜尋的詞語");
						continue;
					}
					if (isset($cmd[2])) {
						SendMessage($tmid, "參數個數錯誤\n".
							"此指令必須給出一個參數為通知的編號\n".
							"不可包含空白");
						continue;
					}
					$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `subject` REGEXP :subject ORDER BY `idx` DESC LIMIT ".$C['SearchLimit']);
					$sth->bindValue(":subject", $cmd[1]);
					$res = $sth->execute();
					$newss = $sth->fetchAll(PDO::FETCH_ASSOC);
					if ($res) {
						if ($newss === false) {
							SendMessage($tmid, "找不到任何結果");
							continue;
						}
						$msg = "";
						require(__DIR__.'/function/mailfilter.php');
						foreach (array_reverse($newss) as $news) {
							if (preg_match("/^(.+) <(.+)>$/", $news["from"], $m)) {
								$fromemail = $m[2];
							} else {
								$fromemail = false;
							}
							if ($fromemail === false || MailFilter($fromemail) == 0) {
								$msg .= "#".$news["idx"]."\n".
									"此來自 ".$news["from"]." 的郵件已被過濾器自動攔截，暫時無法查看，如果您認為這有誤，請回報\n";
							} else if (MailFilter($fromemail) == -1) {
								$msg .= "#".$news["idx"]."\n".
									"此郵件已被封鎖，因此無法查看，如果您認為這有誤，請回報\n";
							} else {
								$msg .= "#".$news["idx"]." ".$news["subject"]."\n";
							}
						}
						if ($msg === "") {
							SendMessage($tmid, "找不到任何結果");
							continue;
						}
						$msg = "搜尋：".$cmd[1]."\n".
							$msg;
						SendMessage($tmid, $msg);
					} else {
						WriteLog("[follow][error][start][selnew] uid=".$uid);
						SendMessage($tmid, "指令失敗");
					}
					break;

				case '/help':
					if (isset($cmd[2])) {
						$msg = "參數過多\n".
							"必須給出一個參數為指令的名稱";
					} else if (isset($cmd[1])) {
						switch ($cmd[1]) {
							case 'start':
								$msg = "/start 啟用訊息通知";
								break;
							
							case 'stop':
								$msg = "/start 停用訊息通知";
								break;
							
							case 'show':
								$msg = "/show 顯示最後一封郵件內容\n".
									 "/show [編號] 顯示指定編號郵件內容\n";
								break;
							
							case 'search':
								$msg = "/search [文字] 搜尋郵件標題\n".
									"此命令最多顯示".$C['SearchLimit']."筆結果\n".
									"此命令支援正規表達式";
								break;
							
							case 'help':
								$msg = "/help 顯示所有命令";
								break;
							
							default:
								$msg = "查無此指令";
								break;
						}
					} else {
						$msg = "可用命令\n".
						"/start 啟用訊息通知\n".
						"/stop 停用訊息通知\n".
						"/show 顯示郵件內容\n".
						"/search 搜尋郵件標題\n".
						"/help 顯示所有命令\n\n".
						"/help [命令] 顯示命令的詳細用法";
					}
					SendMessage($tmid, $msg);
					break;
				
				default:
					SendMessage($tmid, "無法辨識命令\n".
						"輸入 /help 取得可用命令");
					break;
			}
		}
	}
}
