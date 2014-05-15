<?php
/**
 * @category    Bubble
 * @package     Bubble_CmsTree
 * @version     1.2.6
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_CmsTree_Block_Page_Html_Topmenu extends Bubble_CmsTree_Block_Catalog_Navigation
{
    public function shouldRenderPages()
    {
        return Mage::getStoreConfigFlag('bubble_cmstree/general/include_in_menu');
    }
}