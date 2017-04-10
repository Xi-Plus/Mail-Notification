<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

require(__DIR__.'/function/log.php');
require($C["google_apl_client_libraries_path"]);
set_time_limit(600);

$start = microtime(true);
$time = date("Y-m-d H:i:s");

define('APPLICATION_NAME', 'Gmail API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__.'/data/credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/data/client_secret.json');
define('SCOPES', implode(' ', array(
	Google_Service_Gmail::GMAIL_READONLY)
));

function getClient() {
	$client = new Google_Client();
	$client->setApplicationName(APPLICATION_NAME);
	$client->setScopes(SCOPES);
	$client->setAuthConfig(CLIENT_SECRET_PATH);
	$client->setAccessType('offline');

	$credentialsPath = CREDENTIALS_PATH;
	if (file_exists($credentialsPath)) {
		$accessToken = json_decode(file_get_contents($credentialsPath), true);
	} else {
		$authUrl = $client->createAuthUrl();
		printf("Open the following link in your browser:\n%s\n", $authUrl);
		print 'Enter verification code: ';
		$authCode = trim(fgets(STDIN));

		$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

		if(!file_exists(dirname($credentialsPath))) {
			mkdir(dirname($credentialsPath), 0700, true);
		}
		file_put_contents($credentialsPath, json_encode($accessToken));
		printf("Credentials saved to %s\n", $credentialsPath);
	}
	$client->setAccessToken($accessToken);

	if ($client->isAccessTokenExpired()) {
		$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
		file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
	}
	return $client;
}

$client = getClient();
$service = new Google_Service_Gmail($client);

function getMessage($service, $messageId) {
	try {
		$message = $service->users_messages->get("me", $messageId);
		return $message;
	} catch (Exception $e) {
		print 'An error occurred: ' . $e->getMessage();
	}
}
function listMessages($service, $last_id) {
	$pageToken = null;
	$messages = array();
	$opt_param = array();
	do {
		try {
			if ($pageToken) {
				$opt_param['pageToken'] = $pageToken;
			}
			$opt_param['maxResults'] = 10;
			$messagesResponse = $service->users_messages->listUsersMessages("me", $opt_param);
			if ($messagesResponse->getMessages()) {
				$pageToken = $messagesResponse->getNextPageToken();
				foreach ($messagesResponse->getMessages() as $message) {
					if ($message["id"] == $last_id) {
						break 2;
					}
					WriteLog("[fetch][info] new id=".$message["id"]);
					$messages []= getMessage($service, $message["id"]);
				}
			}
		} catch (Exception $e) {
			print 'An error occurred: ' . $e->getMessage();
		}
	} while ($pageToken);

	return $messages;
}
function base64url_decode($data) {
	return base64_decode(str_replace(array('-', '_'), array('+', '/'), $data));
}
function dfspart($part, $ans) {
	if (isset($part["parts"])) {
		foreach ($part["parts"] as $subpart) {
			$ans = dfspart($subpart, $ans);
		}
	} else {
		if (!isset($ans[$part["mimeType"]])) {
			$ans[$part["mimeType"]] = array();
		}
		if (isset($part["body"]["data"])) {
			$part["body"]["data"] = base64url_decode($part["body"]["data"]);
		}
		$ans[$part["mimeType"]] []= $part["body"];
	}
	return $ans;
}

$sth = $G["db"]->prepare("SELECT `messageid` FROM `{$C['DBTBprefix']}news` ORDER BY `idx` DESC LIMIT 1");
$res = $sth->execute();
$row = $sth->fetch(PDO::FETCH_ASSOC);
if ($row === false) {
	$last_id = "";
} else {
	$last_id = $row["messageid"];
}
$messages = listMessages($service, $last_id);
if (count($messages) > 0) {
	WriteLog("[fetch][info] get ".count($messages));
	foreach (array_reverse($messages) as $message) {
		$subject = "";
		$from = "";
		$fromemail = "";
		foreach ($message["modelData"]["payload"]["headers"] as $header) {
			if ($header["name"] == "Subject") {
				$subject = $header["value"];
			} else if ($header["name"] == "From") {
				$from = $header["value"];
			}
		}
		$content = "";
		$datas = dfspart($message["modelData"]["payload"], array());
		if (isset($datas["text/html"])) {
			$content = $datas["text/html"][0]["data"];
			$content = str_replace("</p>", "</p>\n\n", $content);
			$content = str_replace("<br>", "\n", $content);
			$content = htmlspecialchars_decode($content);
			$content = strip_tags($content);
		} else if (isset($datas["text/plain"])) {
			$content = $datas["text/plain"][0]["data"];
		}
		$content = str_replace("\r", "", $content);
		$content = str_replace(array("\xC2\xA0", "\t", "　", "&nbsp;"), " ", $content);
		$content = preg_replace("/ +/", " ", $content);
		$content = preg_replace("/^ $/m", "", $content);
		$content = preg_replace("/\n{3,}/", "\n\n", $content);
		$content = preg_replace("/^\n+/", "", $content);
		$content = preg_replace("/\n+$/", "", $content);

		$sth = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}news` (`messageid`, `from`, `subject`, `content`, `time`, `hash`) VALUES (:messageid, :from, :subject, :content, :time, :hash)");
		$hash = md5(serialize(array("messageid"=>$message["id"], "from"=>$from, "subject"=>$subject, "content"=>$content, "time"=>$time)));
		$sth->bindValue(":messageid", $message["id"]);
		$sth->bindValue(":from", $from);
		$sth->bindValue(":subject", $subject);
		$sth->bindValue(":content", $content);
		$sth->bindValue(":time", $time);
		$sth->bindValue(":hash", $hash);
		$res = $sth->execute();
		if ($res === false) {
			WriteLog("[fetch][error][insnew] messageid=".$message["id"]." msg=".json_encode($sth->errorInfo()));
		}
	}
}
WriteLog("[fetch][info] runtime=".round((microtime(true)-$start), 6));
