<?php
/**
 * Install script
 */

/**
 *  @var $this Mage_Core_Model_Resource_Setup
 */
$this->startSetup();

$version = Mage::getVersionInfo();
if ($version['minor'] >= 6) {
    /**
     * Create table 'aoe_static_tag'
     */
    $table = $this->getConnection()
        ->newTable($this->getTable('aoestatic/tag'))
        ->addColumn('tag_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
            ), 'Tag Id')
        ->addColumn('tag', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => false,
            ), 'Tag')
        ->addIndex($this->getIdxName('aoestatic/tag', array('tag'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
            array('tag'),array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
        ->setComment('AOE Static Tag Table');
    $this->getConnection()->createTable($table);

    /**
     * Create table 'aoe_static_url'
     */
    $table = $this->getConnection()
        ->newTable($this->getTable('aoestatic/url'))
        ->addColumn('url_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
            ), 'URL Id')
        ->addColumn('url', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => false,
            ), 'URL')
        ->addColumn('purge_prio', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'  => true,
            ), 'Purge Priority')
        ->addIndex($this->getIdxName('aoestatic/url', array('purge_prio')),
            array('purge_prio'))
        ->setComment('AOE Static URL Table');
    $this->getConnection()->createTable($table);

    /**
     * Create table 'aoe_static_urltag'
     */
    $table = $this->getConnection()
        ->newTable($this->getTable('aoestatic/urltag'))
        ->addColumn('urltag_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
            ), 'URL-Tag Id')
        ->addColumn('url_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned'  => true,
            'nullable'  => false,
            ), 'Foreign URL Id')
        ->addColumn('tag_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned'  => true,
            'nullable'  => false,
            ), 'Foreign Tag Id')
        ->addIndex($this->getIdxName('aoestatic/urltag', array('url_id')),
            array('url_id'))
        ->addIndex($this->getIdxName('aoestatic/urltag', array('tag_id')),
            array('tag_id'))
        ->addIndex($this->getIdxName('aoestatic/urltag', array('url_id','tag_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
            array('url_id','tag_id'),array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
        ->addForeignKey(
            $this->getFkName('aoestatic/urltag', 'tag_id', 'aoestatic/tag', 'tag_id'),
            'tag_id', $this->getTable('aoestatic/tag'), 'tag_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey($this->getFkName('aoestatic/urltag', 'url_id', 'aoestatic/url', 'url_id'),
            'url_id', $this->getTable('aoestatic/url'), 'url_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->setComment('AOE Static URL-to-Tag-Relation Table');
    $this->getConnection()->createTable($table);
} else {
    // Old pre 1.6 Setup
    $this->run("
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
}
    
$this->endSetup();
