<?php
/**
 * Omeka Central Auth Plugin: Public Login Form
 *
 * Overrides default login form to not display the "Lost your password?" link
 * if the database auth adapter is not being used.
 *
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2015 Bowling Green State University Libraries
 * @license MIT
 * @package Central Auth
 */

queue_js_file('login');
$pageTitle = __('Log In');
echo head(array('bodyclass' => 'login', 'title' => $pageTitle), $header);
?>

<h1><?php echo $pageTitle; ?></h1>

<p id="login-links">
    <span id="backtosite">
        <?php echo link_to_home_page(__('Go to Home Page')); ?>
    </span>

    <?php if (!$required): ?>
        |
        <span id="forgotpassword">
            <?php echo link_to('users', 'forgot-password', __('Lost your password?')); ?>
        </span>
    <?php endif; ?>
</p>

<?php echo flash(); ?>

<?php echo $this->form->setAction($this->url('users/login')); ?>

<?php echo foot(array(), $footer); ?>
