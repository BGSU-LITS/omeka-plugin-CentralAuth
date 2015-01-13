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
     * Performs an authentication attempt.
     *
     * @return Zend_Auth_Result The result of the authentication.
     */
    public function authenticate()
    {
        // Use the parent method to authenticate the user.
        $result = parent::authenticate();

        if ($result->isValid()) {
            // If the user authenticated, create their email address.
            $email = $this->getUsername(). '@'.
                $this->_options['ldap']['accountDomainName'];

            // Lookup the user by their email address in the user table.
            $user = get_db()->getTable('User')->findByEmail($email);

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
                $email,
                array(__('There is no user for the email address %s.', $email))
            );
        }

        // Otherwise, return the parent's result.
        return $result;
    }
}
