<?php

class MOIP_Transparente_Block_Standard_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('MOIP/transparente/info.phtml');
    }



    public function getTransparente()
    {
        return Mage::getSingleton('transparente/api');
    }

public function getNomeBrand($param) {
        $nome = "";
        switch ($param) {
        case "VI":
            $nome = "Visa";
            break;
        case "MC":
            $nome = "MasterCard";
            break;
        case "AE":
            $nome = "American Express";
            break;
          case "DC":
            $nome = "Dinners Club";
            break;
        }
        return $nome;
}
	private function getNomePagamento($param) {
		$nome = "";
		switch ($param) {
		case "BoletoBancario":
		    $nome = "Boleto Bancário";
		    break;
		case "DebitoBancario":
		    $nome = "Transferência Bancária";
		    break;
		case "CartaoCredito":
		    $nome = "Cartão de Crédito";
		    break;
		}
		return $nome;
	}

       public function toPdf()
    {
        $this->setTemplate('MOIP/transparente/info/pdf.phtml');
        return $this->toHtml();
    }

    protected function _prepareInfo()
    {


                $order = $this->getInfo()->getOrder();

                $customer_order = Mage::getModel('customer/customer')->load($order->getCustomerId());
                $order =  $order->getId();

                $model = Mage::getModel('transparente/write');
                $result = $model->load($order, 'mage_pay')->getData();
                $dados = array();

                if((int)$customer_order->getTipopessoa()!= 1){
                    $dados['compra_pj'] = 1;
                    $dados['cnpj'] = $customer_order->getCnpj();
                    $dados['razaosocial'] = $customer_order->getRazaosocial();
                    $dados['nomefantasia'] = $customer_order->getNomefantasia();
                    $dados['insestadual'] = $customer_order->getInsestadual();
                }
                $dados= array_merge($result, $dados);
            return $dados;
    }
      public function getMethodInstance()
    {
        if (!$this->hasMethodInstance()) {
            if ($this->getMethod()) {
                $instance = Mage::helper('payment')->getMethodInstance($this->getMethod());
                if ($instance) {
                    $instance->setInfoInstance($this);
                    $this->setMethodInstance($instance);
                    return $instance;
                }
            }
            Mage::throwException(Mage::helper('payment')->__('The requested Payment Method is not available.'));
        }

        return $this->_getData('method_instance');
    }

    /**
     * Encrypt data
     *
     * @param   string $data
     * @return  string
     */
    public function encrypt($data)
    {
        if ($data) {
            return Mage::helper('core')->encrypt($data);
        }
        return $data;
    }

    /**
     * Decrypt data
     *
     * @param   string $data
     * @return  string
     */
    public function decrypt($data)
    {
        if ($data) {
            return Mage::helper('core')->decrypt($data);
        }
        return $data;
    }

    /**
     * Additional information setter
     * Updates data inside the 'additional_information' array
     * or all 'additional_information' if key is data array
     *
     * @param string|array $key
     * @param mixed $value
     * @return Mage_Payment_Model_Info
     * @throws Mage_Core_Exception
     */
    public function setAdditionalInformation($key, $value = null)
    {
        if (is_object($value)) {
            Mage::throwException(Mage::helper('sales')->__('Payment disallow storing objects.'));
        }
        $this->_initAdditionalInformation();
        if (is_array($key) && is_null($value)) {
            $this->_additionalInformation = $key;
        } else {
            $this->_additionalInformation[$key] = $value;
        }
        return $this->setData('additional_information', $this->_additionalInformation);
    }

    /**
     * Getter for entire additional_information value or one of its element by key
     *
     * @param string $key
     * @return array|null|mixed
     */
    public function getAdditionalInformation($key = null)
    {
        $this->_initAdditionalInformation();
        if (null === $key) {
            return $this->_additionalInformation;
        }
        return isset($this->_additionalInformation[$key]) ? $this->_additionalInformation[$key] : null;
    }

    /**
     * Unsetter for entire additional_information value or one of its element by key
     *
     * @param string $key
     * @return Mage_Payment_Model_Info
     */
    public function unsAdditionalInformation($key = null)
    {
        if ($key && isset($this->_additionalInformation[$key])) {
            unset($this->_additionalInformation[$key]);
            return $this->setData('additional_information', $this->_additionalInformation);
        }
        $this->_additionalInformation = -1;
        return $this->unsetData('additional_information');
    }

    /**
     * Check whether there is additional information by specified key
     *
     * @param $key
     * @return bool
     */
    public function hasAdditionalInformation($key = null)
    {
        $this->_initAdditionalInformation();
        return null === $key
            ? !empty($this->_additionalInformation)
            : array_key_exists($key, $this->_additionalInformation);
    }

    /**
     * Make sure _additionalInformation is an array
     */
    protected function _initAdditionalInformation()
    {
        if (-1 === $this->_additionalInformation) {
            $this->_additionalInformation = $this->_getData('additional_information');
        }
        if (null === $this->_additionalInformation) {
            $this->_additionalInformation = array();
        }
    }
}
