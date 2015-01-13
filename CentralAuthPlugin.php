<?php
/**
 * Omeka Central Auth Plugin
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 */

/**
 * Omeka Central Auth Plugin: Plugin Class
 *
 * @package Central Auth
 */
class CentralAuthPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Plugin hooks.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'config',
        'config_form',
        'define_routes'
    );

    /**
     * @var array Plugin filters.
     */
    protected $_filters = array(
        'admin_whitelist',
        'login_adapter'
    );

    /**
     * @var array Plugin options.
     */
    protected $_options = array(
        'central_auth_sso' => false,
        'central_auth_sso_type' => 'cas',
        'central_auth_sso_cas_hostname' => 'cas.example.com',
        'central_auth_sso_cas_port' => '',
        'central_auth_sso_cas_uri' => 'cas',
        'central_auth_sso_cas_domain' => 'example.com',
        'central_auth_ldap' => false,
        'central_auth_ldap_host' => 'ldap.example.com',
        'central_auth_ldap_port' => '',
        'central_auth_ldap_useStartTls' => false,
        'central_auth_ldap_useSsl' => false,
        'central_auth_ldap_username' => '',
        'central_auth_ldap_password' => '',
        'central_auth_ldap_bindRequiresDn' => false,
        'central_auth_ldap_baseDn' => 'ou=people,dc=example,dc=com',
        'central_auth_ldap_accountCanonicalForm' => 1,
        'central_auth_ldap_accountDomainName' => 'example.com',
        'central_auth_ldap_accountDomainNameShort' => 'EXAMPLE',
        'central_auth_ldap_accountFilterFormat' => 'uid=%s'
    );

    /**
     * Plugin constructor.
     *
     * Requires class autoloader, and calls parent constructor.
     */
    public function __construct()
    {
        require 'vendor/autoload.php';
        parent::__construct();
    }

    /**
     * Hook to plugin installation.
     *
     * Installs the options for the plugin.
     */
    public function hookInstall()
    {
        $this->_installOptions();
    }

    /**
     * Hook to plugin uninstallation.
     *
     * Uninstalls the options for the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Hook to plugin configuration form submission.
     *
     * Sets options submitted by the configuration form.
     */
    public function hookConfig($args)
    {
        foreach (array_keys($this->_options) as $option) {
            if (isset($args['post'][$option])) {
                set_option($option, $args['post'][$option]);
            }
        }
    }

    /**
     * Hook to output plugin configuration form.
     *
     * Include form from config_form.php file.
     */
    public function hookConfigForm()
    {
        include 'config_form.php';
    }

    /**
     * Hook to define routes.
     *
     * Overrides the add, login and logout actions of the UsersController to
     * our customized CentralAuth_UsersController versions.
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];

        $router->addRoute(
            'central_auth_add',
            new Zend_Controller_Router_Route(
                'users/add',
                array(
                    'module' => 'central-auth',
                    'controller' => 'users',
                    'action' => 'add'
                )
            )
        );

        $router->addRoute(
            'central_auth_login',
            new Zend_Controller_Router_Route(
                'users/login',
                array(
                    'module' => 'central-auth',
                    'controller' => 'users',
                    'action' => 'login'
                )
            )
        );

        $router->addRoute(
            'central_auth_logout',
            new Zend_Controller_Router_Route(
                'users/logout',
                array(
                    'module' => 'central-auth',
                    'controller' => 'users',
                    'action' => 'logout'
                )
            )
        );
    }

    /**
     * Filter the admin interface whitelist.
     *
     * Allows our custom login action to be accessed without logging in.
     */
    public function filterAdminWhitelist($whitelist)
    {
        $whitelist[] = array(
            'module' => 'central-auth',
            'controller' => 'users',
            'action' => 'login'
        );

        return $whitelist;
    }

    /**
     * Filter the login auth adapter.
     *
     * If requested, atttempts to use LDAP to login the user instead of the
     * default database auth adapter to authenticate the user.
     */
    public function filterLoginAdapter($adapter, $args)
    {
        // The login_form contains the username and password.
        $form = $args['login_form'];

        // This option specifies if LDAP authentication should be used.
        $ldap = get_option('central_auth_ldap');

        if ($ldap) {
            // Build an array for the LDAP auth adapter from plugin options.
            $options = array();
            $preg = '/^central_auth_ldap_/';

            foreach (array_keys($this->_options) as $option) {
                if (preg_match($preg, $option)) {
                    $key = preg_replace($preg, '', $option);
                    $value = get_option($option);

                    if (!empty($value)) {
                        $options[$key] = $value;
                    }
                }
            }

            // Create new auth adapter with the options, username and password.
            $adapterLdap = new CentralAuth_LdapAdapter(
                array('ldap' => $options),
                $form->getValue('username'),
                $form->getValue('password')
            );

            // Attempt to authenticate using LDAP.
            $result = $adapterLdap->authenticate();

            // If the user authenticated or LDAP authentication is required,
            // return the LDAP auth adapter as the version to use.
            if ($result->isValid() || $ldap == 'required') {
                return $adapterLdap;
            }
        }

        // Return the database auth adapter after setting username/password.
        return $adapter
            ->setIdentity($form->getValue('username'))
            ->setCredential($form->getValue('password'));
    }
}
