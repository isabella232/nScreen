#
# Table structure for table 'members'
#

CREATE TABLE IF NOT EXISTS `members` (
  `member_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `login` varchar(100) NOT NULL DEFAULT '',
  `passwd` varchar(32) NOT NULL DEFAULT '',
  `facebook_id` bigint(11) DEFAULT NULL,
  PRIMARY KEY (`member_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `content` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `recommendations` longtext NOT NULL,
  `recently_viewed` longtext NOT NULL,
  `watch_later` longtext NOT NULL,
  `like_dislike` longtext NOT NULL,
  `shared_by_friends` longtext NOT NULL,
  PRIMARY KEY (`member_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


