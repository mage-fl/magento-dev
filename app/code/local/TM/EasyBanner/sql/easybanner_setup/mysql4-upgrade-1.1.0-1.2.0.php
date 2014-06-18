<?php
/**
 * DO NOT REMOVE OR MODIFY THIS NOTICE
 *
 * EasyBanner module for Magento - flexible banner management
 *
 * @author Templates-Master Team <www.templates-master.com>
 */

/**
 * @var Mage_Core_Model_Resource_Setup
 */
$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('easybanner_placeholder')}
 ADD COLUMN `sort_mode` enum('sort_order','random') NOT NULL DEFAULT 'sort_order';

");

$installer->endSetup();
