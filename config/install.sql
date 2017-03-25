SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `kuas_notification_filter` (
  `no` int(11) NOT NULL,
  `regex` varchar(255) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `comment` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `kuas_notification_filter` (`no`, `regex`, `type`, `comment`) VALUES
(1, '^bcoffice01@kuas.edu.tw$', 1, '祕書室公關組'),
(1, '^bdoffice01@kuas.edu.tw$', 1, '秘書室校務企劃組'),
(1, '^caoffice01@kuas.edu.tw$', 1, '教務處'),
(1, '^ccoffice02@kuas.edu.tw$', 1, '綜合教務組'),
(1, '^cdoffice02@kuas.edu.tw$', 1, '教學發展中心'),
(1, '^cgoffice01@kuas.edu.tw$', 1, '南區區域教學資源中心'),
(1, '^dboffice01@kuas.edu.tw$', 1, '學務處諮商輔導中心'),
(1, '^dcoffice01@kuas.edu.tw$', 1, '學務處生輔組'),
(1, '^ddoffice01@kuas.edu.tw$', 1, '課外活動組'),
(1, '^deoffice02@kuas.edu.tw$', 1, '衛生保健組'),
(1, '^dfoffice01@kuas.edu.tw$', 1, '軍訓室'),
(1, '^eboffice02@kuas.edu.tw$', 1, '總務處事務組'),
(1, '^ecoffice01@kuas.edu.tw$', 1, '總務處出納組'),
(1, '^edoffice01@kuas.edu.tw$', 1, '總務處營繕組'),
(1, '^eeoffice01@kuas.edu.tw$', 1, '總務處保管組'),
(1, '^efoffice01@kuas.edu.tw$', 1, '總務處事務組'),
(1, '^faoffice01@kuas.edu.tw$', 1, '研究發展處'),
(1, '^fboffice01@kuas.edu.tw$', 1, '研究發展處學術服務組'),
(1, '^fcoffice01@kuas.edu.tw$', 1, '研發處技術合作組'),
(1, '^ieoffice02@kuas.edu.tw$', 1, '推廣教育中心ie02'),
(1, '^jcoffice01@kuas.edu.tw$', 1, '進修學院學務組'),
(1, '^jktuhs@kuas.edu.tw$', 1, '人文與社會科學學刊'),
(1, '^khoffice01@kuas.edu.tw$', 1, '語文中心'),
(1, '^kuassa@kuas.edu.tw$', 1, '學生會'),
(1, '^laoffice01@kuas.edu.tw$', 1, '環安衛中心 環保組'),
(1, '^oboffice01@kuas.edu.tw$', 1, '高雄應用科技大學圖書館'),
(1, '^pboffice01@kuas.edu.tw$', 1, '計網中心行政諮詢組'),
(1, '^vaoffice01@kuas.edu.tw$', 1, '管理學院辦公室'),
(1, '^veoffice01@kuas.edu.tw$', 1, '金融系'),
(1, '^vgoffice01@kuas.edu.tw$', 1, '觀光系主任辦公室'),
(1, '^waoffice01@kuas.edu.tw$', 1, '電資學院'),
(1, '^weoffice01@kuas.edu.tw$', 1, '光通所辦公室'),
(1, '^xboffice01@kuas.edu.tw$', 1, '通識中心教學組辦公室'),
(1, '^xcoffice01@kuas.edu.tw$', 1, '通識中心活動組辦公室'),
(1, '^yaoffice01@kuas.edu.tw$', 1, '燕巢校務部'),
(1, '^zaoffice02@kuas.edu.tw$', 1, '育成中心'),
(1, '^zkoffice02@kuas.edu.tw$', 1, '國立高雄應用科技大學產學合作中心');
(2, '@github.com$', -1, 'Github'),

CREATE TABLE `kuas_notification_input` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `input` text NOT NULL,
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `kuas_notification_log` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `message` text NOT NULL,
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `kuas_notification_msgqueue` (
  `tmid` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `kuas_notification_news` (
  `idx` int(11) NOT NULL,
  `messageid` varchar(20) NOT NULL,
  `from` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fbpost` tinyint(4) NOT NULL DEFAULT '0',
  `fbmessage` tinyint(1) NOT NULL DEFAULT '0',
  `hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `kuas_notification_user` (
  `uid` varchar(255) NOT NULL,
  `tmid` varchar(255) NOT NULL,
  `sid` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL,
  `fbmessage` tinyint(1) NOT NULL DEFAULT '0',
  `lastread` timestamp NOT NULL DEFAULT '2038-01-19 03:14:07'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `kuas_notification_filter`
  ADD UNIQUE KEY `regex` (`regex`);

ALTER TABLE `kuas_notification_input`
  ADD UNIQUE KEY `hash` (`hash`);

ALTER TABLE `kuas_notification_log`
  ADD UNIQUE KEY `hash` (`hash`);

ALTER TABLE `kuas_notification_msgqueue`
  ADD UNIQUE KEY `hash` (`hash`);

ALTER TABLE `kuas_notification_news`
  ADD PRIMARY KEY (`idx`),
  ADD UNIQUE KEY `hash` (`hash`);

ALTER TABLE `kuas_notification_user`
  ADD UNIQUE KEY `tmid` (`tmid`);


ALTER TABLE `kuas_notification_news`
  MODIFY `idx` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
