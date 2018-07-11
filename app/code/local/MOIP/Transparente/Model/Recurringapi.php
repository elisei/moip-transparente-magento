<?php
class Moip_Transparente_Model_Recurringapi
{
    
    const ENDPOINT_RECURRING_TEST = "https://sandbox.moip.com.br/assinaturas/v1/";
    const ENDPOINT_RECURRING_PROD = "https://api.moip.com.br/assinaturas/v1/";
    
    
    
    public function getToken()
    {
        $configValue = Mage::getStoreConfig('payment/moip_recurring/api_id');
        $api_token   = Mage::helper('core')->decrypt($configValue);
        return $api_token;
    }
    
    public function getKey()
    {
        $configValue = Mage::getStoreConfig('payment/moip_recurring/api_key');
        $api_key     = Mage::helper('core')->decrypt($configValue);
        return $api_key;
    }
    
    public function searchCustomersPlans($profile, $payments)
    {
        $quote             = $payments->getQuote();
        $customer          = $quote->getCustomer();
        $customer_plans_id = $customer->getId();
       
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_RECURRING_TEST . "customers";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_RECURRING_PROD . "customers";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        $result = array();
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl    = curl_getinfo($ch);
        curl_close($ch);
        $decode_user_plans = json_decode($responseBody, true);
        $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Subscrition.txt');
        $this->generateLog("search", 'MOIP_Subscrition.txt');
        $this->generateLog($customer_plans_id, 'MOIP_Subscrition.txt');
       // $this->generateLog($responseBody, 'MOIP_Subscrition.txt');
        foreach ($decode_user_plans as $key => $value) {
            foreach ($value as $key => $value) {
                $plans_code[] = $value['code'];
            }
        }
        if (in_array($customer_plans_id, $plans_code))
            return $this->generateUpdateCustomer($profile, $payments);
        else
            return $this->setCustomersPlans($profile, $payments);
    }
    
    public function setCustomersPlans($profile, $payments)
    {
        $quote          = $payments->getQuote();
        $customer       = $quote->getCustomer();
        $additionaldata = unserialize($payments->getAdditionalData());
        if ($quote->getShippingAddress()) {
            $address = $quote->getShippingAddress();
        } else {
            $address = $quote->getBillingAddress();
        }
        $birthdate   = $quote->getCustomerDob();
        $day         = Mage::app()->getLocale()->date($quote->getCustomerDob(), null, null, false)->toString('dd');
        $month       = Mage::app()->getLocale()->date($quote->getCustomerDob(), null, null, false)->toString('MM');
        $year        = Mage::app()->getLocale()->date($quote->getCustomerDob(), null, null, false)->toString('Y');
        $method_type = $payments->getQuote()->getPayment()->getMethodInstance()->getCode();
        
        if ($method_type == 'moip_boletorecurring') {
            $customer_plans = array(
                'code' => $customer->getId(),
                'fullname' => $customer->getName(),
                'email' => strtolower(Mage::getSingleton('customer/session')->getCustomer()->getEmail()),
                'cpf' => preg_replace("/[^0-9]/", "", $customer->getTaxvat()),
                'phone_area_code' => $this->getNumberOrDDD($address->getTelephone(), true),
                'phone_number' => $this->getNumberOrDDD($address->getTelephone(), false),
                'birthdate_day' => $day,
                'birthdate_month' => $month,
                'birthdate_year' => $year,
                'address' => array(
                    'street' => $address->getStreet(1),
                    'number' => $this->getNumEndereco($address->getStreet(2), $address->getStreet(1)),
                    'complement' => $address->getStreet(3),
                    'district' => $address->getStreet(4),
                    'city' => $address->getCity(),
                    'state' => strtoupper($address->getRegionCode()),
                    'country' => "BRA",
                    'zipcode' => substr(preg_replace("/[^0-9]/", "", $address->getPostcode()) . '00000000', 0, 8)
                )
                
            );
        } else {
            $customer_plans = array(
                'code' => $customer->getId(),
                'fullname' => $customer->getName(),
                'email' => strtolower(Mage::getSingleton('customer/session')->getCustomer()->getEmail()),
                'cpf' => preg_replace("/[^0-9]/", "", $customer->getTaxvat()),
                'phone_area_code' => $this->getNumberOrDDD($address->getTelephone(), true),
                'phone_number' => $this->getNumberOrDDD($address->getTelephone(), false),
                'birthdate_day' => $day,
                'birthdate_month' => $month,
                'birthdate_year' => $year,
                'address' => array(
                    'street' => $address->getStreet(1),
                    'number' => $this->getNumEndereco($address->getStreet(2), $address->getStreet(1)),
                    'complement' => $address->getStreet(3),
                    'district' => $address->getStreet(4),
                    'city' => $address->getCity(),
                    'state' => strtoupper($address->getRegionCode()),
                    'country' => "BRA",
                    'zipcode' => substr(preg_replace("/[^0-9]/", "", $address->getPostcode()) . '00000000', 0, 8)
                ),
                'billing_info' => array(
                    'credit_card' => array(
                        'holder_name' => $payments->getCcOwner(),
                        'number' => $additionaldata['hash_moip'],
                        'expiration_month' => $payments->getCcExpMonth(),
                        'expiration_year' => $payments->getCcExpYear()
                    )
                )
            );
        }
        
         $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Subscrition.txt');
        $this->generateLog("setCustomersPlans", 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($customer_plans), 'MOIP_Subscrition.txt');
        return $this->createCustomersPlans($customer_plans, $profile, $payments);
    }

    public function updateBillingCard($data, $customer)
    {
        $customer_plans = array(
            
            'credit_card' => array(
                'holder_name' => $data['moip_ccrecurring_owner'],
                'number' => $data['moip_ccrecurring_number'],
                'expiration_month' => $data['moip_ccrecurring_exp_month'],
                'expiration_year' => $data['moip_ccrecurring_exp_year']
            )
        );
        $data           = json_encode($customer_plans);
        $documento      = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_RECURRING_TEST . "customers/{$customer}/billing_infos";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_RECURRING_PROD . "customers/{$customer_id}/billing_infos";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        $result = array();
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl    = curl_getinfo($ch);
        curl_close($ch);
        $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Subscrition.txt');
        $this->generateLog("updateBillingCard", 'MOIP_Subscrition.txt');
        $this->generateLog($header, 'MOIP_Subscrition.txt');
        $this->generateLog($documento, 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($customer_plans), 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($info_curl), 'MOIP_Subscrition.txt');
        $this->generateLog($url, 'MOIP_Subscrition.txt');
        return $responseBody;
        
        
    }

    public function createCustomersPlans($data, $profile, $payments)
    {
        $data      = json_encode($data);
        $documento = 'Content-Type: application/json; charset=utf-8';
        $method_type = $payments->getQuote()->getPayment()->getMethodInstance()->getCode();

        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            if ($method_type == "moip_boletorecurring") {
                $url = self::ENDPOINT_RECURRING_TEST . "customers?new_vault=false";
            } else {
                $url = self::ENDPOINT_RECURRING_TEST . "customers?new_vault=true";
            }
            
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            if ($method_type == "moip_boletorecurring") {
                $url = self::ENDPOINT_RECURRING_PROD . "customers?new_vault=false";
            } else {
                $url = self::ENDPOINT_RECURRING_PROD . "customers?new_vault=true";
            }            
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        $result = array();
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl    = curl_getinfo($ch);
        curl_close($ch);
         $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Subscrition.txt');
        $this->generateLog("createCustomersPlans", 'MOIP_Subscrition.txt');
        $this->generateLog($data, 'MOIP_Subscrition.txt');
        $this->generateLog($responseBody, 'MOIP_Subscrition.txt');
        $this->generateLog($header, 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($info_curl), 'MOIP_Subscrition.txt');
        
        $decode = json_decode($responseBody);
        return $this->generateUpdateCustomer($profile, $payments);
    }
    
    public function generateUpdateCustomer($profile, $payments)
    {
        $quote       = $payments->getQuote();
        $customer    = $quote->getCustomer();
        $customer_id = $customer->getId();
        $method_type = $payments->getQuote()->getPayment()->getMethodInstance()->getCode();
        if ($quote->getShippingAddress()) {
            $address = $quote->getShippingAddress();
        } else {
            $address = $quote->getBillingAddress();
        }
        if ($method_type != 'moip_boletorecurring') {
            $additionaldata = unserialize($payments->getAdditionalData());
            $birthdate      = $quote->getCustomerDob();
            $day            = Mage::app()->getLocale()->date($quote->getCustomerDob(), null, null, false)->toString('dd');
            $month          = Mage::app()->getLocale()->date($quote->getCustomerDob(), null, null, false)->toString('MM');
            $year           = Mage::app()->getLocale()->date($quote->getCustomerDob(), null, null, false)->toString('Y');
            $customer_plans = array(
                
                'credit_card' => array(
                    'holder_name' => $payments->getCcOwner(),
                    'number' => $additionaldata['hash_moip'],
                    'expiration_month' => $payments->getCcExpMonth(),
                    'expiration_year' => $payments->getCcExpYear()
                )
                
            );
            
            $data      = json_encode($customer_plans);
            $documento = 'Content-Type: application/json; charset=utf-8';
            if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
                $url    = self::ENDPOINT_RECURRING_TEST . "customers/{$customer_id}/billing_infos";
                $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
                $header = "Authorization: OAuth " . $oauth;
            } else {
                $url    = self::ENDPOINT_RECURRING_PROD . "customers/{$customer_id}/billing_infos";
                $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
                $header = "Authorization: OAuth " . $oauth;
            }
            $result = array();
            $ch     = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                $header,
                $documento
            ));
            curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
            $responseBody = curl_exec($ch);
            $info_curl    = curl_getinfo($ch);
            curl_close($ch);
            
            $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Subscrition.txt');
            $this->generateLog("generateUpdateCustomer", 'MOIP_Subscrition.txt');
            $this->generateLog($header, 'MOIP_Subscrition.txt');
            $this->generateLog($documento, 'MOIP_Subscrition.txt');
            $this->generateLog(json_encode($customer_plans), 'MOIP_Subscrition.txt');
            $this->generateLog(json_encode($info_curl), 'MOIP_Subscrition.txt');
            $this->generateLog($url, 'MOIP_Subscrition.txt');
        }
        return $this->setCreateSubscriptionsPlans($profile, $payments);
        
    }
    
    public function cancelSubscription($prolie_id)
    {
        #https://sandbox.moip.com.br/assinaturas/v1/subscriptions/{code}/cancel
        
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_RECURRING_TEST . "subscriptions/{$prolie_id}/cancel";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_RECURRING_PROD . "subscriptions/{$prolie_id}/cancel";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl    = curl_getinfo($ch);
        curl_close($ch);
         $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Subscrition.txt');
        $this->generateLog("cancelSubscription", 'MOIP_Subscrition.txt');
        $this->generateLog($url, 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($prolie_id), 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($header), 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($info_curl), 'MOIP_Subscrition.txt');
        
        
        
    }

    
    public function suspendSubscription($prolie_id)
    {
        #https://sandbox.moip.com.br/assinaturas/v1/subscriptions/{code}/cancel
        
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_RECURRING_TEST . "subscriptions/{$prolie_id}/suspend";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_RECURRING_PROD . "subscriptions/{$prolie_id}/suspend";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl    = curl_getinfo($ch);
        curl_close($ch);
         $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Subscrition.txt');
        $this->generateLog("suspendSubscription", 'MOIP_Subscrition.txt');
        $this->generateLog($url, 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($prolie_id), 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($header), 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($info_curl), 'MOIP_Subscrition.txt');
        
        
        
    }
    
    public function activateSubscription($prolie_id)
    {
        #https://sandbox.moip.com.br/assinaturas/v1/subscriptions/{code}/cancel
        
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_RECURRING_TEST . "subscriptions/{$prolie_id}/activate";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_RECURRING_PROD . "subscriptions/{$prolie_id}/activate";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl    = curl_getinfo($ch);
        curl_close($ch);
        $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Subscrition.txt');
        $this->generateLog("activateSubscription", 'MOIP_Subscrition.txt');
        $this->generateLog($url, 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($prolie_id), 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($header), 'MOIP_Subscrition.txt');
        $this->generateLog(json_encode($info_curl), 'MOIP_Subscrition.txt');
        
    }

    public function setCreateSubscriptionsPlans($profile, $payments)
    {
        $quote          = $payments->getQuote();
        $customer       = $quote->getCustomer();
        $customer_plans = $customer->getId();
        $cartItems      = $quote->getAllVisibleItems();
        $method         = $payments->getQuote()->getPayment()->getMethodInstance()->getCode();
        if ($method == 'moip_boletorecurring') {
            $method_type = "BOLETO";
        } else {
            $method_type = "CREDIT_CARD";
        }

        foreach ($cartItems as $item) {
            $code_plans = $item->getSku();
            
            $send_subscription      = array(
                'code' => $profile->getId(),
                'amount' => number_format($profile->getTaxAmount() + $profile->getBillingAmount() + $profile->getShippingAmount(), 2, '', ''),
                "payment_method" => $method_type,
                'plan' => array(
                    'code' => $code_plans
                ),
                'customer' => array(
                    'code' => $customer->getId()
                )
            );
            $send_subscription_json = json_encode($send_subscription);
            $documento              = 'Content-Type: application/json; charset=utf-8';
            if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
                $url    = self::ENDPOINT_RECURRING_TEST . "subscriptions";
                $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
                $header = "Authorization: OAuth " . $oauth;
            } else {
                $url    = self::ENDPOINT_RECURRING_PROD . "subscriptions";
                $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
                $header = "Authorization: OAuth " . $oauth;
            }
            $result = array();
            $ch     = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $send_subscription_json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                $header,
                $documento
            ));
            curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
            $responseBody = curl_exec($ch);
            $info_curl    = curl_getinfo($ch);
            curl_close($ch);
            $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Subscrition.txt');
            $this->generateLog("setCreateSubscriptionsPlans", 'MOIP_Subscrition.txt');
            $this->generateLog($method_type, 'MOIP_Subscrition.txt');
            $this->generateLog($send_subscription_json, 'MOIP_Subscrition.txt');
            $this->generateLog($responseBody, 'MOIP_Subscrition.txt');
            $this->generateLog($header, 'MOIP_Subscrition.txt');
            $this->generateLog(json_encode($info_curl), 'MOIP_Subscrition.txt');
        }
        $this->generateLog($send_subscription_json, 'MOIP_Subscrition.txt');
        return $responseBody;
    }
    
    public function ConsultPlans($data)
    {
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_RECURRING_TEST . "plans";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_RECURRING_PROD . "plans";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        $result = array();
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl    = curl_getinfo($ch);
        curl_close($ch);
        $decode_plans = json_decode($responseBody, true);
        foreach ($decode_plans as $key => $value) {
            foreach ($value as $key => $valueb) {
                $plans_code[] = $valueb['code'];
            }
        }
        if (in_array($data['code'], $plans_code))
            return $this->setUpdatePlans($data);
        else
            return $this->setCreatePlans($data);
    }
    
    public function setCreatePlans($data)
    {
        $data      = json_encode($data);
        $documento = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_RECURRING_TEST . "plans";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_RECURRING_PROD . "plans";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        $result = array();
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl    = curl_getinfo($ch);
        curl_close($ch);
        $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Plans.txt');
        $this->generateLog("setCreatePlans", 'MOIP_Plans.txt');
        $this->generateLog($url, 'MOIP_Plans.txt');
        $this->generateLog($header, 'MOIP_Plans.txt');
        $this->generateLog($data, 'MOIP_Plans.txt');
        $this->generateLog($responseBody, 'MOIP_Plans.txt');
        $this->generateLog(json_encode($info_curl), 'MOIP_Plans.txt');
        
        $decode = json_decode($responseBody, true);
        
        if ($info_curl['http_code'] == 200 || $info_curl['http_code'] == 201) {
            Mage::getSingleton('core/session')->addSuccess('Plano Criado com sucesso');
        } else {
            Mage::getSingleton('core/session')->addError('Não foi possivel criar o plano recorrente, resposta do servidor Moip: ' . $info_curl['http_code'] . " " . $responseBody);
        }
        
        
        return $decode;
    }
    
    
    
    public function setUpdatePlans($data)
    {
        $plans_code = $data['code'];
        $data       = json_encode($data);
        $documento  = 'Content-Type: application/json; charset=utf-8';
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $url    = self::ENDPOINT_RECURRING_TEST . "plans/{$plans_code}";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $header = "Authorization: OAuth " . $oauth;
        } else {
            $url    = self::ENDPOINT_RECURRING_PROD . "plans/{$plans_code}";
            $oauth  = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $header = "Authorization: OAuth " . $oauth;
        }
        $result = array();
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $header,
            $documento
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
        $responseBody = curl_exec($ch);
        $info_curl    = curl_getinfo($ch);
        curl_close($ch);
        $this->generateLog("------- Ambiente - ".Mage::getSingleton('transparente/standard')->getConfigData('ambiente')." -------", 'MOIP_Plans.txt');
        $this->generateLog("setUpdatePlans", 'MOIP_Plans.txt');
        $this->generateLog($url, 'MOIP_Plans.txt');
        $this->generateLog($header, 'MOIP_Plans.txt');
        $this->generateLog($data, 'MOIP_Plans.txt');
        $this->generateLog($responseBody, 'MOIP_Plans.txt');
        $this->generateLog(json_encode($info_curl), 'MOIP_Plans.txt');
        
        $decode = json_decode($responseBody);
        if ($info_curl['http_code'] == 200) {
            Mage::getSingleton('core/session')->addSuccess('Plano Atualizado com sucesso');
        } else {
            Mage::getSingleton('core/session')->addError('Não foi possivel atualizar o plano resposta do servidor Moip: ' . $info_curl['http_code'] . " " . $responseBody);
            
        }
        
        
        
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
    
    public function getNumberOrDDD($param_telefone, $param_ddd = false)
    {
        $cust_ddd       = '11';
        $cust_telephone = preg_replace("/[^0-9]/", "", $param_telefone);
        $st             = strlen($cust_telephone) - 8;
        if ($st > 0) {
            $cust_ddd       = substr($cust_telephone, 0, 2);
            $cust_telephone = substr($cust_telephone, $st, 8);
        }
        if ($param_ddd === false) {
            $retorno = $cust_telephone;
        } else {
            $retorno = $cust_ddd;
        }
        return $retorno;
    }
    
    public function generateLog($variable, $name_log)
    {
        if (Mage::getSingleton('transparente/standard')->getConfigData('log') == 1) {
            $dir_log = Mage::getBaseDir('var') . '/log/MOIP/Assinaturas/';
            
            if (!file_exists($dir_log)) {
                mkdir($dir_log, 0755, true);
            }
            
            Mage::log($variable, null, 'MOIP/Assinaturas/' . $name_log, true);
        } else {
            
        }
    }
}