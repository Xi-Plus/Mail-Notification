<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

require(__DIR__.'/function/log.php');
require($C["google_apl_client_libraries_path"]);
set_time_limit(600);

WriteLog("[fetch][info] start");
$start = microtime(true);
$time = date("Y-m-d H:i:s");

define('APPLICATION_NAME', 'Gmail API PHP Quickstart');
define('CREDENTIALS_PATH', '~/.credentials/gmail-php-quickstart.json');
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

	$credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
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

function expandHomeDirectory($path) {
	$homeDirectory = getenv('HOME');
	if (empty($homeDirectory)) {
		$homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
	}
	return str_replace('~', realpath($homeDirectory), $path);
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
					$messages []= getMessage($service, $message["id"]);
					break 2;
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

$last_id = file_get_contents(__DIR__."/data/last_id.txt");
$messages = listMessages($service, $last_id);
if (count($messages) > 0) {
	file_put_contents(__DIR__."/data/last_id.txt", $messages[0]["id"]);
	$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}user` WHERE `fbmessage` = 1");
	$sth->execute();
	$users = $sth->fetchAll(PDO::FETCH_ASSOC);
	$sthmsg = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}msgqueue` (`tmid`, `message`, `time`, `hash`) VALUES (:tmid, :message, :time, :hash)");
	foreach ($messages as $message) {
		$subject = "";
		foreach ($message["modelData"]["payload"]["headers"] as $header) {
			if ($header["name"] == "Subject") {
				$subject = $header["value"];
				break;
			}
		}
		$content = "";
		foreach ($message["modelData"]["payload"]["parts"] as $part) {
			if ($part["mimeType"] == "text/plain") {
				$content = $part["body"]["data"];
				break;
			}
		}
		if ($content == "") {
			$content = $message["modelData"]["payload"]["parts"][0]["body"]["data"];
		}
		$content = base64url_decode($content);
		$content = preg_replace("/^\s+$/m", "", $content);
		$content = preg_replace("/\n{3,}/", "\n\n", $content);
		$content = preg_replace("/^[\t 　]+/m", "", $content);
		$content = preg_replace("/\n+$/", "", $content);
		$msg = $subject."\n".
			"----------------------------------------\n".
			$content;
		foreach ($users as $user) {
			$hash = md5(json_encode(array("tmid"=>$user["tmid"], "message"=>$msg, "time"=>$time)));
			$sthmsg->bindValue(":tmid", $user["tmid"]);
			$sthmsg->bindValue(":message", $msg);
			$sthmsg->bindValue(":time", $time);
			$sthmsg->bindValue(":hash", $hash);
			$res = $sthmsg->execute();
			if ($res === false) {
				WriteLog("[fbmsg][error][insque] tmid=".$user["tmid"]." msg=".$content);
			}
		}
		echo $msg."\n\n\n";
	}
}
exec("php fbmessage.php > /dev/null 2>&1 &");
WriteLog("[fetch][info] runtime=".round((microtime(true)-$start), 6));
