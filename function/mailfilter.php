<?php
$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}filter` ORDER BY `no` ASC");
$sth->execute();
$C["mailfilter"] = $sth->fetchAll(PDO::FETCH_ASSOC);
function MailFilter($mail) {
	global $C;
	foreach ($C["mailfilter"] as $mailfilter) {
		if (preg_match("/".$mailfilter["regex"]."/", $mail)) {
			return $mailfilter["type"];
		}
	}
	return 0;
}
function AutoBlacklist($mail, $comment) {
	global $C, $G;
	$sth = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}filter` (`no`, `regex`, `type`, `comment`) VALUES ('2', :regex, '-1', :comment)");
	$sth->bindValue(":regex", "^".$mail."$");
	$sth->bindValue(":comment", $comment);
	$sth->execute();
}
