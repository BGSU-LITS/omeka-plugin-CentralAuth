<?php
/**
 * Omeka Central Auth Plugin: LDAP Auth Adapter
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 */

/**
 * Omeka Central Auth Plugin: LDAP Auth Adapter Class
 *
 * Extends the Zend_Auth_Adapter_Ldap to also check that the authenticated user
 * is also an active Omeka user.
 *
 * @package Central Auth
 */
class CentralAuth_LdapAdapter extends Zend_Auth_Adapter_Ldap
{
    /**
     * @var object Zend_Auth_Result from previous call to autheticate method.
     */
    protected $_authResult = false;

    /**
     * Performs an authentication attempt.
     *
     * @return Zend_Auth_Result The result of the authentication.
     */
    public function authenticate()
    {
        // Return auth result from a previous call if available.
        if ($this->_authResult) {
            return $this->_authResult;
        }

        // Use the parent method to authenticate the user.
        $result = parent::authenticate();

        // Check if user actually authenticated.
        if ($result->isValid()) {
            if (get_option('central_auth_email')) {
                // If user matching is by email, create email address.
                $lookup = $this->getUsername() . '@' .
                    get_option('central_auth_email_domain');

                // Lookup the user by their email address in the user table.
                $user = get_db()->getTable('User')->findByEmail($lookup);
            } else {
                // Otherwise use the username.
                $lookup = $this->getUsername();

                // Lookup the user by their username in the user table.
                $user = get_db()->getTable('User')->findBySql(
                    'username = ?',
                    array($lookup),
                    true
                );
            }

            // If the user was found and active, store successful auth result
            // for future use in Omeka authentication hook and return it.
            if ($user && $user->active) {
                $this->_authResult = new Zend_Auth_Result(
                    Zend_Auth_Result::SUCCESS,
                    $user->id
                );

                return $this->_authResult;
            }

            // Store and return that the user does not have an active account.
            $message = __('Omeka user matching "%s" not found.', $lookup);

            $this->_authResult = new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                $lookup,
                array($message)
            );

            _log(
                'CentralAuth_LdapAdapter: ' . $message,
                Zend_Log::INFO
            );


            return $this->_authResult;
        }

        // Otherwise, log messages to error log.
        $messages = $result->getMessages();

        _log(
            'CentralAuth_LdapAdapter: ' . implode("\n", $messages),
            Zend_Log::ERR
        );

        // Store and return the parent's result with error message for user.
        $this->_authResult = new Zend_Auth_Result(
            $result->getCode(),
            $result->getIdentity(),
            array($messages[0])
        );

        return $this->_authResult;
    }
}
