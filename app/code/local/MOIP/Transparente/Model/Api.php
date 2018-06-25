<?php
class MOIP_Transparente_Model_Api
{
    const TOKEN_TEST            = "8OKLQFT5XQZXU7CKXX43GPJOMIJPMSMF";
    const KEY_TEST              = "NT0UKOXS4ALNSVOXJVNXVKRLEOQCITHI5HDKW3LI";
    const ENDPOINT_TEST         = "https://sandbox.moip.com.br/v2/";
    const TOKEN_PROD            = "EVCHBAUMKM0U4EE4YXIA8VMC0KBEPKN2";
    const KEY_PROD              = "4NECP62EKI8HRSMN3FGYOZNVYZOMBDY0EQHK9MHO";
    const ENDPOINT_PROD         = "https://api.moip.com.br/v2/";
    const ENDPOINTOAUTHPROD     = "https://connect.moip.com.br/oauth/authorize";
    const ENDPOINTOAUTHDEV      = "https://connect-sandbox.moip.com.br/oauth/authorize"; 
    const SCOPE_APP             = "RECEIVE_FUNDS,REFUND,MANAGE_ACCOUNT_INFO,DEFINE_PREFERENCES,RETRIEVE_FINANCIAL_INFO";
    const RESPONSETYPE          = "code";
    const ACCOUNT_TEST          = "https://conta-sandbox.moip.com.br/";
    const ACCOUNT_PROD          = "https://conta.moip.com.br/";
    const MOIP_AUTHORIZED       = "AUTHORIZED";
    const MOIP_PRE_AUTHORIZED   = "PRE_AUTHORIZED";
    const MOIP_CANCELLED        = "CANCELLED";
    const MOIP_WAITING          = "WAITING";
    
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
    public function getAppId($type){
        if($type == "prod"){
            if(Mage::getStoreConfig('payment/moip_transparente_standard/type_app') == "d14"){
               return "APP-2UFTVZ3XW4A8";
            } elseif(Mage::getStoreConfig('payment/moip_transparente_standard/type_app') == "d30") {
                return "APP-YLDWLJWZTVDG";
            }else {
                return "APP-AKYBMMVU1FL1";
            }
            
        } else {
            return "APP-9MUFQ39Y4CQU";
        }
    }
    public function getClienteSecret($type){
        if($type == "prod"){
            if(Mage::getStoreConfig('payment/moip_transparente_standard/type_app') == "d14"){
               return "589147b6fdca404c98c4b557e1286cbc";
            } elseif(Mage::getStoreConfig('payment/moip_transparente_standard/type_app') == "d30") {
                return "1caa2776d84e4324899efcc6a4699d24";
            }else {
                return "db9pavx8542khvsyn3s0tpxyu2gom2m";
            }
        } else {
            return "26xa86dbc7mhdyqq2w69vscvhz47cri";
        }
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

    public function getListaComissoesAvancadas($order)
    {
        $items     = $order->getAllVisibleItems();
        $itemcount = count($items);
        $produtos  = array();
        $storeId   = Mage::app()->getStore()->getStoreId();

        $split_type             = Mage::getStoreConfig('moipall/mktplacet_config/split_type');

        if($split_type != "fullstoreview") {
             $comissionados[0] =  array(
                                'moipAccount' => array('id' => Mage::getStoreConfig('moipall/mktplacet_config/mpa')),
                                'type' => "PRIMARY",
                            );
        }

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
            $attribute_mpa          = Mage::app()->getWebsite()->getConfig('moipall/mktplacet_config/mpa_store');
            $attribute_commisao     = Mage::app()->getWebsite()->getConfig('moipall/mktplacet_config/value_store');
    

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
        } elseif($split_type == 'fullstoreview') {
            
            $mpa_secundary     = Mage::app()->getWebsite()->getConfig('moipall/mktplacet_config/mpa_store');
          
            $comissionados[]    = array(
                    'moipAccount' => array('id' => $mpa_secundary),
                    'type' => "SECONDARY",
                    'amount' => array('fixed' => number_format($order->getGrandTotal(), 2, '', '')),
                    'feePayor' => "true"
                );
            
        } else{
            // Você pode personalizar seu método de split. falso evento eu mudei o que envio por favor válide... @Denis Barboni


            $splitdata = new Varien_Object(array('order' => $order, 'comissionados' => $comissionados));
            Mage::dispatchEvent('moip_insert_custom_split', array('splitdata' => $splitdata)) ;
            $comissionados = $splitdata->getComissionados();

        }
        

        return $comissionados;
    }
    
    public function getAuditOrder($order)
    {
        $grandTotalProd = null;
       
        $items     = $order->getAllVisibleItems();
        $itemcount = count($items);
        $produtos  = array();
        foreach ($items as $itemId => $item) {
            if ($item->getPrice() > 0) {
               $grandTotalProd += $item->getPrice() * $item->getQtyOrdered(); //300
            }
        }

       
        $shipping_amout = $order->getShippingAmount(); //30
        $grandTotal     = $order->getGrandTotal(); //270
        $total_cal      = $grandTotalProd + $shipping_amout;

        if ($total_cal != $grandTotal) {
            if ($total_cal > $grandTotal) {
                $diff          = ($shipping_amout + $grandTotalProd) - $grandTotal;
                $return_values = array(
                    "shipping" => number_format($shipping_amout, 2, '', ''),
                    "discount" => number_format($diff, 2, '', ''),
                    "addition" => 0,
                );
            } else {
                $diff          = $grandTotal - $shipping_amout - $grandTotalProd;
                $return_values = array(
                    "shipping" => number_format($shipping_amout, 2, '', ''),
                    "discount" => 0,
                    "addition" => number_format($diff, 2, '', '')
                );
            }
        } else {
            $return_values = array(
                "shipping" => number_format($shipping_amout, 2, '', ''),
                "discount" => 0,
                "addition" => 0
            );
        }
        return $return_values;
    }
    public function getListaProdutos($order)
    {
        $items     = $order->getallvisibleitems();
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
    public function setDataMoip($order)
    {
        $id_proprio = $order->getIncrementId();
      
        
        if ($order->getShippingAddress()) {
            $a = $order->getShippingAddress();
            $b = $order->getBillingAddress();
            $this->generateLog($b->Debug(), 'MOIP_OrderDebug.log');
            
        } else {
            $a = $b = $order->getBillingAddress();
            $this->generateLog($b->Debug(), 'MOIP_OrderDebug.log');
        }
        $email         = $order->getCustomerEmail();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $cep           = substr(preg_replace("/[^0-9]/", "", $b->getPostcode()) . '00000000', 0, 8);
        $billing_cep   = substr(preg_replace("/[^0-9]/", "", $a->getPostcode()) . '00000000', 0, 8);
        $dob           = Mage::app()->getLocale()->date($order->getCustomerDob(), null, null, true)->toString('Y-MM-dd');
        if(!$order->getCustomerDob()){
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

        $taxvat        = $order->getCustomerTaxvat();
        $taxvat        = preg_replace("/[^0-9]/", "", $taxvat);
         if(strlen($taxvat) > 11){
            $document_type = "CNPJ";
        } else {
            $document_type = "CPF";
        }

        if($order->getCustomerTipopessoa() == 0 && $order->getCustomerNomefantasia()){
            if(!$a->getCompany()){
                $nome = $order->getCustomerRazaosocial(). " ".$order->getCustomerCnpj();
            } else {
                $nome = $a->getCompany()." ".$order->getCustomerCnpj();
            }
            
            $document_type = "CNPJ";
            $taxvat = $order->getCustomerCnpj();
        } else {
             $nome =  $b->getFirstname() . ' ' . $b->getLastname();
        }
        $website_id    = Mage::app()->getWebsite()->getId();
        $website_name  = Mage::app()->getWebsite()->getName();
        $store_name    = Mage::app()->getStore()->getName();
        $data          = array(
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
        $autida_values = $this->getAuditOrder($order);

        
        $json_order    = array(
            "ownId" => $id_proprio,
            "amount" => array(
                "currency" => "BRL",
                "subtotals" => $autida_values
            ),
            "items" => $this->getListaProdutos($order),
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
            $comissoes = $this->getListaComissoesAvancadas($order);
            $normalize = $this->normalizeComissao($comissoes);
            $array_receivers = array("receivers" => $normalize);
            $json_order = array_merge($json_order, $array_receivers);
        }
        $json_order    = json_encode($json_order);
        $this->generateLog("------ Geração da order #{$id_proprio} ------", 'MOIP_Order.log');
        $this->generateLog("------ Json Enviado da order ------", 'MOIP_Order.log');
        $this->generateLog($json_order, 'MOIP_Order.log');
        return $json_order;
    }

    public function setMoipOrder($json_order)
    {

        
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_TEST."orders/";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_PROD."orders/";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }

        $documento = 'Content-Type: application/json; charset=utf-8';

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $json_order,
          CURLOPT_HTTPHEADER => array(
                                        $header,
                                        $documento
                                    ),
          CURLOPT_USERAGENT => 'MoipMagento/2.0.0'
        ));

        $response = curl_exec($curl);
        $info_curl = curl_getinfo($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->generateLog("------ Resposta de MOIP_Order ------", 'MOIP_OrderError.log');
            $this->generateLog($response, 'MOIP_OrderError.log');
          return $err;
        } else {
            $this->generateLog($header, 'MOIP_Order.log');
            $this->generateLog("------ Resposta de MOIP_Order ------", 'MOIP_Order.log');
            $this->generateLog($response, 'MOIP_Order.log');
            $this->generateLog("------ CurlInfo de MOIP_Order ------", 'MOIP_Order.log');
            $this->generateLog(json_encode($info_curl), 'MOIP_Order.log');
          return json_decode($response, true);
        }
    }

    public function setMoipPayment($json, $IdMoip)
    {
        
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_TEST."orders/{$IdMoip}/payments";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_PROD."orders/{$IdMoip}/payments";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
         $documento = 'Content-Type: application/json; charset=utf-8';

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $json,
          CURLOPT_HTTPHEADER => array(
                                        $header,
                                        $documento
                                    ),
          CURLOPT_USERAGENT => 'MoipMagento/2.0.0'
        ));

        $response = curl_exec($curl);
        $info_curl = curl_getinfo($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->generateLog("------ Resposta de MOIP_Order ------", 'MOIP_OrderError.log');
            $this->generateLog($response, 'MOIP_OrderError.log');
          return $err;
        } else {
            $this->generateLog($header, 'MOIP_Order.log');
             $this->generateLog("------ Post Enviado ------", 'MOIP_Order.log');
            $this->generateLog($json, 'MOIP_Order.log');
            $this->generateLog("------ Resposta de setMoipPayment ------", 'MOIP_Order.log');
            $this->generateLog($response, 'MOIP_Order.log');
            $this->generateLog("------ CurlInfo de setMoipPayment ------", 'MOIP_Order.log');
            $this->generateLog(json_encode($info_curl), 'MOIP_Order.log');
          return json_decode($response, true);
        }
    }

    public function getMoipOrder($moip_order_id)
    {
       
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_TEST."orders/".$moip_order_id;
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_PROD."orders/".$moip_order_id;
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
       $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
                                        $header,
                                        $documento
                                    ),
          CURLOPT_USERAGENT => 'MoipMagento/2.0.0'
        ));

        $response = curl_exec($curl);
        $info_curl = curl_getinfo($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->generateLog("------ Resposta de getMoipOrder ------", 'MOIP_getOrder_error.log');
            $this->generateLog($response, 'MOIP_GetPaymentError.log');
          return $err;
        } else {
            $this->generateLog($header, 'MOIP_GetPayment.log');
            $this->generateLog("------ Resposta de GetPayment ------", 'MOIP_getOrder.log');
            $this->generateLog($response, 'MOIP_getOrder.log');
            $this->generateLog("------ CurlInfo de getMoipOrder ------", 'MOIP_getOrder.log');
            $this->generateLog(json_encode($info_curl), 'MOIP_getOrder.log');
          return json_decode($response, true);
        }
    }

    public function getMoipPayment($moip_order_id)
    {
       
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_TEST."payments/".$moip_order_id;
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_PROD."payments/".$moip_order_id;
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
       $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
                                        $header,
                                        $documento
                                    ),
          CURLOPT_USERAGENT => 'MoipMagento/2.0.0'
        ));

        $response = curl_exec($curl);
        $info_curl = curl_getinfo($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->generateLog("------ Resposta de GetPayment ------", 'MOIP_GetPaymentError.log');
            $this->generateLog($response, 'MOIP_GetPaymentError.log');
          return $err;
        } else {
            $this->generateLog($header, 'MOIP_GetPayment.log');
            $this->generateLog("------ Resposta de GetPayment ------", 'MOIP_GetPayment.log');
            $this->generateLog($response, 'MOIP_GetPayment.log');
            $this->generateLog("------ CurlInfo de GetPayment ------", 'MOIP_GetPayment.log');
            $this->generateLog(json_encode($info_curl), 'MOIP_GetPayment.log');
          return json_decode($response, true);
        }
    }

    public function getRefundMoip($moip_order_id, $amount){

        $post_fields = array('amount' => number_format($amount, 2, '', ''));
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_TEST."orders/".$moip_order_id."/refunds";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_PROD."orders/".$moip_order_id."/refunds";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        
       
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($post_fields),
          CURLOPT_HTTPHEADER => array(
                                        $header,
                                        $documento
                                    ),
          CURLOPT_USERAGENT => 'MoipMagento/2.0.0'
        ));

        $response = curl_exec($curl);
        $info_curl = curl_getinfo($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->generateLog("------ Resposta de RefundMoip ------", 'MOIP_RefundMoipError.log');
            $this->generateLog($response, 'MOIP_RefundMoipError.log');
          return $err;
        } else {
            $this->generateLog($header, 'MOIP_RefundMoip.log');
            $this->generateLog("------ send de RefundMoip ------", 'MOIP_RefundMoip.log');
            $this->generateLog(json_encode($post_fields), 'MOIP_CaptureMoip.log');
            $this->generateLog("------ Resposta de RefundMoip ------", 'MOIP_RefundMoip.log');
            $this->generateLog($response, 'MOIP_CaptureMoip.log');
            $this->generateLog("------ CurlInfo de RefundMoip ------", 'MOIP_RefundMoip.log');
            $this->generateLog(json_encode($info_curl), 'MOIP_RefundMoip.log');
          return $response;
        }
    }
    
    public function setMoipCapture($moip_order_id){

        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_TEST."payments/".$moip_order_id."/capture";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_PROD."payments/".$moip_order_id."/capture";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
       $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "",
          CURLOPT_HTTPHEADER => array(
                                        $header,
                                        $documento
                                    ),
          CURLOPT_USERAGENT => 'MoipMagento/2.0.0'
        ));

        $response = curl_exec($curl);
        $info_curl = curl_getinfo($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->generateLog("------ Resposta de CaptureMoip ------", 'MOIP_CaptureMoipError.log');
            $this->generateLog($response, 'MOIP_CaptureMoipError.log');
          return $err;
        } else {
            $this->generateLog($header, 'MOIP_CaptureMoip.log');
            $this->generateLog("------ Resposta de CaptureMoip ------", 'MOIP_CaptureMoip.log');
            $this->generateLog($response, 'MOIP_CaptureMoip.log');
            $this->generateLog("------ CurlInfo de CaptureMoip ------", 'MOIP_CaptureMoip.log');
            $this->generateLog(json_encode($info_curl), 'MOIP_CaptureMoip.log');
          return json_decode($response);
        }
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
    public function setJsonCc($info, $order)
    {
        $additionaldata = unserialize($info->getAdditionalData());

        $dob           = Mage::app()->getLocale()->date($order->getCustomerDob(), null, null, false)->toString('Y-MM-dd');
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
        
        $b = $order->getBillingAddress();        
        $dob = $dob_year."-".$dob_month."-".$dob_day;
        $ddd  = $this->getNumberOrDDD($b->getTelephone(), true);
        $telefone = $this->getNumberOrDDD($b->getTelephone());
        if ($additionaldata['use_cofre'] == 0) {
            $json = array(
                "installmentCount" => $additionaldata['installmentcount_moip'],
                "statementDescriptor" => substr(Mage::getStoreConfig('payment/moip_transparente_standard/apelido'), 0, 13),
                
                "fundingInstrument" => array(
                    "method" => "CREDIT_CARD",
                    "creditCard" => array(
                        "store" => (bool)$additionaldata['save_card'],
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
                ),
                "device" => array(
                    "device" => $order->getRemoteIp(),
                    "userAgent" => Mage::helper('core/http')->getHttpUserAgent()
                    )
            );
        } elseif ($additionaldata['use_cofre'] == 1) {
            $json = array(
                "installmentCount" => $additionaldata['installmentcountcofre_moip'],
                "statementDescriptor" => substr(Mage::getStoreConfig('payment/moip_transparente_standard/apelido'), 0, 13),
                "fundingInstrument" => array(
                    "method" => "CREDIT_CARD",
                    "creditCard" => array(
                        "store" => (bool)$additionaldata['save_card'],
                        "id" => $additionaldata['credit_card_cofre_nb'],
                        "cvc" => $additionaldata['credit_card_ccv']
                    )
                ),
                "device" => array(
                    "device" => $order->getRemoteIp(),
                    "userAgent" => Mage::helper('core/http')->getHttpUserAgent()
                    )
            );
        }
        $json = json_encode($json);
        return $json;
    }



    public function setJsonBoleto()
    {
        
        $NDias = Mage::getStoreConfig('payment/moip_boleto/vcmentoboleto');
        $diasUteis = Mage::getStoreConfig('payment/moip_boleto/vcmentoboleto_diasuteis');


        $json           = array(
            "statementDescriptor" => substr(Mage::getStoreConfig('payment/moip_transparente_standard/apelido'), 0, 13),
            "fundingInstrument" => array(
                "method" => "BOLETO",
                "boleto" => array(
                    "expirationDate" => $this->getDataVencimento($NDias, $diasUteis),
                    "instructionLines" => array(
                        "first" => "Pagamento do pedido na loja: " . Mage::getStoreConfig('payment/moip_transparente_standard/apelido'),
                        "second" => "Não Receber após o Vencimento",
                        "third" => "+ Info em: " . Mage::getBaseUrl()
                    )
                )
            )
        );

        $json           = json_encode($json);
        return $json;
    }
    public function setJsonTef($info)
    {
        $additionaldata = unserialize($info->getAdditionalData());
        $NDias = Mage::getStoreConfig('payment/moip_boleto/vcmentotef');
        $diasUteis = Mage::getStoreConfig('payment/moip_boleto/vcmentotef_diasuteis');
        $json           = array(
            "statementDescriptor" => substr(Mage::getStoreConfig('payment/moip_transparente_standard/apelido'), 0, 13),
            "fundingInstrument" => array(
                "method" => "ONLINE_BANK_DEBIT",
                "onlineBankDebit" => array(
                    "bankNumber" => $additionaldata['moip_tef_banknumber'],
                    "expirationDate" => $this->getDataVencimento($NDias, $diasUteis),
                    "returnUri" => Mage::getBaseUrl()
                )
            )
        );
        $json           = json_encode($json);
        return $json;
    }
    public function getDataVencimento($NDias, $diasUteis)
    {
        $DataAct = date("Ymd");
        $d       = new DateTime($DataAct);
        $t       = $d->getTimestamp();
        if ((bool) $diasUteis) {
            for ($i = 0; $i < $NDias; $i++) {
                $addDay = 86400;
                $nextDay = date('w', ($t + $addDay));
                if ($nextDay == 0 || $nextDay == 6) {
                    $i--;
                }
                $t = $t + $addDay;
            }
        }else {
            $t += 86400 * $NDias;
        }
        $d->setTimestamp($t);
        return $d->format('Y-m-d');
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

