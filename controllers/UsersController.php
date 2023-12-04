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
     * If LDAP is required, make sure username/password can't be used.
     */
    public function loginAction()
    {
        // Store to the view if LDAP is required.
        $this->view->required =
            get_option('central_auth_ldap') == 'required';

        // Perform the normal login form action.
        parent::loginAction();

        // If LDAP is required, provide a blank login form, so the user will
        // not be able to login through the normal auth adapter.
        if ($required) {
            $this->view->form = new Omeka_Form();
        }
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
}
