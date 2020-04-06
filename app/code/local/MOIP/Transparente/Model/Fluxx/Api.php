<?php
class MOIP_Transparente_Model_Fluxx_Api 
{

    const ENDPOINT_FLUXX_TEST         = "https://sandbox.fluxx.com.br/v1/";
    const ENDPOINT_FLUXX_PROD         = "https://api.fluxx.com.br/v1/";
    const ACCOUNT_TEST                = "https://sandbox.fluxx.com.br/";
    const ACCOUNT_PROD                = "https://www.fluxx.com.br/";
    
    public function getApiWirecard()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

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
                    "shipping" => (int)number_format($shipping_amout, 2, '', ''),
                    "discount" => (int)number_format($diff, 2, '', ''),
                    "addition" => (int)0,
                    "items"    => (int)number_format($grandTotalProd, 2, '', ''),
                );
            } else {
                $diff          = $grandTotal - $shipping_amout - $grandTotalProd;
                $return_values = array(
                    "shipping" => (int)number_format($shipping_amout, 2, '', ''),
                    "discount" => (int)0,
                    "addition" => (int)number_format($diff, 2, '', ''),
                    "items"    => (int)number_format($grandTotalProd, 2, '', ''),
                );
            }
        } else {
            $return_values = array(
                "shipping" => (int)number_format($shipping_amout, 2, '', ''),
                "discount" => (int)0,
                "addition" => (int)0,
                "items"    => (int)number_format($grandTotalProd, 2, '', ''),
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
                    'price' => (int)number_format($item->getPrice(), 2, '', '')
                );
            }
        }
        return $produtos;
    }

    public function setDataFluxx($order, $wirecard)
    {
        $id_proprio = $order->getIncrementId();
      
        
        if ($order->getShippingAddress()) {
            $a = $order->getShippingAddress();
            $b = $order->getBillingAddress();
           
        } else {
            $a = $b = $order->getBillingAddress();
            
        }
        $email         = $order->getCustomerEmail();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $cep           = substr(preg_replace("/[^0-9]/", "", $b->getPostcode()) . '00000000', 0, 8);
        $billing_cep   = substr(preg_replace("/[^0-9]/", "", $a->getPostcode()) . '00000000', 0, 8);
        $dob           = Mage::app()->getLocale()->date($order->getCustomerDob(), null, null, true)->toString('Y-MM-dd');
        if (!$order->getCustomerDob()) {
            $dob = date('Y-m-d', strtotime($dob . ' -1 day'));
        }
        $dob           = explode('-', $dob);
        $dob_day = $dob[2];
        if (is_null($dob_day)) {
            $dob_day = 01;
        }
        $dob_month = $dob[1];
        if (is_null($dob_month)) {
            $dob_month = 01;
        }
        $dob_year = $dob[0];
        if ($dob_year < 1900) {
            $dob_year += 1900;
        }
        $dob = $dob_year."-".$dob_month."-".$dob_day;

        $taxvat        = $order->getCustomerTaxvat();
        $taxvat        = preg_replace("/[^0-9]/", "", $taxvat);

        if (strlen($taxvat) > 11) {
            $document_type = "CNPJ";
        } else {
            $document_type = "CPF";
        }

        if ($order->getCustomer()->getCnpj()) {

            if (!$a->getCompany()) {
                $nome = $order->getCustomerRazaosocial(). " - ".$order->getCustomerCnpj();
            } else {
                $nome = $a->getCompany()." - ".$order->getCustomer()->getCnpj();
            }

            if($document_type != "CNPJ"){
                $taxvat = $order->getCustomer()->getCnpj();
                $document_type = "CNPJ";  
            }
            
        } else {
            $nome =  $b->getFirstname() . ' ' . $b->getLastname();
        }
      

        $website_id    = Mage::app()->getWebsite()->getId();
        $website_name  = Mage::app()->getWebsite()->getName();
        $store_name    = Mage::app()->getStore()->getName();
        $data          = array(
            'nome' => $nome,
            'email' => strtolower($email),
            'ddd' => (int)$this->getNumberOrDDD($b->getTelephone(), true),
            'telefone' => (int)$this->getNumberOrDDD($b->getTelephone()),
            'mobile' => (int)$this->getNumberOrDDD($b->getFax()),
            'mobile_ddd' => (int)$this->getNumberOrDDD($b->getFax(), true),
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
            'celular' => (int)$this->getNumberOrDDD($b->getFax(), true) . '' . $this->getNumberOrDDD($b->getFax()),
            'sexo' => '',
            'data_nascimento' => $dob,
            'frete' => number_format($b->getShippingAmount(), 2, '', '')
        );
        $autida_values = $this->getAuditOrder($order);
        $validacao = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
        $confirmation_url = Mage::getUrl('Transparente/Fluxx/Notify/'.'validacao/'.$validacao.'/');
        $addressesBilling[] = array(
                    "street" => $data['billing_logradouro'],
                    "streetNumber" => $data['billing_numero'],
                    "complement" => $data['billing_complemento'],
                    "district" => $data['billing_bairro'],
                    "city" => $data['billing_cidade'],
                    "state" => $data['billing_estado'],
                    "country" => "BRA",
                    "zipCode" => $data['billing_cep']
                );
        $json_order    = array(
            "wirecardId" => $wirecard,
            "ownId" => $id_proprio,
            "amount" => array(
                "total" => (int)number_format($order->getGrandTotal(), 2, '', ''),
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
                    "countryCode" => 55,
                    "areaCode" => $data['ddd'],
                    "number" => $data['telefone']
                ),
                "mobile" => array(
                    "countryCode" => 55,
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
                "addresses" => $addressesBilling,
                
            ),
            "merchant" => array(
                    "confirmationUrl" => $confirmation_url,
                    "userConfirmationAction" => "POST",
                    "name" => substr(Mage::getStoreConfig('payment/moip_transparente_standard/apelido'), 0, 13)
                )
           
           
        );
        
        $json_order    = json_encode($json_order);
        $this->generateLog("------ Geração da order #{$id_proprio} ------", 'Fluxx_Order.log');
        $this->generateLog("------ Json Enviado da order ------", 'Fluxx_Order.log');
        $this->generateLog($json_order, 'Fluxx_Order.log');
        return $json_order;
    }

    public function getPaymentInWirecard($payment_id){
        $documento = 'Content-Type: application/json; charset=utf-8';
        $wirecard_base_url  = $this->getApiWirecard()->getEnvirommentUrl();
        $url                = $wirecard_base_url."payments/".$payment_id;
        $wirecard_header  = $this->getApiWirecard()->getHeaderAuthorization();
           
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_SSLVERSION => 6,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 4,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
                                        $wirecard_header,
                                        $documento
                                    ),
          CURLOPT_USERAGENT => 'MoipMagento/2.0.0'
        ));

        $response = curl_exec($curl);
        $info_curl = curl_getinfo($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->generateLog("------ Resposta de GetPayment ------", 'Fluxx_Get_Moip_Order.log');
            $this->generateLog($info_curl, 'Fluxx_Get_Moip_Order.log');
            $this->generateLog($response, 'Fluxx_Get_Moip_Order.log');
            return $err;
        } else {
            $this->generateLog($header, 'MOIP_Order.log');
            $this->generateLog("------ Resposta de GetPayment ------", 'Fluxx_Get_Moip_Order.log');
            $this->generateLog($response, 'Fluxx_Get_Moip_Order.log');
            $this->generateLog("------ CurlInfo de GetPayment ------", 'Fluxx_Get_Moip_Order.log');
            $this->generateLog(json_encode($info_curl), 'Fluxx_Get_Moip_Order.log');
            return json_decode($response, true);
        }
    }

    public function setDataPaymentCancelled($data, $order_id){
        if($data['cancellationDetails']['cancelledBy'] == "ACQUIRER"){
            $data['cancellationDetails']['code'] = (int)$data['cancellationDetails']['code'];

            // unset($data['cancellationDetails']['device']['geolocation']);
            
            $toJson = array("wirecardPayId" => $data['id'], "ownId" => $order_id, "amount" => array("total" => $data['amount']['total']),
            "status" => "CANCELLED", "cancellationDetails" => $data['cancellationDetails'], "createdAt" => $data['createdAt'], "updatedAt" => $data['updatedAt'], "device" => array("ip" => $data['device']['ip'], "userAgent" => $data['device']['userAgent']));
            return json_encode($toJson);
        }
        return false;
        
    }

    public function setDataForSendBoleto($info, $order_id) {
        $toJson = array("wirecardId" => $info['moip_order_id'], "ownId" => $order_id, "boleto" => array("expirationDate" =>  $info['expiration_date'], "lineCode" => preg_replace("/[^0-9]/", "", $info['line_code'])));
        return json_encode($toJson);
    }

    public function sendBoleto($json, $fluxxId){
        if (Mage::getStoreConfig('payment/moip_fluxx/ambiente') == "teste") {
            $url    = self::ENDPOINT_FLUXX_TEST."checkout/".$fluxxId;
            $user  =  Mage::getStoreConfig('payment/moip_fluxx/user_dev');
            $pass  = Mage::getStoreConfig('payment/moip_fluxx/pass_dev');
            $header = "Authorization: Basic " . base64_encode($user . ":" . $pass);
        } else {
            $url    = self::ENDPOINT_FLUXX_PROD."checkout/".$fluxxId;
            $user  =  Mage::getStoreConfig('payment/moip_fluxx/user_prod');
            $pass  = Mage::getStoreConfig('payment/moip_fluxx/pass_prod');
            $header = "Authorization: Basic " . base64_encode($user . ":" . $pass);
        }

        $documento = 'Content-Type: application/json; charset=utf-8';

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_SSLVERSION => 6,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 4,
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
            $this->generateLog("------ Resposta de Erro Fluxx - Metodo Envia Boleto ------", 'Fluxx_Payment.log');
            $this->generateLog($json, 'Fluxx_Payment.log');
            $this->generateLog($response, 'Fluxx_Payment.log');
            $this->generateLog($err, 'Fluxx_Payment.log');
            $this->generateLog($info_curl, 'Fluxx_Payment.log');
            return $err;
        } else {
            $this->generateLog($header, 'Fluxx_Payment.log');
            $this->generateLog("------ Fluxx - Metodo Envia Boleto ------", 'Fluxx_Payment.log');
            $this->generateLog("------ Send Data ------", 'Fluxx_Payment.log');
            $this->generateLog($json, 'Fluxx_Payment.log');
            $this->generateLog("------ Resposta ------", 'Fluxx_Payment.log');
            $this->generateLog($response, 'Fluxx_Payment.log');
            $this->generateLog("------ CurlInfo  ------", 'Fluxx_Payment.log');
            $this->generateLog(json_encode($info_curl), 'Fluxx_Payment.log');
            return json_decode($response, true);
        }
    }
    public function setFluxxInitOrderByMoip($json_order)
    {
        if (Mage::getStoreConfig('payment/moip_fluxx/ambiente') == "teste") {
            $url    = self::ENDPOINT_FLUXX_TEST."checkout";
            $user  =  Mage::getStoreConfig('payment/moip_fluxx/user_dev');
            $pass  = Mage::getStoreConfig('payment/moip_fluxx/pass_dev');
            $header = "Authorization: Basic " . base64_encode($user . ":" . $pass);
        } else {
            $url    = self::ENDPOINT_FLUXX_PROD."checkout";
            $user  =  Mage::getStoreConfig('payment/moip_fluxx/user_prod');
            $pass  = Mage::getStoreConfig('payment/moip_fluxx/pass_prod');
            $header = "Authorization: Basic " . base64_encode($user . ":" . $pass);
        }

        $documento = 'Content-Type: application/json; charset=utf-8';

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_SSLVERSION => 6,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 4,
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
            $this->generateLog("------ Resposta de Erro Fluxx - Metodo INICIAR PRÉ-APROVAÇÃO DE CRÉDITO ------", 'Fluxx_Order.log');
            $this->generateLog($json_order, 'Fluxx_Order.log');
            $this->generateLog($response, 'Fluxx_Order.log');
            $this->generateLog($err, 'Fluxx_Order.log');
            $this->generateLog($info_curl, 'Fluxx_Order.log');
            return $err;
        } else {
            $this->generateLog($header, 'Fluxx_Order.log');
            $this->generateLog("------ Fluxx - Metodo INICIAR PRÉ-APROVAÇÃO DE CRÉDITO ------", 'Fluxx_Order.log');
            $this->generateLog("------ Send Data ------", 'Fluxx_Order.log');
            $this->generateLog($json_order, 'Fluxx_Order.log');
            $this->generateLog("------ Resposta ------", 'Fluxx_Order.log');
            $this->generateLog($response, 'Fluxx_Order.log');
            $this->generateLog("------ CurlInfo  ------", 'Fluxx_Order.log');
            $this->generateLog(json_encode($info_curl), 'Fluxx_Order.log');
            return json_decode($response, true);
        }
    }

    public function sendPaymentDeniedFluxx($json, $IdMoip)
    {
        if (Mage::getStoreConfig('payment/moip_fluxx/ambiente') == "teste") {
            $url    = self::ENDPOINT_FLUXX_TEST."orders/wirecard/".$IdMoip;
            $user  =  Mage::getStoreConfig('payment/moip_fluxx/user_dev');
            $pass  = Mage::getStoreConfig('payment/moip_fluxx/pass_dev');
            $header = "Authorization: Basic " . base64_encode($user . ":" . $pass);
        } else {
            $url    = self::ENDPOINT_FLUXX_PROD."orders/wirecard/".$IdMoip;
            $user  =  Mage::getStoreConfig('payment/moip_fluxx/user_prod');
            $pass  = Mage::getStoreConfig('payment/moip_fluxx/pass_prod');
            $header = "Authorization: Basic " . base64_encode($user . ":" . $pass);
        }

        $documento = 'Content-Type: application/json; charset=utf-8';

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_SSLVERSION => 6,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 4,
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
            $this->generateLog("------ Fluxx - Metodo CRIAR PAGAMENTO ------", 'Fluxx_Order.log');
            $this->generateLog("------ Error ------", 'Fluxx_Order.log');
            $this->generateLog($err, 'Fluxx_Order.log');
            $this->generateLog("------ Send Data ------", 'Fluxx_Order.log');
            $this->generateLog($json, 'Fluxx_Order.log');
            $this->generateLog("------ CurlInfo de CRIAR PAGAMENTO ------", 'Fluxx_Order.log');
            $this->generateLog($info_curl, 'Fluxx_Order.log');
            return $err;
        } else {
            $this->generateLog($header, 'Fluxx_Order.log');
            $this->generateLog("------ Fluxx - Metodo CRIAR PAGAMENTO ------", 'Fluxx_Order.log');
            $this->generateLog("------ Send Data ------", 'Fluxx_Order.log');
            $this->generateLog($json, 'Fluxx_Order.log');
            $this->generateLog("------ Resposta de CRIAR PAGAMENTO ------", 'Fluxx_Order.log');
            $this->generateLog($response, 'Fluxx_Order.log');
            $this->generateLog("------ CurlInfo de CRIAR PAGAMENTO ------", 'Fluxx_Order.log');
            $this->generateLog(json_encode($info_curl), 'Fluxx_Order.log');
            return json_decode($info_curl, true);
        }
    }

    

    public function getNumEndereco($endereco, $enderecob)
    {
        $numEnderecoDefault = '0';
        if (!$endereco) {
            $endereco = $enderecob;
        } else {
            $endereco = $endereco;
        }
        $numEndereco = trim(preg_replace("/[^0-9]/", "", $endereco));
        if ($numEndereco) {
            return ($numEndereco);
        } else {
            return ($numEnderecoDefault);
        }
    }
    public function getPosSeparador($endereco)
    {
        $posSeparador = strpos($endereco, ',');
        if ($posSeparador === false) {
            $posSeparador = strpos($endereco, '-');
        }
        return ($posSeparador);
    }
    public function getNumberOrDDD($param_telefone, $param_ddd = false)
    {
        $cust_ddd       = '11';
        $cust_telephone = preg_replace("/[^0-9]/", "", $param_telefone);
        if (strlen($cust_telephone) == 11) {
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
        } else {
            $t += 86400 * $NDias;
        }
        $d->setTimestamp($t);
        return $d->format('Y-m-d');
    }
    
    public function generateLog($variable, $name_log)
    {
        if (Mage::getSingleton('transparente/standard')->getConfigData('log') == 1) {
            $dir_log = Mage::getBaseDir('var').'/log/MOIP/';
            if (!file_exists($dir_log)) {
                mkdir($dir_log, 0755, true);
            }

            Mage::log($variable, null, 'MOIP/'.$name_log, true);
        } else {
        }
    }
}
