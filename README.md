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

### User Matching
Once users authenticate through Single Sign On or LDAP, they must be matched with their records in the Omeka user database. By default, the username used for authentication will be matched against the username of a user in the Omeka database. If the Match by Email option is checked, the username used for authentication will be combined with the specifield Email Domain Name to form an email address that will be matched against the email address of a user in the Omeka database.

### Single Sign On and LDAP Modes
For both Single Sign On and LDAP, you can choose the authentication method's mode of operation. If enabled, Single Sign On is always performed first, followed by LDAP and finally the Omeka users database. You may force the Omeka login form to be displayed if Single Sign On is not required by appending `?form` as the query string to a login form URL.

First, if Single Sign On is not in Disabled mode, existing logins will be looked up via email address in the users database. If a matching record is found, and that account is active, the user will be logged in. If an account is not found and Single Sign On is in Gateway mode, the other authentication methods will be used. Otherwise, the user will be redirected to the Single Sign On form, and their login will similarly be tested. If their login fails and Single Sign On is in Required mode, they will be prevented from logging in through any other means unless the Single Sign On system failed to work correctly. Otherwise, the remaining authentication methods will be used.

Second, the Omeka login form is presented to the user. After the form is submitted, if LDAP is not in Disabled mode, the username and password will be authenticated against the configured LDAP server. If the user is authenticated, and that account is active, the user will be logged in. If their login fails and Single Sign On is in Required mode, they will be prevented from logging in. Otherwise, the remaining authentication methods will be used.

Finally, the username and password will be authenticated against active users in the users database.

To use only Single Sign On or LDAP, set either mode to Required. To use Single Sign On, LDAP or the users database, set both modes to Optional.

### Single Sign On and CAS
Currently, the only available Single Sign On type is CAS. This is the only option you can currently select.

For CAS, you must configure the host and URI. If the CAS endpoint is at `https://cas.example.com/cas` the host should be `cas.example.com` and the URI should be `cas`. You can optionally supply a non-default port.

### LDAP
For LDAP, you must configure the host. You can optionally supply a non-default port. It is recommended to use StartTLS or SSL to encrypt the connection to the LDAP server.

You must update the Base DN with the correct value for your directory. Other options will likely need to be set as well. For more information on all of the LDAP options, see the [Zend_Ldap manual](http://framework.zend.com/manual/1.12/en/zend.ldap.api.html). The [Zend LDAP Authentication manual](http://framework.zend.com/manual/1.12/en/zend.auth.adapter.ldap.html) provides more information and examples for Microsoft Active Directory Server and OpenLDAP.

You may configure LDAP options either via the plugin configuration form in the Omeka Dashboard, or in a central_auth.ini file located in the plugin root directory. Options specified via the Omeka Dashboard, including the LDAP password, will be saved in the site database as plain text. Options specified in the central_auth.ini file, although also stored in plain text, will not be stored in the database. If an option is specified in both the database and the central_auth.ini file, the option value in the central_auth.ini file will take precedence. You may specify some options in the database and some in the .ini file.

To specify ldap options in central_auth.ini, add a section `[ldap]`. This section supports all the options found in the config_form.php file for the plugin configuration form, with the prefix `central_auth_ldap_` removed from the option name. The only exception is the option `central_auth_ldap`, which in the .ini file is represented by `mode`. It may be set to either "optional" or "required".

An example central_auth.ini file is shown below. If used, it is recommended to configure your webserver to deny access to the file.
```ini
[ldap]
mode=""
host="ldap.example.edu"
port=""
useStartTls=false
useSsl=false
username=""
password=""
bindRequiresDn=false
baseDn="ou=people,dc=example,dc=edu"
accountCanonicalForm=1
accountDomainName="example.edu"
accountDomainNameShort="EXAMPLE"
accountFilterFormat="uid=%s"
```

## Requirements
Besides Omeka, required packages are installed via Composer. The following libraries are used:

* [SimpleCAS](https://github.com/mda515t/SimpleCAS) - Provides for CAS authentication.
* [HTTP_Request2](https://github.com/pear/HTTP_Request2) - Used by SimpleCAS for handling HTTP.
* [ca-bundle](https://github.com/composer/ca-bundle) - Provides certificate authority information to validate SSL connections.

## Development
This plugin was developed by the [Bowling Green State University Libraries](http://www.bgsu.edu/library.html). Development is [hosted on GitHub](https://github.com/BGSU-LITS/omeka-plugin-CentralAuth). This is the spiritual successor to the original [Omeka LDAP Authentication Plugin](https://code.google.com/p/omeka-ldap-plugin/) developed by Lehigh University, and the version 2.0 [LDAP Omeka Plugin](https://github.com/BGSU-LITS/LDAP-Plugin) developed by Bowling Green State University.
