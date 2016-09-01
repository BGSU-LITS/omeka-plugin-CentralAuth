<?php
/**
 * Omeka Central Auth Plugin: Users Controller
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 */

require_once CONTROLLER_DIR.'/UsersController.php';

/**
 * Omeka Central Auth Plugin: Users Controller Class
 *
 * Extends the UsersController class to provide revised versions of various
 * actions needed for Central Auth.
 *
 * @package Central Auth
 */
class CentralAuth_UsersController extends UsersController
{
    /**
     * Action to create and proccess the add user form.
     *
     * Adds a checkbox to specify if the user is active, and prevent the
     * account activation email from being sent if that checkbox is checked.
     */
    public function addAction()
    {
        // Get a new user.
        $user = new User();

        // Create a form for that user.
        $form = $this->_getUserForm($user);

        // Remove the submit button if it is part of the form.
        if (method_exists($form, 'setSubmitButtonText')) {
            $submit = $form->getElement('submit');
            $form->removeElement('submit');
        }

        // Add a checkbox to the form to specify if the user should be active.
        $form->addElement(
            'checkbox',
            'active',
            array(
                'label' =>
                    __('Active?'),
                'description' =>
                    __('Inactive users cannot log in to the site.')
            )
        );

        $form->setHasActiveElement(true);

        // Replace the submit button and set its label if necessary.
        if (!empty($submit)) {
            $form->addElement($submit);
            $form->setSubmitButtonText(__('Add User'));
        }

        // Store the form and user to the view.
        $this->view->form = $form;
        $this->view->user = $user;

        // If the form has not been posted, do not continue.
        if (!$this->getRequest()->isPost()) {
            return;
        }

        // Display error if the form is not valid.
        if (!$form->isValid($_POST)) {
            $this->_helper->flashMessenger(
                __(
                    'There was an invalid entry on the form.'.
                    ' Please try again.'
                ),
                'error'
            );

            return;
        }

        // Set the submitted data to the user, and attempt to save the record.
        $user->setPostData($_POST);

        if ($user->save(false)) {
            // Either the user is already active, or we should attempt to send
            // the activation email. In either case, notify about the outcome.
            if ($user->active || $this->sendActivationEmail($user)) {
                $this->_helper->flashMessenger(
                    __(
                        'The user "%s" was successfully added!',
                        $user->username
                    ),
                    'success'
                );
            } else {
                $this->_helper->flashMessenger(
                    __(
                        'The user "%s" was added, but the activation email'.
                        ' could not be sent.',
                        $user->username
                    )
                );
            }

            // Redirect to the browse users action.
            $this->_helper->redirector('browse');
        } else {
            // Something went wrong saving the user, send an error message.
            $this->_helper->flashMessenger($user->getErrors());
        }
    }

    /**
     * Action to create and process the user login form.
     *
     * If a single sign on (SSO) system is specified, attempt to authenticate
     * with that system. Otherwise, perform the parent's login action.
     */
    public function loginAction()
    {
        // This option specifies if SSO should be used.
        $sso = get_option('central_auth_sso');

        // Store if SSO is required to be used.
        $required = $sso === 'required';

        // Do not attempt SSO if the user posted a login form.
        if ($sso && empty($_POST['submit'])) {
            // Get the SSO auth adapter if available.
            $adapter = $this->_getAdapter();

            if ($adapter) {
                // Attempt to authenticate with the SSO auth adapter.
                $result = $this->_auth->authenticate($adapter);

                // If authenticated, redirect the user to their page.
                if ($result->isValid()) {
                    $session = new Zend_Session_Namespace;
                    $this->_helper->redirector->gotoUrl($session->redirect);
                }

                // If SSO unexpectantly failed, do not require SSO.
                if ($result->getCode() == Zend_Auth_Result::FAILURE) {
                    $required = false;
                }

                // Get the reasons for the failure.
                $messages = $result->getMessages();

                // If SSO is not required, instruct user to try login form.
                if (!$required) {
                    $messages[] = __('You may try to log in directly below.');
                }

                // Show error messages to the user.
                if ($messages) {
                    $this->_helper->flashMessenger(
                        implode("\n", $messages),
                        'error'
                    );
                }
            }
        }

        // Store to the view if either SSO or LDAP is required.
        $this->view->required = $required ||
            get_option('central_auth_ldap') == 'required';

        // Perform the normal login form action.
        parent::loginAction();

        // If SSO is required, provide a blank login form, so the user will not
        // be able to login through the normal auth adapter.
        if ($required) {
            $this->view->form = new Omeka_Form();
        }
    }

    /**
     * Action for users to log out.
     *
     * If SSO has been specified, notify that system of the user's log out.
     * Also perform the parent's action.
     */
    public function logoutAction()
    {
        // Clear the user's session.
        $this->_auth->clearIdentity();

        $_SESSION = array();
        Zend_Session::destroy();

        // If SSO is available, send that system the log out.
        if (get_option('central_auth_sso')) {
            $adapter = $this->_getAdapter();

            if ($adapter) {
                $adapter->logout(
                    $this->view->serverUrl().
                    $this->view->url('/')
                );
            }
        }

        // Redirect to main page.
        $this->_helper->redirector->gotoUrl('');
    }

    /**
     * Handles actions that are public to the user.
     *
     * If the user is already logged in, replace the parent method's
     * redirection with a version that works for this controller. Then perform
     * the parent method.
     */
    protected function _handlePublicActions()
    {
        // Get the action being performed.
        $action = $this->_request->getActionName();

        // Do nothing if it is not a public action.
        if (!in_array($action, $this->_publicActions)) {
            return;
        }

        // If the user is logged in, redirect to the actual home page.
        if ($this->getInvokeArg('bootstrap')->getResource('Currentuser')) {
            $this->_helper->redirector->gotoUrl('');
        }

        // Call the parent method.
        parent::_handlePublicActions();
    }

    /**
     * Gets an auth adapter.
     *
     * @return object|null If SSO is being used, an implementation of
     * Zend_Auth_Adapter_Interface for the SSO type. Otherwise null.
     */
    protected function _getAdapter()
    {
        // Get both the SSO and SSO type options.
        $sso = get_option('central_auth_sso');
        $type = get_option('central_auth_sso_type');

        // The type is CAS.
        if ($type == 'cas') {
            // Get options for the CAS SSO type.
            $options = array();

            foreach (array('hostname', 'port', 'uri') as $key) {
                $options[$key] = get_option('central_auth_sso_cas_'. $key);
            }

            // Return a new CAS auth adapter with the specified options, and
            // note if the SSO mode is gateway.
            return new CentralAuth_CasAdapter($options, $sso === 'gateway');
        }
    }
}
