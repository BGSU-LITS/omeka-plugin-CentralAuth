<?php
/**
 * Omeka Central Auth Plugin: CAS Auth Adapter
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 */

/**
 * Omeka Central Auth Plugin: CAS Auth Adapter Class
 *
 * @package Central Auth
 */
class CentralAuth_CasAdapter implements Zend_Auth_Adapter_Interface
{
    /**
     * @var boolean If SSO is in gateway mode.
     */
    protected $_gateway;

    /**
     * @var object SimpleCAS protocol.
     */
    protected $_protocol;

    /**
     * @var object SimpleCAS client.
     */
    protected $_client;

    /**
     * Class constructor.
     *
     * @param array $options Options provided to the SimpleCAS protocol object.
     * @param boolean $gateway If SSO is in gateway mode.
     */
    public function __construct($options = array(), $gateway = false) {
        // Store gateway mode.
        $this->_gateway = $gateway;

        // Create a new SimpleCAS protocol object with specified options.
        $this->_protocol = new SimpleCAS_Protocol_Version2($options);

        // User's should be redirected back to Omeka after logout.
        $this->_protocol->setLogoutServiceRedirect(true);

        // Specify a certificate authority file so that SSL is validated.
        $this->_protocol->getRequest()->setConfig(
            'ssl_cafile',
            \Kdyby\CurlCaBundle\CertificateHelper::getCaInfoFile()
        );

        // Create a new SimpleCAS client object with the protocol.
        $this->_client = SimpleCAS::client($this->_protocol);

        // Handle Single Log Out (SLO).
        $this->_client->handleSingleLogOut();
    }

    /**
     * Performs an authentication attempt.
     *
     * @return Zend_Auth_Result The result of the authentication.
     */
    public function authenticate()
    {
        // Attempt authentication in the specified mode.
        if ($this->_gateway) {
            $this->_client->gatewayAuthentication();
        } else {
            $this->_client->forceAuthentication();
        }

        $lookup = '';

        // Check if user actually authenticated.
        if ($this->_client->isAuthenticated()) {
            if (get_option('central_auth_email')) {
                // If user matching is by email, create email address.
                $lookup = $this->_client->getUsername(). '@'.
                    get_option('central_auth_email_domain');

                // Lookup the user by their email address in the user table.
                $user = get_db()->getTable('User')->findByEmail($lookup);
            } else {
                // Otherwise use the username.
                $lookup = $this->_client->getUsername();

                // Lookup the user by their username in the user table.
                $user = get_db()->getTable('User')->findBySql(
                    'username = ?',
                    array($lookup),
                    true
                );
            }

            // If the user was found and active, return success.
            if ($user && $user->active) {
                return new Zend_Auth_Result(
                    Zend_Auth_Result::SUCCESS,
                    $user->id
                );
            }

            // Return that the user does not have an active account.
            return new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                $lookup,
                array(__('User matching "%s" not found.', $lookup))
            );
        }

        // In gateway mode, it's okay if the user was not authenticated.
        if ($this->_gateway) {
            return new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS,
                $lookup
            );
        }

        // Otherwise, the CAS authentication failed.
        return new Zend_Auth_Result(
            Zend_Auth_Result::FAILURE_UNCATEGORIZED,
            $lookup,
            array(__('Single sign on is currently unavailable.'))
        );
    }

    /**
     * Performs a user logout.
     *
     * @param string $url The URL to redirect to after logout.
     */
    public function logout($url = '')
    {
        // Tell the client to logout and redirect to the specified URL.
        $this->_client->logout($url);
    }
}
