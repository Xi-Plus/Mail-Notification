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
