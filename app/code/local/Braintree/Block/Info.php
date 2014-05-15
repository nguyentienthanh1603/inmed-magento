<?php

class Braintree_Block_Info extends Mage_Payment_Block_Info
{
    protected function getCcTypeName()
    {
        $types = Mage::getSingleton('payment/config')->getCcTypes();
        $ccType = $this->getInfo()->getCcType();
        if (isset($types[$ccType])) {
                return $types[$ccType];
        }
        else {
            return Mage::helper("payment")->__("Stored Card");
        }
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = array();
        $info = $this->getInfo();

        if ($ccType = $this->getCcTypeName())
        {
            $data[Mage::helper("payment")->__("Credit Card Type")] = $ccType;
        }

        if ($info->getCcLast4())
        {
            $data[Mage::helper("payment")->__("Credit Card Number")] = sprintf('xxxx-%s', $info->getCcLast4());
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }

    public function getChildHtml($name = '', $useCache = true, $sorted = false) {
        $payment = $this->getRequest()->getPost('payment');
        $result = "";

        if (isset($payment["cc_token"]) && $payment["cc_token"]) {
            $data["payment[cc_token]"] = $payment["cc_token"];
            $cc_token = $payment["cc_token"];
            $result .= "<input type='hidden' name='payment[cc_token]' value='$cc_token'>";
        }
        return $result;
    }
}

