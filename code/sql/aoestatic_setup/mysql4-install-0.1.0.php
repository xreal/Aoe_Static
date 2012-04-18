<?php
$installer = $this;

$installer->startSetup();

$installer->run("
    CREATE TABLE IF NOT EXISTS `aoe_static_tag` (
        `tag_id` int(11) NOT NULL auto_increment,
        `tag` varchar(500) character set latin1 NOT NULL,
        PRIMARY KEY  (`tag_id`),
UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `aoe_static_url` (
    `url_id` int(11) NOT NULL auto_increment,
    `url` varchar(2000) character set latin1 NOT NULL,
    `purge_prio` int(11) default NULL,
    PRIMARY KEY  (`url_id`),
KEY `purge_prio` (`purge_prio`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `aoe_static_urltag` (
    `urltag_id` int(11) NOT NULL auto_increment,
    `url_id` int(11) NOT NULL,
    `tag_id` int(11) NOT NULL,
    PRIMARY KEY  (`urltag_id`),
UNIQUE KEY `IDX_URL_ID_TAG_ID` (`url_id`,`tag_id`),
KEY `IDX_URL_ID` (`url_id`),
KEY `IDX_TAG_ID` (`tag_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


ALTER TABLE  `aoe_static_urltag` 
    ADD FOREIGN KEY (`url_id`) REFERENCES `aoe_static_url` (`url_id`) ON DELETE CASCADE;
");

