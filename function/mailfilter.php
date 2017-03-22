<?php
function MailFilter($mail) {
	$whitelist = array(
		"bcoffice01@kuas.edu.tw", //祕書室公關組
		"bdoffice01@kuas.edu.tw", //秘書室校務企劃組
		"caoffice01@kuas.edu.tw", //教務處
		"ccoffice02@kuas.edu.tw", //綜合教務組
		"cdoffice02@kuas.edu.tw", //教學發展中心
		"cgoffice01@kuas.edu.tw", //南區區域教學資源中心
		"dboffice01@kuas.edu.tw", //學務處諮商輔導中心
		"dcoffice01@kuas.edu.tw", //學務處生輔組
		"ddoffice01@kuas.edu.tw", //課外活動組
		"deoffice02@kuas.edu.tw", //衛生保健組
		"dfoffice01@kuas.edu.tw", //軍訓室
		"eboffice02@kuas.edu.tw", //總務處事務組
		"ecoffice01@kuas.edu.tw", //總務處出納組
		"edoffice01@kuas.edu.tw", //總務處營繕組
		"eeoffice01@kuas.edu.tw", //總務處保管組
		"efoffice01@kuas.edu.tw", //總務處事務組
		"faoffice01@kuas.edu.tw", //研究發展處
		"fboffice01@kuas.edu.tw", //研究發展處學術服務組
		"fcoffice01@kuas.edu.tw", //研發處技術合作組
		"ieoffice02@kuas.edu.tw", //推廣教育中心ie02
		"jcoffice01@kuas.edu.tw", //進修學院學務組
		"jktuhs@kuas.edu.tw",     //人文與社會科學學刊
		"khoffice01@kuas.edu.tw", //語文中心
		"kuassa@kuas.edu.tw",     //學生會
		"laoffice01@kuas.edu.tw", //環安衛中心 環保組
		"oboffice01@kuas.edu.tw", //高雄應用科技大學圖書館
		"pboffice01@kuas.edu.tw", //計網中心行政諮詢組
		"vaoffice01@kuas.edu.tw", //管理學院辦公室
		"veoffice01@kuas.edu.tw", //金融系
		"waoffice01@kuas.edu.tw", //電資學院
		"weoffice01@kuas.edu.tw", //光通所辦公室
		"xboffice01@kuas.edu.tw", //通識中心教學組辦公室
		"yaoffice01@kuas.edu.tw", //燕巢校務部
		"zaoffice02@kuas.edu.tw", //育成中心
		"zkoffice02@kuas.edu.tw"  //國立高雄應用科技大學產學合作中心
	);
	if (in_array($mail, $whitelist)) {
		return true;
	}
	return false;
}
