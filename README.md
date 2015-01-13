# omeka-plugin-CentralAuth
[Omeka](http://omeka.org/) plugin that allows login through various centralized authentication systems. Currently, Central Authentication Service (CAS) and Lightweight Directory Access Protocol (LDAP) are supported.

## Installing Releases
Released versions of this plugin are [available for download](https://github.com/BGSU-LITS/omeka-plugin-CentralAuth/releases). You may extract the archive to your Omeka plugin directory.

## Installing Source Code
You will need to place the source code within a directory named CentralAuth in your Omeka plugin directory. Then, you need to use [Composer](http://getcomposer.org/) to execute the following command from the Central Auth directory: 

`composer install` 

After that, if you update the source code to a newer version, execute the following command: 

`composer update`

## Configuration
After [installing the plugin in Omeka](http://omeka.org/codex/Managing_Plugins_2.0), you will be able to configure numerous options:

### Single Sign On and LDAP Modes
For both Single Sign On and LDAP, you can choose the authentication method's mode of operation. If enabled, Single Sign On is always performed first, followed by LDAP and finally the Omeka users database.

First, if Single Sign On is not in Disabled mode, existing logins will be looked up via email address in the users database. If a matching record is found, and that account is active, the user will be logged in. If an account is not found and Single Sign On is in Gateway mode, the other authentication methods will be used. Otherwise, the user will be redirected to the Single Sign On form, and their login will similarly be tested. If their login fails and Single Sign On is in Required mode, they will be prevented from logging in through any other means unless the Single Sign On system failed to work correctly. Otherwise, the remaining authentication methods will be used.

Second, the Omeka login form is presented to the user. After the form is submitted, if LDAP is not in Disabled mode, the username and password will be authenticated against the configured LDAP server. If the user is authenticated, and that account is active, the user will be logged in. If their login fails and Single Sign On is in Required mode, they will be prevented from logging in. Otherwise, the remaining authentication methods will be used.

Finally, the username and password will be authenticated against active users in the users database.

To use only Single Sign On or LDAP, set either mode to Required. To use Single Sign On, LDAP or the users database, set both modes to Optional.

### Single Sign On and CAS
Currently, the only available Single Sign On type is CAS. This is the only option you can currently select.

For CAS, you should configure the host and URI. If the CAS endpoint is at `https://cas.example.com/cas` the host should be `cas.example.com` and the URI should be `cas`. You can optionally supply a non-default port.

When looking for the user in the users database, the email field will be searched by appending the account domain name to make an email address with the authenticated username. For an email address of `username@example.com` you should use `example.com`.

### LDAP
For LDAP, you should configure the host. You can optionally supply a non-default port. It is recommended to use StartTLS or SSL to encrypt the connection to the LDAP server. 

You must update base DN, account domain name and account short domain name with the correct values for your directory. For more information on all of the LDAP options, see the [Zend_Ldap manual](http://framework.zend.com/manual/1.12/en/zend.ldap.api.html).

## Requirements
Besides Omeka, required packages are installed via Composer. The following libraries are used:

* [SimpleCAS](https://github.com/saltybeagle/SimpleCAS) - Provides for CAS authentication.
* [HTTP_Request2](http://pear.php.net/HTTP_Request2) - Used by SimpleCAS for handling HTTP.
* [CurlCaBundle](https://github.com/Kdyby/CurlCaBundle) - Provides certificate authority information to validate SSL connections.

## Development
This plugin was developed by the [Bowling Green State University Libraries](http://www.bgsu.edu/library.html). Development is [hosted on GitHub](https://github.com/BGSU-LITS/omeka-plugin-CentralAuth). This is the spiritual successor to the original [Omeka LDAP Authentication Plugin](https://code.google.com/p/omeka-ldap-plugin/) developed by Lehigh University, and the version 2.0 [LDAP Omeka Plugin](https://github.com/BGSU-LITS/LDAP-Plugin) developed by Bowling Green State University.





