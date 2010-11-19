<?php if ($updated) { ?>
	<div class="updated"><p><strong><?php _e('Options saved.', 'mt_trans_domain' ); ?></strong></p></div>
<?php } ?>
<div class="wrap">
<h2>Custom User CSS</h2>
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
<?php wp_nonce_field('update-options'); ?>

<table class="form-table">

<tr valign="top">
<th scope="row">Custom CSS</th>
<td><textarea cols="80" rows="25" name="<?php echo $opt_name ?>">
<?php echo $css_val ?>
</textarea>
</tr>
 

</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="<?php echo $opt_name ?>" />

<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>

</form>
</div>
