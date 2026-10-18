# phpMyAdmin MySQL-Dump
# version 2.3.3pl1
# http://www.phpmyadmin.net/ (download page)
#
# Host: localhost
# Generation Time: Jan 09, 2003 at 02:42 AM
# Server version: 3.23.52
# PHP Version: 4.2.3
# Database : `dotproject`
# --------------------------------------------------------

#
# Table structure for table `timetrack_data`
#

CREATE TABLE timetrack_data (
  tt_data_id int(11) NOT NULL auto_increment,
  tt_data_timesheet_id int(11) unsigned NOT NULL default '0',
  tt_data_date datetime NOT NULL default '0000-00-00 00:00:00',
  tt_data_client_id int(6) unsigned default NULL,
  tt_data_project_id int(11) unsigned default NULL,
  tt_data_task_id int(11) unsigned default NULL,
  tt_data_description varchar(44) NOT NULL default '',
  tt_data_hours float NOT NULL default '0',
  tt_data_change_date timestamp(14) NOT NULL,
  tt_data_note varchar(255) default NULL,
  PRIMARY KEY  (tt_data_id)
) ENGINE=MyISAM;

#
# Table structure for table `timetrack_idx`
#

CREATE TABLE timetrack_idx (
  tt_id int(11) NOT NULL auto_increment,
  tt_user_id int(11) NOT NULL default '0',
  tt_week int(2) NOT NULL default '0',
  tt_active tinyint(4) default NULL,
  tt_note_id int(11) default NULL,
  tt_year int(4) NOT NULL default '0',
  tt_submitted date default NULL,
  tt_start_date datetime default NULL,
  tt_end_date datetime default NULL,
  tt_supervisor_approval date default NULL,
  tt_pm_approval date default NULL,
  tt_approve_note varchar(80) default NULL,
  tt_approve_note_date date NOT NULL default '0000-00-00',
  PRIMARY KEY  (tt_id)
) ENGINE=MyISAM;