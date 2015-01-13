<?php
/**
 * Omeka Central Auth Plugin: Admin Login Form
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

<h1>Omeka</h1>

<h2><?php echo link_to_admin_home_page(); ?></h2>

<?php echo flash(); ?>

<div class="eight columns alpha offset-by-one">
    <?php echo $this->form->setAction($this->url('users/login')); ?>
</div>

<?php if (!$required): ?>
    <p id="forgotpassword">
        <?php echo link_to('users', 'forgot-password', __('(Lost your password?)')); ?>
    </p>
<?php endif; ?>

<?php echo foot(array(), $footer); ?>
