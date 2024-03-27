<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\Redirect;
use MiniOrange\SP\Helper\Exception\NotRegisteredException;

use MiniOrange\SP\Helper\Exception\RequiredFieldsException;

/**
 * The base action class that is inherited by each of the action
 * class. It consists of certain common functions that needs to
 * be inherited by each of the action class. Extends the
 * \Magento\Framework\App\Action\Action class which is usually
 * extended by Controller class.
 */
abstract class BaseAction extends Action
{

    protected $spUtility;
    protected $context;
    protected $REQUEST;
    protected $POST;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MiniOrange\SP\Helper\SPUtility $spUtility
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->spUtility = $spUtility;
        parent::__construct($context);
    }


    /**
     * This function checks if the required fields passed to
     * this function are empty or not. If empty throw an exception.
     *
     * @param $array
     * @throws RequiredFieldsException
     */
    protected function checkIfRequiredFieldsEmpty($array)
    {
        foreach ($array as $key => $value) {
            if ((is_array($value) && ( !isset($value[$key]) || $this->spUtility->isBlank($value[$key])) )
                || $this->spUtility->isBlank($value)
              ) {
                throw new RequiredFieldsException();
            }
        }
    }


    /**
     * This function is used to send LogoutResponse as a request Parameter.
     * LogoutResponse is sent in the request parameter if the binding is
     * set as HTTP Redirect. Http Redirect is the default way Logout Response
     * is sent.
     *
     * @param $samlResponse
     * @param $sendRelayState
     * @param $ssoUrl
     * @return Redirect
     */
    protected function sendHTTPRedirectResponse($samlResponse, $sendRelayState, $ssoUrl)
    {
        $redirect = $ssoUrl;
        $redirect .= strpos($ssoUrl, '?') !== false  ? '&' : '?';
        $redirect .= 'SAMLResponse=' . $samlResponse . '&RelayState=' . urlencode($sendRelayState);
        return $this->resultRedirectFactory->create()->setUrl($redirect);
    }

    
    /** This function is abstract that needs to be implemented by each Action Class */
    abstract public function execute();


    /* ===================================================================================================
                THE FUNCTIONS BELOW ARE FREE PLUGIN SPECIFIC AND DIFFER IN THE PREMIUM VERSION
       ===================================================================================================
     */

    /**
     * This function checks if the user has registered himself
     * and throws an Exception if not registered. Checks the
     * if the admin key and api key are saved in the database.
     *
     * @throws NotRegisteredException
     */
    protected function checkIfValidPlugin()
    {
        if (!$this->spUtility->micr()) {
            throw new NotRegisteredException;
        }
    }


    /**
     * This function is used to send LogoutRequest & AuthRequest as a request Parameter.
     * LogoutRequest & AuthRequest is sent in the request parameter if the binding is
     * set as HTTP Redirect. Http Redirect is the default way Authn Request
     * is sent. [PREMIUM] - Function also generates the signature and appends it in the
     * parameter as well along with the relayState parameter
     * @param $samlRequest
     * @param $sendRelayState
     * @param $idpUrl
     * @return Redirect
     */
    protected function sendHTTPRedirectRequest($samlRequest, $sendRelayState, $idpUrl,$params)
    { 
          $this->spUtility->customlog("BaseAction: sendHTTPRedirectRequest");
          $samlRequest = "SAMLRequest=" . $samlRequest;
        foreach($params as $key=>$value){
            if($key == "relayState")
                $samlRequest =  $samlRequest . "&RelayState=" . urlencode($sendRelayState);
            else{
                $samlRequest = $samlRequest. "&$key=".$value;
            }

            }
        $redirect = $idpUrl;
        $this->spUtility->customlog("BaseAction: idpUrl:".$idpUrl);
        $redirect .= strpos($idpUrl, '?') !== false  ? '&' : '?';
        $redirect .= $samlRequest;
        return $this->resultRedirectFactory->create()->setUrl($redirect);
    }


    /**
     * This function is used to send LogoutRequest & AuthRequest as a post Parameter.
     * LogoutRequest & AuthRequest is sent in the post parameter if the binding is
     * set as HTTP Post. [PREMIUM] - Function also generates the signature and
     * appends it in the XML document before sending it over as post
     * parameter data along with the relayState parameter.
     * @param $samlRequest
     * @param $sendRelayState
     * @param $idpUrl
     */
    protected function sendHTTPPostRequest($samlRequest, $sendRelayState, $sloUrl)
    {
        $base64EncodedXML = base64_encode($samlRequest);
        //post request
        ob_clean();
        printf("  <html><head><script src='https://code.jquery.com/jquery-1.11.3.min.js'></script><script type=\"text/javascript\">
                    $(function(){document.forms['saml-request-form'].submit();});</script></head>
                    <body>
                        Please wait...
                        <form action=\"%s\" method=\"post\" id=\"saml-request-form\" style=\"display:none;\">
                            <input type=\"hidden\" name=\"SAMLRequest\" value=\"%s\" />
                            <input type=\"hidden\" name=\"RelayState\" value=\"%s\" />
                        </form>
                    </body>
                </html>", $sloUrl, $base64EncodedXML, htmlentities($sendRelayState));
    }


    /**
     * This function is used to send Logout Response as a post Parameter.
     * Logout Response is sent in the post parameter if the binding is
     * set as HTTP Post.
     *
     * @param $samlResponse
     * @param $sendRelayState
     * @param $ssoUrl
     */
    protected function sendHTTPPostResponse($samlResponse, $sendRelayState, $ssoUrl)
    {
        $base64EncodedXML = base64_encode($samlResponse);
        //post request
        ob_clean();
        printf("  <html><head><script src='https://code.jquery.com/jquery-1.11.3.min.js'></script><script type=\"text/javascript\">
                    $(function(){document.forms['saml-request-form'].submit();});</script></head>
                    <body>
                        Please wait...
                        <form action=\"%s\" method=\"post\" id=\"saml-request-form\" style=\"display:none;\">
                            <input type=\"hidden\" name=\"SAMLResponse\" value=\"%s\" />
                            <input type=\"hidden\" name=\"RelayState\" value=\"%s\" />
                        </form>
                    </body>
                </html>", $ssoUrl, $base64EncodedXML, htmlentities($sendRelayState));
    }

    /** Setter for the request Parameter */
    public function setRequestParam($request)
    {
        $this->REQUEST = $request;
        return $this;
    }


    /** Setter for the post Parameter */
    public function setPostParam($post)
    {
        $this->POST = $post;
        return $this;
    }
}
