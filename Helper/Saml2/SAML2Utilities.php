<?php
/**
 * This file is part of miniOrange plugin.
 *
 * miniOrange plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * miniOrange plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with miniOrange plugin.  If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * @todo - This class needs to be modified and optimized.
 */

namespace MiniOrange\SP\Helper\Saml2;

use Exception;
use MiniOrange\SP\Helper\SPZendUtility;
use MiniOrange\SP\Helper\Xmlseclibs\XMLSecEnc;
use MiniOrange\SP\Helper\Xmlseclibs\XMLSecurityKey;
use MiniOrange\SP\Helper\Xmlseclibs\XMLSecurityDSig;
use Magento\Framework\Serialize\SerializerInterface;

class SAML2Utilities
{

    public static function generateID()
    {
        return '_' . self::stringToHex(self::generateRandomBytes(21));
    }

    public static function stringToHex($bytes)
    {
        $ret = '';
        for ($i = 0; $i < strlen($bytes); $i++) {
            $ret .= sprintf('%02x', ord($bytes[$i]));
        }
        return $ret;
    }

    public static function generateRandomBytes($length)
    {
        return openssl_random_pseudo_bytes($length);
    }

    public static function generateTimestamp($instant = null)
    {
        if ($instant === null) {
            $instant = time();
        }
        return gmdate('Y-m-d\TH:i:s\Z', $instant);
    }

    public static function xpQuery(\DomNode$node, $query)
    {
        static $xpCache = null;

        if ($node instanceof \DOMDocument) {
            $doc = $node;
        } else {
            $doc = $node->ownerDocument;
        }

        if ($xpCache === null || !$xpCache->document->isSameNode($doc)) {
            $xpCache = new \DOMXPath($doc);
            $xpCache->registerNamespace('soap-env', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpCache->registerNamespace('saml_protocol', 'urn:oasis:names:tc:SAML:2.0:protocol');
            $xpCache->registerNamespace('saml_assertion', 'urn:oasis:names:tc:SAML:2.0:assertion');
            $xpCache->registerNamespace('saml_metadata', 'urn:oasis:names:tc:SAML:2.0:metadata');
            $xpCache->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
            $xpCache->registerNamespace('xenc', 'http://www.w3.org/2001/04/xmlenc#');
        }

        try {
            $results = $xpCache->query($query, $node);
        } catch (Exception $e) {
            return null;
        }

        $ret = [];
        for ($i = 0; $i < $results->length; $i++) {
            $ret[$i] = $results->item($i);
        }

        return $ret;
    }

    public static function parseNameId(\DOMElement $xml)
    {
        $ret = ['Value' => trim($xml->textContent)];

        foreach (['NameQualifier', 'SPNameQualifier', 'Format'] as $attr) {
            if ($xml->hasAttribute($attr)) {
                $ret[$attr] = $xml->getAttribute($attr);
            }
        }

        return $ret;
    }

    public static function xsDateTimeToTimestamp($time)
    {
        $matches = [];

        // We use a very strict regex to parse the timestamp.
        $regex = '/^(\\d\\d\\d\\d)-(\\d\\d)-(\\d\\d)T(\\d\\d):(\\d\\d):(\\d\\d)(?:\\.\\d+)?Z$/D';
        if (preg_match($regex, $time, $matches) == 0) {
            throw new \Exception("Invalid SAML2 timestamp passed to xsDateTimeToTimestamp: ".$time);
        }

        // Extract the different components of the time from the  matches in the regex.
        // intval will ignore leading zeroes in the string.
        $year   = intval($matches[1]);
        $month  = intval($matches[2]);
        $day    = intval($matches[3]);
        $hour   = intval($matches[4]);
        $minute = intval($matches[5]);
        $second = intval($matches[6]);

        // We use gmmktime because the timestamp will always be given
        //in UTC.
        $ts = gmmktime($hour, $minute, $second, $month, $day, $year);

        return $ts;
    }

    /**
     * Decrypt an encrypted element.
     *
     * This is an internal helper function.
     *
     * @param  DOMElement     $encryptedData The encrypted data.
     * @param  XMLSecurityKey $inputKey      The decryption key.
     * @param  array          &$blacklist    Blacklisted decryption algorithms.
     * @return DOMElement     The decrypted element.
     * @throws Exception
     */
    private static function doDecryptElement(\DOMElement $encryptedData, XMLSecurityKey $inputKey, array &$blacklist)
    {
        $enc = new XMLSecEnc();
        $enc->setNode($encryptedData);

        $enc->type = $encryptedData->getAttribute("Type");
        $symmetricKey = $enc->locateKey($encryptedData);
        if (!$symmetricKey) {
            throw new \Exception('Could not locate key algorithm in encrypted data.');
        }

        $symmetricKeyInfo = $enc->locateKeyInfo($symmetricKey);
        if (!$symmetricKeyInfo) {
            throw new \Exception('Could not locate <dsig:KeyInfo> for the encrypted key.');
        }
        $inputKeyAlgo = $inputKey->getAlgorith();
        if ($symmetricKeyInfo->isEncrypted) {
            $symKeyInfoAlgo = $symmetricKeyInfo->getAlgorith();
            if (in_array($symKeyInfoAlgo, $blacklist, true)) {
                throw new \Exception('Algorithm disabled: ' . var_export($symKeyInfoAlgo, true));
            }
            if ($symKeyInfoAlgo === XMLSecurityKey::RSA_OAEP_MGF1P && $inputKeyAlgo === XMLSecurityKey::RSA_1_5) {
                /*
                 * The RSA key formats are equal, so loading an RSA_1_5 key
                 * into an RSA_OAEP_MGF1P key can be done without problems.
                 * We therefore pretend that the input key is an
                 * RSA_OAEP_MGF1P key.
                 */
                $inputKeyAlgo = XMLSecurityKey::RSA_OAEP_MGF1P;
            }
            /* Make sure that the input key format is the same as the one used to encrypt the key. */
            if ($inputKeyAlgo !== $symKeyInfoAlgo) {
                throw new \Exception('Algorithm mismatch between input key and key used to encrypt ' .
                    ' the symmetric key for the message. Key was: ' .
                    var_export($inputKeyAlgo, true) . '; message was: ' .
                    var_export($symKeyInfoAlgo, true));
            }
            /** @var XMLSecEnc $encKey */
            $encKey = $symmetricKeyInfo->encryptedCtx;
            $symmetricKeyInfo->key = $inputKey->key;
            $keySize = $symmetricKey->getSymmetricKeySize();
            if ($keySize === null) {
                /* To protect against "key oracle" attacks, we need to be able to create a
                 * symmetric key, and for that we need to know the key size.
                 */
                throw new \Exception('Unknown key size for encryption algorithm: ' . var_export($symmetricKey->type, true));
            }
            try {
                $key = $encKey->decryptKey($symmetricKeyInfo);
                if (strlen($key) != $keySize) {
                    throw new \Exception('Unexpected key size (' . strlen($key) * 8 . 'bits) for encryption algorithm: ' .
                        var_export($symmetricKey->type, true));
                }
            } catch (\Exception $e) {
                /* We failed to decrypt this key. Log it, and substitute a "random" key. */

                /* Create a replacement key, so that it looks like we fail in the same way as if the key was correctly padded. */
                /* We base the symmetric key on the encrypted key and private key, so that we always behave the
                 * same way for a given input key.
                 */
                $encryptedKey = $encKey->getCipherValue();
                $pkey = openssl_pkey_get_details($symmetricKeyInfo->key);
                $pkey = sha1(\Magento\Framework\Serialize\SerializerInterface::serialize($pkey), true);
                $key = sha1($encryptedKey . $pkey, true);
                /* Make sure that the key has the correct length. */
                if (strlen($key) > $keySize) {
                    $key = substr($key, 0, $keySize);
                } elseif (strlen($key) < $keySize) {
                    $key = str_pad($key, $keySize);
                }
            }
            $symmetricKey->loadkey($key);
        } else {
            $symKeyAlgo = $symmetricKey->getAlgorith();
            /* Make sure that the input key has the correct format. */
            if ($inputKeyAlgo !== $symKeyAlgo) {
                throw new \Exception('Algorithm mismatch between input key and key in message. ' .
                    'Key was: ' . var_export($inputKeyAlgo, true) . '; message was: ' .
                    var_export($symKeyAlgo, true));
            }
            $symmetricKey = $inputKey;
        }
        $algorithm = $symmetricKey->getAlgorith();
        if (in_array($algorithm, $blacklist, true)) {
            throw new \Exception('Algorithm disabled: ' . var_export($algorithm, true));
        }
        /** @var string $decrypted */
        $decrypted = $enc->decryptNode($symmetricKey, false);
        /*
         * This is a workaround for the case where only a subset of the XML
         * tree was serialized for encryption. In that case, we may miss the
         * namespaces needed to parse the XML.
         */
        $xml = '<root xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" '.
                     'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
            $decrypted .
            '</root>';
        $newDoc = new \DOMDocument();
        if (!$newDoc->loadXML($xml)) {
            throw new \Exception('Failed to parse decrypted XML. Maybe the wrong sharedkey was used?');
        }

        $decryptedElement = $newDoc->firstChild->firstChild;
        if ($decryptedElement === null) {
            throw new \Exception('Missing encrypted element.');
        }

        if (!($decryptedElement instanceof \DOMElement)) {
            throw new \Exception('Decrypted element was not actually a DOMElement.');
        }

        return $decryptedElement;
    }
    /**
     * Decrypt an encrypted element.
     *
     * @param  DOMElement     $encryptedData The encrypted data.
     * @param  XMLSecurityKey $inputKey      The decryption key.
     * @param  array          $blacklist     Blacklisted decryption algorithms.
     * @return DOMElement     The decrypted element.
     * @throws Exception
     */
    public static function decryptElement(
        \DOMElement $encryptedData,
        XMLSecurityKey $inputKey,
        array $blacklist = [],
        XMLSecurityKey $alternateKey = null
    ) {
        try {
            //echo "trying primary";
            return self::doDecryptElement($encryptedData, $inputKey, $blacklist);
        } catch (\Exception $e) {
            //Try with alternate key
            try {
                //echo "trying secondary";
                return self::doDecryptElement($encryptedData, $alternateKey, $blacklist);
            } catch (\Exception $t) {
                throw new \Exception('Failed to decrypt XML element.');
            }
            /*
             * Something went wrong during decryption, but for security
             * reasons we cannot tell the user what failed.
             */
            throw new \Exception('Failed to decrypt XML element.');
        }
    }

    public static function extractStrings(\DOMElement $parent, $namespaceURI, $localName)
    {
        $ret = [];
        for ($node = $parent->firstChild; $node !== null; $node = $node->nextSibling) {
            if ($node->namespaceURI !== $namespaceURI || $node->localName !== $localName) {
                continue;
            }
            $ret[] = trim($node->textContent);
        }

        return $ret;
    }

    public static function validateElement(\DOMElement $root)
    {
        //$data = $root->ownerDocument->saveXML($root);
        //echo htmlspecialchars($data);

        /* Create an XML security object. */
        $objXMLSecDSig = new XMLSecurityDSig();

        /* Both SAML messages and SAML assertions use the 'ID' attribute. */
        $objXMLSecDSig->idKeys[] = 'ID';


        /* Locate the XMLDSig Signature element to be used. */
        $signatureElement = self::xpQuery($root, './ds:Signature');

        if (count($signatureElement) === 0) {
            /* We don't have a signature element to validate. */
            return false;
        } elseif (count($signatureElement) > 1) {
            throw new \Exception("XMLSec: more than one signature element in root.");
        }/*  elseif ((in_array('Response', $signatureElement) && $ocurrence['Response'] > 1) ||
            (in_array('Assertion', $signatureElement) && $ocurrence['Assertion'] > 1) ||
            !in_array('Response', $signatureElement) && !in_array('Assertion', $signatureElement)
        ) {
            return false;
        } */

        $signatureElement = $signatureElement[0];
        $objXMLSecDSig->sigNode = $signatureElement;

        /* Canonicalize the XMLDSig SignedInfo element in the message. */
        $objXMLSecDSig->canonicalizeSignedInfo();

       /* Validate referenced xml nodes. */
        if (!$objXMLSecDSig->validateReference()) {
            throw new \Exception("XMLsec: digest validation failed");
        }

        /* Check that $root is one of the signed nodes. */
        $rootSigned = false;
        /** @var \DomNode$signedNode */
        foreach ($objXMLSecDSig->getValidatedNodes() as $signedNode) {
            if ($signedNode->isSameNode($root)) {
                $rootSigned = true;
                break;
            } elseif ($root->parentNode instanceof \DOMDocument && $signedNode->isSameNode($root->ownerDocument)) {
                /* $root is the root element of a signed document. */
                $rootSigned = true;
                break;
            }
        }

        if (!$rootSigned) {
            throw new \Exception("XMLSec: The root element is not signed.");
        }

        /* Now we extract all available X509 certificates in the signature element. */
        $certificates = [];
        foreach (self::xpQuery($signatureElement, './ds:KeyInfo/ds:X509Data/ds:X509Certificate') as $certNode) {
            $certData = trim($certNode->textContent);
            $certData = str_replace(["\r", "\n", "\t", ' '], '', $certData);
            $certificates[] = $certData;
            //echo "CertDate: " . $certData . "<br />";
        }

        $ret = [
            'Signature' => $objXMLSecDSig,
            'Certificates' => $certificates,
            ];

        //echo "Signature validated";


        return $ret;
    }



    public static function validateSignature(array $info, XMLSecurityKey $key)
    {
        /** @var XMLSecurityDSig $objXMLSecDSig */
        $objXMLSecDSig = $info['Signature'];

        $sigMethod = self::xpQuery($objXMLSecDSig->sigNode, './ds:SignedInfo/ds:SignatureMethod');
        if (empty($sigMethod)) {
            throw new \Exception('Missing SignatureMethod element');
        }
        $sigMethod = $sigMethod[0];
        if (!$sigMethod->hasAttribute('Algorithm')) {
            throw new \Exception('Missing Algorithm-attribute on SignatureMethod element.');
        }
        $algo = $sigMethod->getAttribute('Algorithm');

        if ($key->type === XMLSecurityKey::RSA_SHA256 && $algo !== $key->type) {
            $key = self::castKey($key, $algo);
        }

        /* Check the signature. */
        if (! $objXMLSecDSig->verify($key)) {
            throw new \Exception('Unable to validate Sgnature');
        }
    }

    public static function castKey(XMLSecurityKey $key, $algorithm, $type = 'public')
    {

        // do nothing if algorithm is already the type of the key
        if ($key->type === $algorithm) {
            return $key;
        }

        $keyInfo = openssl_pkey_get_details($key->key);
        if ($keyInfo === false) {
            throw new \Exception('Unable to get key details from XMLSecurityKey.');
        }
        if (!isset($keyInfo['key'])) {
            throw new \Exception('Missing key in public key details.');
        }

        $newKey = new XMLSecurityKey($algorithm, ['type'=>$type]);
        $newKey->loadKey($keyInfo['key']);

        return $newKey;
    }

    public static function processResponse($certFingerprint, $signatureData)
    {
        $responseSigned = self::checkSign($certFingerprint, $signatureData);
        return $responseSigned;
    }

    public static function checkSign($certFingerprint, $signatureData)
    {
        $certificates = $signatureData['Certificates'];

        if (count($certificates) === 0) {
            return false;
        }

        $fpArray = [];
        $fpArray[] = $certFingerprint;
        $pemCert = self::findCertificate($fpArray, $certificates);
        if ($pemCert===false) {
            return false;
        }
        $lastException = null;

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type'=>'public']);
        $key->loadKey($pemCert);

        try {
            /*
             * Make sure that we have a valid signature
             */
            self::validateSignature($signatureData, $key);
            return true;
        } catch (\Exception $e) {
            $lastException = $e;
        }


        /* We were unable to validate the signature with any of our keys. */
        if ($lastException !== null) {
            throw $lastException;
        } else {
            return false;
        }
    }

   
    private static function findCertificate(array $certFingerprints, array $certificates)
    {
        $candidates = [];

        foreach ($certificates as $cert) {
            $fp = strtolower(sha1(SPZendUtility::base64Decode($cert)));
            if (!in_array($fp, $certFingerprints, true)) {
                $candidates[] = $fp;
                continue;
            }

            /* We have found a matching fingerprint. */
            $pem = "-----BEGIN CERTIFICATE-----\n" .
                chunk_split($cert, 64) .
                "-----END CERTIFICATE-----\n";

            return $pem;
        }

        return false;
    }

    /**
     * Parse a boolean attribute.
     *
     * @param  \\DOMElement $node          The element we should fetch the attribute from.
     * @param  string     $attributeName The name of the attribute.
     * @param  mixed      $default       The value that should be returned if the attribute doesn't exist.
     * @return bool|mixed The value of the attribute, or $default if the attribute doesn't exist.
     * @throws \Exception
     */
    public static function parseBoolean(\DOMElement $node, $attributeName, $default = null)
    {
        if (!$node->hasAttribute($attributeName)) {
            return $default;
        }
        $value = $node->getAttribute($attributeName);
        switch (strtolower($value)) {
            case '0':
            case 'false':
                return false;
            case '1':
            case 'true':
                return true;
            default:
                throw new \Exception('Invalid value of boolean attribute ' . var_export($attributeName, true) . ': ' . var_export($value, true));
        }
    }

        /**
     * Insert a Signature-node.
     *
     * @param XMLSecurityKey $key The key we should use to sign the message.
     * @param array $certificates The certificates we should add to the signature node.
     * @param \DOMElement $root The XML node we should sign.
     * @param \DomNode $insertBefore The XML element we should insert the signature element before.
     * @throws Exception
     */
    public static function insertSignature(
        XMLSecurityKey $key,
        array $certificates,
        \DOMElement $root,
        \DomNode $insertBefore = null
    ) {
        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        switch ($key->type) {
            case XMLSecurityKey::RSA_SHA256:
                $type = XMLSecurityDSig::SHA256;
                break;
            case XMLSecurityKey::RSA_SHA384:
                $type = XMLSecurityDSig::SHA384;
                break;
            case XMLSecurityKey::RSA_SHA512:
                $type = XMLSecurityDSig::SHA512;
                break;
            default:
                $type = XMLSecurityDSig::SHA1;
        }

        $objXMLSecDSig->addReferenceList(
            [$root],
            $type,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
            ['id_name' => 'ID', 'overwrite' => false]
        );

        $objXMLSecDSig->sign($key);

        foreach ($certificates as $certificate) {
            $objXMLSecDSig->add509Cert($certificate, true);
        }
        $objXMLSecDSig->insertSignature($root, $insertBefore);
    }

    public static function signXML($xml, $publicCertificate, $privateKey, $insertBeforeTagName = "")
    {
        $param =[ 'type' => 'private'];
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, $param);
        $key->loadKey($privateKey);
        $document = new \DOMDocument();
        $document->loadXML($xml);
        $element = $document->firstChild;
        if (!empty($insertBeforeTagName)) {
            $domNode= $document->getElementsByTagName($insertBeforeTagName)->item(0);
            self::insertSignature($key, [ $publicCertificate ], $element, $domNode);
        } else {
            self::insertSignature($key, [ $publicCertificate ], $element);
        }
        $requestXML = $element->ownerDocument->saveXML($element);
        return $requestXML;
    }


    public static function getEncryptionAlgorithm($method)
    {
        switch ($method) {
            case 'http://www.w3.org/2001/04/xmlenc#tripledes-cbc':
                return XMLSecurityKey::TRIPLEDES_CBC;

            case 'http://www.w3.org/2001/04/xmlenc#aes128-cbc':
                return XMLSecurityKey::AES128_CBC;

            case 'http://www.w3.org/2001/04/xmlenc#aes192-cbc':
                return XMLSecurityKey::AES192_CBC;

            case 'http://www.w3.org/2001/04/xmlenc#aes256-cbc':
                return XMLSecurityKey::AES256_CBC;

            case 'http://www.w3.org/2001/04/xmlenc#rsa-1_5':
                return XMLSecurityKey::RSA_1_5;

            case 'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p':
                return XMLSecurityKey::RSA_OAEP_MGF1P;

            case 'http://www.w3.org/2000/09/xmldsig#dsa-sha1':
                return XMLSecurityKey::DSA_SHA1;

            case 'http://www.w3.org/2000/09/xmldsig#rsa-sha1':
                return XMLSecurityKey::RSA_SHA1;

            case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256':
                return XMLSecurityKey::RSA_SHA256;

            case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384':
                return XMLSecurityKey::RSA_SHA384;

            case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512':
                return XMLSecurityKey::RSA_SHA512;

            default:
                throw new \Exception('Invalid Encryption Method: '.$method);
        }
    }

    public static function sanitize_certificate($certificate)
    {
        $certificate = preg_replace("/[\r\n]+/", "", $certificate);
        $certificate = str_replace("-", "", $certificate);
        $certificate = str_replace("BEGIN CERTIFICATE", "", $certificate);
        $certificate = str_replace("END CERTIFICATE", "", $certificate);
        $certificate = str_replace(" ", "", $certificate);
        $certificate = chunk_split($certificate, 64, "\r\n");
        $certificate = "-----BEGIN CERTIFICATE-----\r\n" . $certificate . "-----END CERTIFICATE-----";
        return $certificate;
    }

    public static function desanitize_certificate($certificate)
    {
        $certificate = preg_replace("/[\r\n]+/", "", $certificate);
        //$certificate = str_replace( "-", "", $certificate );
        $certificate = str_replace("-----BEGIN CERTIFICATE-----", "", $certificate);
        $certificate = str_replace("-----END CERTIFICATE-----", "", $certificate);
        $certificate = str_replace(" ", "", $certificate);
        //$certificate = chunk_split($certificate, 64, "\r\n");
        //$certificate = "-----BEGIN CERTIFICATE-----\r\n" . $certificate . "-----END CERTIFICATE-----";
        return $certificate;
    }

    public static function generateRandomAlphanumericValue($length)
    {
        $chars = "abcdef0123456789";
        $chars_len = strlen($chars);
        $uniqueID = "";
        for ($i = 0; $i < $length; $i++) {
            $uniqueID .= substr($chars, rand(0, 15), 1);
        }
        return 'a'.$uniqueID;
    }
}
