<?php

class SGoodwin_Layouts_Helper_Data extends Mage_Core_Helper_Abstract
{
	function limit_words($string, $word_limit)
	{
		$words = explode(" ",$string);
		return implode(" ",array_splice($words,0,$word_limit));
	}
    

}


