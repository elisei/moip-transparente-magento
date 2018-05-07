<?php
class MOIP_Transparente_Model_Admin
{
 	const TOKEN_TEST = "8OKLQFT5XQZXU7CKXX43GPJOMIJPMSMF";
    const KEY_TEST = "NT0UKOXS4ALNSVOXJVNXVKRLEOQCITHI5HDKW3LI";
    const ENDPOINT_TEST = "https://sandbox.moip.com.br/v2/";
    const TOKEN_PROD = "EVCHBAUMKM0U4EE4YXIA8VMC0KBEPKN2";
    const KEY_PROD = "4NECP62EKI8HRSMN3FGYOZNVYZOMBDY0EQHK9MHO";
    const ENDPOINT_PROD = "https://api.moip.com.br/v2/";
  

  	public function getDados($quote)
    {

        $id_proprio = $quote->getIncrementId();
      
        
        if ($quote->getShippingAddress()) {
            $a = $quote->getShippingAddress();
            $b = $quote->getBillingAddress();
            $this->generateLog($b->Debug(), 'MOIP_OrderDebug.log');
            
        } else {
            $a = $quote->getShippingAddress();
            $b = $quote->getBillingAddress();
            $this->generateLog($b->Debug(), 'MOIP_OrderDebug.log');
        }
        $email         = $quote->getCustomerEmail();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $cep           = substr(preg_replace("/[^0-9]/", "", $b->getPostcode()) . '00000000', 0, 8);
        $billing_cep   = substr(preg_replace("/[^0-9]/", "", $a->getPostcode()) . '00000000', 0, 8);
        $dob           = Mage::app()->getLocale()->date($quote->getCustomerDob(), null, null, true)->toString('Y-MM-dd');
        if(!$quote->getCustomerDob()){
            $dob = date('Y-m-d', strtotime($dob . ' -1 day'));
        }
        $dob           = explode('-',$dob);
        $dob_day = $dob[2];
        if(is_null($dob_day)){
            $dob_day = 01;
        }
        $dob_month = $dob[1];
        if(is_null($dob_month)){
            $dob_month = 01;
        }
        $dob_year = $dob[0];
        if($dob_year < 1900){
            $dob_year += 1900; 
        }
        $dob = $dob_year."-".$dob_month."-".$dob_day;
        $taxvat        = $quote->getCustomerTaxvat();
        $taxvat        = preg_replace("/[^0-9]/", "", $taxvat);
         if(strlen($taxvat) > 11){
            $document_type = "CNPJ";
        } else {
            $document_type = "CPF";
        }
        if($quote->getCustomerTipopessoa() == 0 && $quote->getCustomerNomefantasia()){
            $nome = $quote->getCustomerRazaosocial(). " ".$quote->getCustomerCnpj();
            $document_type = "CNPJ";
            $taxvat = $quote->getCustomerCnpj();
        } else {
             $nome =  $b->getFirstname() . ' ' . $b->getLastname();
        }
        $website_id    = Mage::app()->getWebsite()->getId();
        $website_name  = Mage::app()->getWebsite()->getName();
        $store_name    = Mage::app()->getStore()->getName();
        $data          = array(
            'id_transacao' => $quote->getId(),
            
            'nome' => $nome,
            'email' => strtolower($email),
            'ddd' => $this->getNumberOrDDD($b->getTelephone(), true),
            'telefone' => $this->getNumberOrDDD($b->getTelephone()),
            'mobile' => $this->getNumberOrDDD($b->getFax()),
            'mobile_ddd' => $this->getNumberOrDDD($b->getFax(), true),
            'shipping_logradouro' => $b->getStreet(1),
            'shipping_numero' => $this->getNumEndereco($b->getStreet(2), $b->getStreet(1)),
            'shipping_complemento' => $b->getStreet(3),
            'shipping_bairro' => $b->getStreet(4),
            'shipping_cep' => $cep,
            'shipping_cidade' => $b->getCity(),
            'shipping_estado' => strtoupper($b->getRegionCode()),
            'shipping_pais' => $b->getCountry(),
            'billing_logradouro' => $a->getStreet(1),
            'billing_numero' => $this->getNumEndereco($a->getStreet(2), $a->getStreet(1)),
            'billing_complemento' => $a->getStreet(3),
            'billing_bairro' => $a->getStreet(4),
            'billing_cep' => $billing_cep,
            'billing_cidade' => $a->getCity(),
            'billing_estado' => strtoupper($a->getRegionCode()),
            'billing_pais' => $a->getCountry(),
            'cpf' => $taxvat,
            'celular' => $this->getNumberOrDDD($b->getFax(), true) . '' . $this->getNumberOrDDD($b->getFax()),
            'sexo' => '',
            'data_nascimento' => $dob,
            'frete' => number_format($b->getShippingAmount(), 2, '', '')
        );
        $autida_values = $this->getAuditOrder($quote, $b->getShippingAmount());
        
        $json_order    = array(
            "ownId" => $id_proprio,
            "amount" => array(
                "currency" => "BRL",
                "subtotals" => $autida_values
            ),
            "items" => $this->getListaProdutos($quote),
            "customer" => array(
                "ownId" => $data['email'],
                "fullname" => $data['nome'],
                "email" => $data['email'],
                "birthDate" => $data['data_nascimento'],
                "taxDocument" => array(
                    "type" => $document_type,
                    "number" => $data['cpf']
                ),
                "phone" => array(
                    "countryCode" => "55",
                    "areaCode" => $data['ddd'],
                    "number" => $data['telefone']
                ),
                "mobile" => array(
                    "countryCode" => "55",
                    "areaCode" => $data['mobile_ddd'],
                    "number" => $data['mobile']
                    ),
                "shippingAddress" => array(
                    "street" => $data['shipping_logradouro'],
                    "streetNumber" => $data['shipping_numero'],
                    "complement" => $data['shipping_complemento'],
                    "district" => $data['shipping_bairro'],
                    "city" => $data['shipping_cidade'],
                    "state" => $data['shipping_estado'],
                    "country" => "BRA",
                    "zipCode" => $data['shipping_cep']
                ),
                "billingAddress" => array(
                    "street" => $data['billing_logradouro'],
                    "streetNumber" => $data['billing_numero'],
                    "complement" => $data['billing_complemento'],
                    "district" => $data['billing_bairro'],
                    "city" => $data['billing_cidade'],
                    "state" => $data['billing_estado'],
                    "country" => "BRA",
                    "zipCode" => $data['billing_cep']
                )
            ),
           
           
        );
        $use_split = Mage::getStoreConfig('moipall/mktplacet_config/enable_split');
        if($use_split){
            $comissoes = $this->getListaComissoesAvancadas($quote);
            $normalize = $this->normalizeComissao($comissoes);
            $array_receivers = array("receivers" => $normalize);
            $json_order = array_merge($json_order, $array_receivers);
        }
        $json_order    = Mage::helper('core')->jsonEncode((object) $json_order);
        $this->generateLog("------ Geração da order #{$id_proprio} ------", 'MOIP_Order.log');
        $this->generateLog("------ Json Enviado da order ------", 'MOIP_Order.log');
        $this->generateLog($json_order, 'MOIP_Order.log');
        return $json_order;
    }
     public function getAuditOrder($quote, $shipping)
    {
        $grandTotalProd = null;
       
        $items     = $quote->getallvisibleitems();
        $itemcount = count($items);
        $produtos  = array();
        foreach ($items as $itemId => $item) {
            if ($item->getPrice() > 0) {
               $grandTotalProd += $item->getPrice() * $item->getQtyOrdered();
            }
        }


        $totals     = $quote->getTotals();
        $grandTotal = $quote->getGrandTotal();
        $total_cal  = $grandTotalProd + $shipping;
        $this->generateLog($grandTotal, 'debub_valores.log');
        $this->generateLog($grandTotalProd, 'debub_valores.log');

        if ($total_cal != $grandTotal) {
            if ($total_cal > $grandTotal) {
                $diff          = $total_cal - $grandTotal;
                $return_values = array(
                    "shipping" => number_format($shipping, 2, '', ''),
                    "discount" => number_format($diff, 2, '', ''),
                    "addition" => 0,
                );
            } else {
                $diff          = $grandTotal - $total_cal;
                $return_values = array(
                    "shipping" => number_format($shipping, 2, '', ''),
                    "discount" => 0,
                    "addition" => number_format($diff, 2, '', '')
                );
            }
        } else {
            $return_values = array(
                "shipping" => number_format($shipping, 2, '', ''),
                "discount" => 0,
                "addition" => 0
            );
        }
        return $return_values;
    }
    public function getListaProdutos($quote)
    {
        $items     = $quote->getallvisibleitems();
        $itemcount = count($items);
        $produtos  = array();
        foreach ($items as $itemId => $item) {
            if ($item->getPrice() > 0) {
                $produtos[] = array(
                    'product' => $item->getName(),
                    'quantity' => $item->getQtyOrdered(),
                    'detail' => $item->getSku(),
                    'price' => number_format($item->getPrice(), 2, '', '')
                );
            }
        }
        return $produtos;
    }
    public function getOrderMoip($json_order)
    {
        
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_TEST."orders/";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_PROD."orders/";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        $result = array();
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_order);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl = curl_getinfo($ch);
        curl_close($ch);
        $decode = json_decode($responseBody);
        $this->generateLog($header, 'MOIP_Order.log');
        $this->generateLog("------ Resposta da Order ------", 'MOIP_Order.log');
        $this->generateLog($responseBody, 'MOIP_Order.log');
        $this->generateLog("------ CurlInfo da Order ------", 'MOIP_Order.log');
        $this->generateLog(json_encode($info_curl), 'MOIP_Order.log');
        return $decode;
    }
    public function getNumEndereco($endereco, $enderecob)
    {
        $numEnderecoDefault = '0';
        if (!$endereco)
            $endereco = $enderecob;
        else
            $endereco = $endereco;
        $numEndereco = trim(preg_replace("/[^0-9]/", "", $endereco));
        if ($numEndereco)
            return ($numEndereco);
        else
            return ($numEnderecoDefault);
    }
    public function getPosSeparador($endereco)
    {
        $posSeparador = strpos($endereco, ',');
        if ($posSeparador === false)
            $posSeparador = strpos($endereco, '-');
        return ($posSeparador);
    }
    public function getNumberOrDDD($param_telefone, $param_ddd = false)
    {
        $cust_ddd       = '11';
        $cust_telephone = preg_replace("/[^0-9]/", "", $param_telefone);
        if(strlen($cust_telephone) == 11){
            $st             = strlen($cust_telephone) - 9;
            $indice         = 9;
        } else {
            $st             = strlen($cust_telephone) - 8;
            $indice         = 8;
        }
        
        if ($st > 0) {
            $cust_ddd       = substr($cust_telephone, 0, 2);
            $cust_telephone = substr($cust_telephone, $st, $indice);
        }
        if ($param_ddd === false) {
            $retorno = $cust_telephone;
        } else {
            $retorno = $cust_ddd;
        }
        return $retorno;
    }
     public function generateLog($variable, $name_log){
        

        if(Mage::getSingleton('transparente/standard')->getConfigData('log') == 1){
            Mage::log($variable, null, 'MOIP/'.$name_log, true);
        } else {
            
        }
        

    }
}

