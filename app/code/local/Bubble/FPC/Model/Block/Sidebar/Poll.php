<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Model_Block_Sidebar_Poll extends Bubble_FPC_Model_Block_Abstract
{
    protected $_blockName = 'right.poll';

    protected $_observers = array(
        'poll_vote_add'     => 'updateCookie',
        'customer_logout'   => 'deleteCookie',
    );

    /**
     * Initialization
     */
    protected function _construct()
    {
        if ($pollId = Mage::getSingleton('core/session')->getJustVotedPoll()) {
            $this->_session()->setLastVotedPoll($pollId);
        }
    }

    /**
     * @param $pollId
     * @return mixed
     */
    public function getPollAnswers($pollId)
    {
        $answers = Mage::getModel('poll/poll_answer')
            ->getResourceCollection()
            ->addPollFilter($pollId)
            ->load();

        return $answers;
    }

    /**
     * @return array
     */
    protected function _getDefaultValue()
    {
        $pollId = Mage::getModel('poll/poll')
            ->setStoreFilter(Mage::app()->getStore()->getId())
            ->getRandomId();

        return array(
            'poll_id' => (int) $pollId,
        );
    }

    /**
     * @return array
     */
    protected function _getValue()
    {
        $pollId = $this->_session()->getLastVotedPoll();
        if (empty($pollId)) {
            return $this->_getDefaultValue();
        }

        $answers = $this->getPollAnswers($pollId)
            ->walk('getVotesCount');

        return array(
            'poll_id' => (int) $pollId,
            'answers' => $answers,
        );
    }
}