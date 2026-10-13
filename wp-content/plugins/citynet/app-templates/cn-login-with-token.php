<?php
/**
 * Template Name: قالب login-with-token
 *
 *
 * @package Citynet
 */


get_header();
?>

<div id="app"></div>

<?php // echo $_POST['Token'] ?>

<input type="hidden" name="Token" value="<?= $_POST['Token'] ?>">

<?php //citynet_print_r($_POST); ?>
<?php get_footer(); ?>
