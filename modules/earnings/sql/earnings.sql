CREATE TABLE `earnings` (
  `earning_id` int(11) NOT NULL auto_increment,
  `earning_user_id` int(11) NOT NULL default '0',
  `earning_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `earning_num` varchar(35) NOT NULL default '',
  `earning_title` varchar(40) NOT NULL default '',
  `earning_submit_company_id` int(10) NOT NULL default '0',
  `earning_submit_contact` varchar(50) NOT NULL default '',
  `earning_submit_email` varchar(200) NOT NULL default '',
  `earning_submit_address1` varchar(50) NOT NULL default '',
  `earning_submit_address2` varchar(50) NOT NULL default '',
  `earning_submit_city` varchar(30) NOT NULL default '',
  `earning_submit_state` varchar(30) NOT NULL default '',
  `earning_submit_zip` varchar(11) NOT NULL default '',
  `earning_submit_phone` varchar(20) NOT NULL default '',
  `earning_terms` varchar(10) NOT NULL default 'NET30',
  `earning_comments` varchar(250) NOT NULL default '',
  `earning_total` int(20) NOT NULL default '0',
  `earning_submitted` datetime NOT NULL default '0000-00-00 00:00:00',
  `earning_submitted_comment` varchar(250) NOT NULL default '',
  `earning_approved_by` int(11) NOT NULL default '0',
  `earning_approved` datetime NOT NULL default '0000-00-00 00:00:00',
  `earning_approved_comment` varchar(250) NOT NULL default '',
  `earning_paid` datetime NOT NULL default '0000-00-00 00:00:00',
  `earning_paid_comment` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`earning_id`),
  UNIQUE KEY `earning_num` (`earning_num`),
  KEY `earning_user_id` (`earning_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 ;
# --------------------------------------------------------

CREATE TABLE `earnings_items` (
  `earning_items_id` int(11) NOT NULL auto_increment,
  `earning_parent_id` int(11) default NULL,
  `earning_tasklog_id` int(11) default NULL,
  `earning_item_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `earning_item_description` text NOT NULL,
  `earning_item_hours` float NOT NULL default '0',
  `earning_item_costcode` varchar(8) NOT NULL default '',
  `earning_item_rate` float NOT NULL default '0',
  PRIMARY KEY  (`earning_items_id`),
  KEY `earning_parent_id` (`earning_parent_id`)
) ENGINE=MyISAM AUTO_INCREMENT=58 ;

