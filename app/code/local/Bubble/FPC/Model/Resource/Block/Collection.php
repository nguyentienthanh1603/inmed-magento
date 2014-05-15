<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Model_Resource_Block_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('bubble_fpc/block');
    }
}