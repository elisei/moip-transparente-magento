<?php
class MOIP_Transparente_Model_Api
{
    const TOKEN_TEST = "8OKLQFT5XQZXU7CKXX43GPJOMIJPMSMF";
    const KEY_TEST = "NT0UKOXS4ALNSVOXJVNXVKRLEOQCITHI5HDKW3LI";
    const ENDPOINT_TEST = "https://sandbox.moip.com.br/v2/";
    const TOKEN_PROD = "EVCHBAUMKM0U4EE4YXIA8VMC0KBEPKN2";
    const KEY_PROD = "4NECP62EKI8HRSMN3FGYOZNVYZOMBDY0EQHK9MHO";
    const ENDPOINT_PROD = "https://api.moip.com.br/v2/";
  
   
    public function getPayment()
    {
        return $this->getQuote()->getPayment();
    }
    public function getSession()
    {
        return Mage::getSingleton('transparente/session');
    }
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    public function normalizeComissao($comissionados){

       $_i = 0;
        foreach ($comissionados as $key => $value) {
            if(!is_null($value["moipAccount"]["id"])){
                    $id_sellers[] = $value["moipAccount"]["id"];
                    $amount_sellers[] = $value["amount"]["fixed"];
                   
                   if(in_array($value["moipAccount"]["id"], $controle_sellers_repeat )){
                        $repeat = true;
                   } else {
                    if(!$repeat){
                        $controle_sellers_repeat[] = $value["moipAccount"]["id"];
                        $repeat = false;
                    }
                    
                   }
            } else {
                unset($comissionados[$_i]);
            }
            $_i++;
           
           
        }
        
                
        if($repeat){
                $unique = array_unique($id_sellers);
                $duplicates = array_diff_assoc($id_sellers, $unique);
                $duplicate_keys = array_keys(array_intersect($id_sellers, $duplicates));

                foreach ($duplicate_keys as $key => $value) {
                    $seller_id_temp             = $comissionados[$value]["moipAccount"]["id"];
                        unset($comissionados[$value]);
                    if(!array_key_exists($seller_id_temp, $temp_comissionado )){
                        $amount_for_seller[$seller_id_temp]     = $amount_sellers[$value] + $amount_for_seller[$seller_id_temp];
                        $temp_comissionados[$seller_id_temp]    = array(
                                                                        'moipAccount' => array(
                                                                                                'id' => $seller_id_temp
                                                                                                ),
                                                                        'type' => 'SECONDARY',
                                                                        'amount' => array(
                                                                                            'fixed' => $amount_for_seller[$seller_id_temp]
                                                                                        )
                                                                    );

                    }
                }

            foreach ($temp_comissionados as $key => $value) {
                $new_sellers[] = $value;
            }
            $comissionados = array_merge($comissionados, $new_sellers);
        }
        return $comissionados;
    }
    public function getListaComissoesAvancadas($quote)
    {

        $comissionados[0] =  array(
                                'moipAccount' => array('id' => Mage::getStoreConfig('moipall/mktplacet_config/mpa')),
                                'type' => "PRIMARY",
                            );


        
        $items     = $quote->getAllVisibleItems();
        $itemcount = count($items);
        $produtos  = array();
        $storeId   = Mage::app()->getStore()->getStoreId();

        $split_type             = Mage::getStoreConfig('moipall/mktplacet_config/split_type');

        if($split_type == 'attributeproduct'){
            $attribute_mpa          = Mage::getStoreConfig('moipall/mktplacet_config/mpa_forprod');
            $attribute_commisao     = Mage::getStoreConfig('moipall/mktplacet_config/value_forprod');
    
            
            foreach ($items as $key => $item) {
                $mpa_secundary  = null;
                $valor_comissao_comprado = null;
                $product                     = Mage::getModel('catalog/product')->load($item->getProductId());
                $mpa_secundary               = $product->getAttributeText($attribute_mpa);
                $valor_comissao_comprado     = $product->getData($attribute_commisao);
                
                $valor_comissao              = $valor_comissao_comprado / 100;
                $comissao_valor     = $item->getPrice() - ($item->getPrice() * $valor_comissao);
                $comissao_toJson    = $comissao_valor*$item->getQty();
                $this->generateLog($item->getProductId(), 'MOIP_Comissioesdois.log');
                $this->generateLog("MPA ".is_null($mpa_secundary)." setado ".isset($mpa_secundary)." mpa é ".$mpa_secundary, 'MOIP_Comissioesdois.log');
                $this->generateLog("comissao ".$valor_comissao_comprado  , 'MOIP_Comissioesdois.log');
                if((string)$mpa_secundary  != ""){
                    $comissionados[]    = array(
                                                'moipAccount' => array('id' => $mpa_secundary),
                                                'type' => "SECONDARY",
                                                'amount' => array('fixed' => number_format($comissao_toJson, 2, '', ''))
                                            );                   
                }
            }
        } elseif($split_type == 'perstoreview') {
            $attribute_mpa          = Mage::getStoreConfig('moipall/mktplacet_config/mpa_store');
            $attribute_commisao     = Mage::getStoreConfig('moipall/mktplacet_config/value_store');
    

            foreach ($items as $key => $item) {

                $product                     = Mage::getModel('catalog/product')->load($item->getProductId());
                $valor_comissao_comprado     = $attribute_commisao;
                $mpa_secundary               = $attribute_mpa;
                $valor_comissao              = $valor_comissao_comprado / 100;
                $comissao_valor     = $item->getPrice() - ($item->getPrice() * $valor_comissao);
                $comissao_toJson    = $comissao_valor*$item->getQty();
                if($mpa_secundary){
                    $comissionados[]    = array(
                            'moipAccount' => array('id' => $mpa_secundary),
                            'type' => "SECONDARY",
                            'amount' => array('fixed' => number_format($comissao_toJson, 2, '', ''))
                        );
                }
            }
        } else{
            // Você pode personalizar seu método de split aqui! :D 

        }
        

        return $comissionados;
    }
    public function generatePayment($json, $IdMoip)
    {
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_TEST."orders/{$IdMoip}/payments";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_PROD."orders/{$IdMoip}/payments";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        $result = array();
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl = curl_getinfo($ch);
        curl_close($ch);
        $this->generateLog("------ Geração do Pagamento ------", 'MOIP_Order.log');
        $this->generateLog("------ Json Enviado do Pagamento ------", 'MOIP_Order.log');
        $this->generateLog($json, 'MOIP_Order.log');
        $this->generateLog($oauth, 'MOIP_Order.log');
        $this->generateLog("------ Resposta do Pagamento ------", 'MOIP_Order.log');
        $this->generateLog($responseBody, 'MOIP_Order.log');
        $this->generateLog("------ CurlInfo do Pagamento ------", 'MOIP_Order.log');
        $this->generateLog(json_encode($info_curl), 'MOIP_Order.log');
        
        $decode = json_decode($responseBody);
        return $decode;
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
                    'quantity' => $item->getQty(),
                    'detail' => $item->getSku(),
                    'price' => number_format($item->getPrice(), 2, '', '')
                );
            }
        }
        return $produtos;
    }
    public function getAuditOrder($quote, $shipping)
    {
        $grandTotalProd = null;
       
        $items     = $quote->getallvisibleitems();
        $itemcount = count($items);
        $produtos  = array();
        foreach ($items as $itemId => $item) {
            if ($item->getPrice() > 0) {
               $grandTotalProd += $item->getPrice() * $item->getQty();
            }
        }


        $totals     = $quote->getTotals();
        $grandTotal = $totals['grand_total']->getValue();
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
    public function getDados($quote)
    {
        $id_proprio = $quote->getReservedOrderId();
      
        
        if ($quote->getShippingAddress()) {
            $a = $quote->getShippingAddress();
            $b = $quote->getBillingAddress();
            $this->generateLog($b->Debug(), 'MOIP_OrderDebug.log');
            
        } else {
            $a = $quote->getShippingAddress();
            $b = $quote->getBillingAddress();
            $this->generateLog($b->Debug(), 'MOIP_OrderDebug.log');
        }
        $email         = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $cep           = substr(preg_replace("/[^0-9]/", "", $b->getPostcode()) . '00000000', 0, 8);
        $billing_cep   = substr(preg_replace("/[^0-9]/", "", $a->getPostcode()) . '00000000', 0, 8);
        $dob           = Mage::app()->getLocale()->date($quote->getCustomerDob(), null, null, false)->toString('Y-MM-dd');
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
            $nome = $quote->getCustomerNomefantasia();
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
    public function getOrderIdMoip($json_order)
    {
        $session   = $this->getCheckout();
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
        return $decode->id;
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
    public function getPaymentJsonCc($info, $quote)
    {
        $additionaldata = unserialize($info->getAdditionalData());

        $dob           = Mage::app()->getLocale()->date($quote->getCustomerDob(), null, null, false)->toString('Y-MM-dd');
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
        
        $b = $quote->getBillingAddress();        
        $dob = $dob_year."-".$dob_month."-".$dob_day;
        $ddd  = $this->getNumberOrDDD($b->getTelephone(), true);
        $telefone = $this->getNumberOrDDD($b->getTelephone());
        if ($additionaldata['use_cofre'] == 0) {
            $json = array(
                "installmentCount" => $additionaldata['installmentcount_moip'],
                "fundingInstrument" => array(
                    "method" => "CREDIT_CARD",
                    "creditCard" => array(
                        "hash" => $additionaldata['hash_moip'],
                        "holder" => array(
                            "fullname" => $additionaldata['fullname_moip'],
                            "birthdate" => $dob,
                            "taxDocument" => array(
                                "type" => "CPF",
                                "number" => $additionaldata['taxdocument_moip']
                            ),
                            "phone" => array(
                                "countryCode" => "55",
                                "areaCode" => $ddd,
                                "number" => $telefone
                            )
                        )
                    )
                )
            );
        } elseif ($additionaldata['use_cofre'] == 1) {
            $json = array(
                "installmentCount" => $additionaldata['installmentcountcofre_moip'],
                "fundingInstrument" => array(
                    "method" => "CREDIT_CARD",
                    "creditCard" => array(
                        "id" => $additionaldata['credit_card_cofre_nb'],
                        "cvc" => $additionaldata['credit_card_ccv']
                    )
                )
            );
        }
        $json = Mage::helper('core')->jsonEncode((object) $json);
        return $json;
    }
    public function getPaymentJsonBoleto($info, $quote)
    {
        $additionaldata = unserialize($info->getAdditionalData());
        $json           = array(
            "fundingInstrument" => array(
                "method" => "BOLETO",
                "boleto" => array(
                    "expirationDate" => $this->getDataVencimento(Mage::getStoreConfig('payment/moip_boleto/vcmentoboleto')),
                    "instructionLines" => array(
                        "first" => "Pagamento do pedido na loja: " . Mage::getStoreConfig('payment/moip_transparente_standard/apelido'),
                        "second" => "Não Receber após o Vencimento",
                        "third" => "+ Info em: " . Mage::getBaseUrl()
                    )
                )
            )
        );
        $json           = Mage::helper('core')->jsonEncode((object) $json);
        return $json;
    }
    public function getPaymentJsonTef($info, $quote)
    {
        $additionaldata = unserialize($info->getAdditionalData());
        $json           = array(
            "fundingInstrument" => array(
                "method" => "ONLINE_BANK_DEBIT",
                "onlineBankDebit" => array(
                    "bankNumber" => $additionaldata['banknumber_moip'],
                    "expirationDate" => $this->getDataVencimento(Mage::getStoreConfig('payment/tef_boleto/vcmentotef')),
                    "returnUri" => Mage::getBaseUrl()
                )
            )
        );
        $json           = Mage::helper('core')->jsonEncode((object) $json);
        return $json;
    }
    public function getDataVencimento($NDias)
    {
        $DataAct = date("Ymd");
        $d       = new DateTime($DataAct);
        $t       = $d->getTimestamp();
        for ($i = 0; $i < $NDias; $i++) {
            $addDay  = 86400;
            $nextDay = date('w', ($t + $addDay));
            if ($nextDay == 0 || $nextDay == 6) {
                $i--;
            }
            $t = $t + $addDay;
        }
        $d->setTimestamp($t);
        return $d->format('Y-m-d');
    }
    
    public function getParcelamento(){
        $valor = $this->getQuote()->getGrandTotal();
        $config_parcelas_juros = $this->getInfoParcelamentoJuros();
        $config_parcelas_minimo = $this->getInfoParcelamentoMinimo();
        $config_parcelas_maximo = Mage::getStoreConfig('payment/moip_cc/nummaxparcelamax');
        $json_parcelas = array();
        $count = 0;
        $json_parcelas[0] = array(
                                    'parcela' => Mage::helper('core')->currency($valor, true, false),
                                    'total_parcelado' =>  Mage::helper('core')->currency($valor, true, false),
                                    'total_juros' =>  0,
                                    'juros' => 0
                                );
        $json_parcelas[1] = array(
                                    'parcela' => Mage::helper('core')->currency($valor, true, false),
                                    'total_parcelado' =>  Mage::helper('core')->currency($valor, true, false),
                                    'total_juros' =>  0,
                                    'juros' => 0
                                );

        
        $max_div = (int)$valor/$config_parcelas_minimo;
        if($max_div > $config_parcelas_maximo) {
            $max_div = $config_parcelas_maximo;
        } elseif ($max_div > 12) {
            $max_div = 12;
        }

        if(Mage::getStoreConfig('payment/moip_cc/parcelas_avancadas') == 1){
            if($valor > Mage::getStoreConfig('payment/moip_cc/condicional_3_sem_juros') ){
            $limite = Mage::getStoreConfig('payment/moip_cc/condicional_3_max_parcela');;
            } elseif ($valor >= Mage::getStoreConfig('payment/moip_cc/condicional_2_sem_juros')) {
                $limite = Mage::getStoreConfig('payment/moip_cc/condicional_2_max_parcela');
            } elseif ($valor <= Mage::getStoreConfig('payment/moip_cc/condicional_1_sem_juros')) {
                $limite = Mage::getStoreConfig('payment/moip_cc/condicional_1_max_parcela');
            } else {
                $limite = Mage::getStoreConfig('nummaxparcelamax');
            }    
        } else {
            $limite = $max_div;
        }
        

        foreach ($config_parcelas_juros as $key => $value) {
            if($count <= $max_div){
                if($value > 0){
                    if(Mage::getStoreConfig('payment/moip_cc/tipodejuros') == 1) {
                        if($limite >= $count && Mage::getStoreConfig('payment/moip_cc/parcelas_avancadas')){
                                $parcela =  $this->getJurosComposto($valor, 0, $count);
                            } else {
                                $parcela =  $this->getJurosComposto($valor, $value, $count);
                            }
                    } else {
                        if($limite >= $count && Mage::getStoreConfig('payment/moip_cc/parcelas_avancadas')){
                            $parcela =  $this->getJurosSimples($valor, 0, $count);
                        } else {
                            $parcela =  $this->getJurosSimples($valor, $value, $count);
                        }
                    }
                    
                    $total_parcelado = $parcela * $count;
                    $juros = $value;
                    if($parcela > 5 && $parcela > $config_parcelas_minimo){
                        $json_parcelas[$count] = array(
                            'parcela' => Mage::helper('core')->currency($parcela, true, false),
                            'total_parcelado' =>  Mage::helper('core')->currency($total_parcelado, true, false),
                            'total_juros' =>  $total_parcelado - $valor,
                            'juros' => $juros,
                        );
                    }
                } else {
                    if($valor > 0 && $count > 0){
                     $json_parcelas[$count] = array(
                                        'parcela' => Mage::helper('core')->currency(($valor/$count), true, false),
                                        'total_parcelado' =>  Mage::helper('core')->currency($valor, true, false),
                                        'total_juros' =>  0,
                                        'juros' => 0
                                    );
                    }
                }
            }

            $count++;
        }
        foreach ($json_parcelas as $key => $value) {
            if($key > $limite)
                unset($json_parcelas[$key]);
        }
            
    return $json_parcelas;
    }
    public function getJurosComposto($valor, $juros, $parcela)
    {
        $principal = $valor;
        $taxa = $juros/100;
        $valParcela = ($principal * $taxa) / (1 - (pow(1 / (1 + $taxa), $parcela)));
        return $valParcela;
    }

    public function getJurosSimples($valor, $juros, $parcela)
    {
        $principal = $valor;
        $taxa = $juros/100;
        $valjuros = $principal * $taxa;
        $valParcela = ($principal + $valjuros)/$parcela;
        return $valParcela;
    }

    public function getInfoParcelamentoJuros() {
        $juros = array();

        $juros['0'] = 0;

        $juros['1'] = 0;

        $juros['2'] =  Mage::getStoreConfig('payment/moip_cc/parcela2');

        
        $juros['3'] =  Mage::getStoreConfig('payment/moip_cc/parcela3');

        
        $juros['4'] =  Mage::getStoreConfig('payment/moip_cc/parcela4');

        
        $juros['5'] =  Mage::getStoreConfig('payment/moip_cc/parcela5');


        $juros['6'] =  Mage::getStoreConfig('payment/moip_cc/parcela6');


        $juros['7'] =  Mage::getStoreConfig('payment/moip_cc/parcela7');


        $juros['8'] =  Mage::getStoreConfig('payment/moip_cc/parcela8');


        $juros['9'] =  Mage::getStoreConfig('payment/moip_cc/parcela9');
       

        $juros['10'] =  Mage::getStoreConfig('payment/moip_cc/parcela10');
       

        $juros['11'] =  Mage::getStoreConfig('payment/moip_cc/parcela11');
       

        $juros['12'] =  Mage::getStoreConfig('payment/moip_cc/parcela12');
       
        return $juros;
    }

     public function getInfoParcelamentoMinimo() {
       
        
        $valor = Mage::getStoreConfig('payment/moip_cc/valor_minimo');
        
       
        return $valor;
    }

    
    public function generateLog($variable, $name_log){
        

        if(Mage::getSingleton('transparente/standard')->getConfigData('log') == 1){
            $dir_log = Mage::getBaseDir('var').'/log/MOIP/';

            if (!file_exists($dir_log)) {
                mkdir($dir_log, 0755, true);
            }

            Mage::log($variable, null, 'MOIP/'.$name_log, true);    
        } else {
            
        }
        

    }
}

