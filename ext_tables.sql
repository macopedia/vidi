#
# Table structure for table 'tx_vidi_selection'
#
CREATE TABLE tx_vidi_selection (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,

	visibility int(11) DEFAULT '0' NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	data_type varchar(255) DEFAULT '' NOT NULL,
	query text,
	speaking_query text,

	PRIMARY KEY (uid),
	KEY parent (pid),
);

#
# Table structure for table 'tx_vidi_preference'
#
CREATE TABLE tx_vidi_preference (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	data_type varchar(255) DEFAULT '' NOT NULL,
	preferences text,

	PRIMARY KEY (uid),
	KEY parent (pid),
);

#
# Table structure for table 'tx_vidi_querybuilder'
#
CREATE TABLE tx_vidi_querybuilder (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	affected_table varchar(255) DEFAULT '' NOT NULL,
	queryname varchar(255) DEFAULT '' NOT NULL,
	where_parts text,
	user varchar(255) DEFAULT '' NOT NULL,
	hidden tinyint(1) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(1) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
);
