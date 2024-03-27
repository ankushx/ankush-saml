<?php

namespace MiniOrange\SP\Helper;

use MiniOrange\SP\Helper\SPConstants;

/**
 * This class denotes all the cURL related functions.
 */
class Curl
{

    public static function create_customer($email, $company, $password, $phone = '', $first_name = '', $last_name = '')
    {

        $url          = SPConstants::HOSTNAME . '/moas/rest/customer/add';
        $customerKey = SPConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey      = SPConstants::DEFAULT_API_KEY;
        $fields =  [
                'companyName'      => $company,
                'areaOfInterest' => SPConstants::AREA_OF_INTEREST,
                'firstname'      => $first_name,
                'lastname'          => $last_name,
                'email'          => $email,
                'phone'          => $phone,
                'password'          => $password,
        ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function get_customer_key($email, $password)
    {
        $url          = SPConstants::HOSTNAME. "/moas/rest/customer/key";
        $customerKey = SPConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey      = SPConstants::DEFAULT_API_KEY;
        $fields =  [
                    'email'     => $email,
                    'password'  => $password
                ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function check_customer($email)
    {
        $url          = SPConstants::HOSTNAME . "/moas/rest/customer/check-if-exists";
        $customerKey = SPConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey      = SPConstants::DEFAULT_API_KEY;
        $fields = [
                    'email'     => $email,
                ];
        $authHeader  = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function submit_contact_us(
        $q_email,
        $q_phone,
        $query
    ) {
        $url              = SPConstants::HOSTNAME . "/moas/rest/customer/contact-us";
        $query          = '['.SPConstants::AREA_OF_INTEREST.']: ' . $query;
        $customerKey     = SPConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey          = SPConstants::DEFAULT_API_KEY;
        $fields = [
                    'email'     => $q_email,
                    'phone'        => $q_phone,
                    'query'        => $query,
                    'ccEmail'        => 'magentosupport@xecurify.com'
                ];
        $authHeader  = self::createAuthHeader($customerKey, $apiKey);
        $response      = self::callAPI($url, $fields, $authHeader);
        return true;
    }
    public static function submit_to_magento_team(
        $q_email,
        $sub,
        $values,
        $magentoVersion
    ) {
        $url =  SPConstants::HOSTNAME . "/moas/api/notify/send";
        $customerKey =  SPConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey =  SPConstants::DEFAULT_API_KEY;

        $fields1 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "nitesh.pamnani@xecurify.com",
                'bccEmail'      => "rutuja.sonawane@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "nitesh.pamnani@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Magento 2.0 SAML SP free Plugin $sub : $q_email",
                'content'       => " Admin Email = $q_email, First name= $values[0],last Name = $values[1], Site= $values[2], Magento Version = $magentoVersion"
            ),
        );

        $fields2 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "rushikesh.nikam@xecurify.com",
                'bccEmail'      => "raj@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "rushikesh.nikam@xecurify.com",
                'toName'        => "Rushikesh",
                'subject'       => "Magento 2.0 SAML SP free Plugin $sub : $q_email",
                'content'       => " Admin Email = $q_email, First name= $values[0],last Name = $values[1], Site= $values[2], Magento Version = $magentoVersion"
            ),
        );

        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $field_string1 = json_encode($fields1);
        $field_string2 = json_encode($fields2);
        $response1 = self::callAPI($url, $fields1, $authHeader);
        $response2 = self::callAPI($url, $fields2, $authHeader);
        return true;
    }

    public static function submit_to_magento_team_core_config_data(
        $q_email,
        $sub,
        $content,
        $values
    ) {
        $url =  SPConstants::HOSTNAME . "/moas/api/notify/send";
        $customerKey =  SPConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey =  SPConstants::DEFAULT_API_KEY;

        $fields1 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "nitesh.pamnani@xecurify.com",
                'bccEmail'      => "rutuja.sonawane@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "nitesh.pamnani@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Magento 2.0 SAML SP free Plugin $sub : $q_email",
                'content'       => " $content ,
                                     Admin Email = $q_email , Idp Name: $values[0] , Login Binding Type: $values[1] , Login URL:$values[2] ,issuer:$values[3] , X509 Certificate: $values[4] , Customer link: $values[5] ,
                                     Response signed: $values[6] , assertion signed: $values[7]"
            ),
        );

        $fields2 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "rushikesh.nikam@xecurify.com",
                'bccEmail'      => "raj@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "rushikesh.nikam@xecurify.com",
                'toName'        => "Rushikesh",
                'subject'       => "Magento 2.0 SAML SP free Plugin $sub : $q_email",
                'content'       => " $content ,
                                     Admin Email = $q_email , Idp Name: $values[0] , Login Binding Type: $values[1] , Login URL:$values[2] ,issuer:$values[3] , X509 Certificate: $values[4] , Customer link: $values[5] ,
                                     Response signed: $values[6] , assertion signed: $values[7]"
            ),
        );

        $field_string1 = json_encode($fields1);
        $field_string2 = json_encode($fields2);
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response1 = self::callAPI($url, $fields1, $authHeader);
        $response2 = self::callAPI($url, $fields2, $authHeader);
        return true;
    }

    public static function submit_to_magento_team_autocreate_limit_exceeded($q_email, $site, $magentoVersion) {
        $url =  SPConstants::HOSTNAME . "/moas/api/notify/send";
        $customerKey =  SPConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey =  SPConstants::DEFAULT_API_KEY;

        $fields1 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "nitesh.pamnani@xecurify.com",
                'bccEmail'      => "rutuja.sonawane@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "nitesh.pamnani@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Magento 2.0 SAML SP free Plugin AUTOCREATE USER LIMIT EXEEDED",
                'content'       => "Admin User: $q_email, Site: $site, Magento Version = $magentoVersion"
            ),
        );

        $fields2 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "rushikesh.nikam@xecurify.com",
                'bccEmail'      => "raj@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "rushikesh.nikam@xecurify.com",
                'toName'        => "Rushikesh",
                'subject'       => "Magento 2.0 SAML SP free Plugin AUTOCREATE USER LIMIT EXEEDED",
                'content'       => "Admin User: $q_email, Site: $site, Magento Version = $magentoVersion"
            ),
        );

        $field_string1 = json_encode($fields1);
        $field_string2 = json_encode($fields2);
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response1 = self::callAPI($url, $fields1, $authHeader);
        $response2 = self::callAPI($url, $fields2, $authHeader);
        return true;
    }

    public static function forgot_password($email, $customerKey, $apiKey)
    {
        $url          = SPConstants::HOSTNAME . '/moas/rest/customer/password-reset';

        $fields      = [
                'email' => $email
        ];

        $authHeader  = self::createAuthHeader($customerKey, $apiKey);
        $response    = self::callAPI($url, $fields, $authHeader);
        return $response;
    }


    public static function check_customer_ln($customerKey, $apiKey)
    {
        $url = SPConstants::HOSTNAME . '/moas/rest/customer/license';
        $fields = [
                'customerId' => $customerKey,
                'applicationName' => SPConstants::APPLICATION_NAME,
                'licenseType' => !MoUtility::micr() ? 'DEMO' : 'PREMIUM',
        ];

        $authHeader  = self::createAuthHeader($customerKey, $apiKey);
        $response    = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    private static function createAuthHeader($customerKey, $apiKey)
    {
        $currentTimestampInMillis = round(microtime(true) * 1000);
        $currentTimestampInMillis = number_format($currentTimestampInMillis, 0, '', '');

        $stringToHash = $customerKey . $currentTimestampInMillis . $apiKey;
        $authHeader = hash("sha512", $stringToHash);

        $header =  [
                "Content-Type: application/json",
                "Customer-Key: $customerKey",
                "Timestamp: $currentTimestampInMillis",
                "Authorization: $authHeader"
        ];
        return $header;
    }

    private static function callAPI($url, $jsonData = [], $headers = ["Content-Type: application/json"])
    {
        // Custom functionality written to be in tune with Mangento2 coding standards.
        $curl = new MoCurl();
        $options = [
            'CURLOPT_FOLLOWLOCATION'=> true,
            'CURLOPT_ENCODING'=> "",
            'CURLOPT_RETURNTRANSFER'=> true,
            'CURLOPT_AUTOREFERER'=> true,
            'CURLOPT_TIMEOUT'=> 0,
            'CURLOPT_MAXREDIRS'=> 10
        ];
        $method = !empty($jsonData) ? 'POST' : 'GET';
        $curl->setConfig($options);
        $curl->write($method, $url, '1.1', $headers, !empty($jsonData) ? json_encode($jsonData) : "");
        $content = $curl->read();
        $curl->close();
        return $content;
    }
}
