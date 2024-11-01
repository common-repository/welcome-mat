<?php
namespace MaxInbound;

defined('ABSPATH') or die('No direct access permitted');

global $post;
$post_id = $post->ID;


$template = MI()->templates()->load($post_id, array("preview" => true) );

if (! $template)
{
	exit('Template not found');
}

$fields = $template->getFields(); // Fields as an Array
$parts = $template->getParts(); // Template parts.
$data = $template->getData();  // The saved data

?>

<div id="maxinbound" class="editor" data-view='tabs'>
	<input type="hidden" name="template" value="<?php echo $template->getTemplateName(); ?>" />




<?php foreach ($parts as $part => $pfields):

?>
	<div class="mb_tab option-container editor">
		<div class="title">
			<span class="dashicons dashicons-list-view"></span>
			<span class='title'><?php echo ucfirst($part) ?></span>

		</div>
		<div class="inside">
		<?php
		if (function_exists('get_preview_post_link') )
		{
			$preview_link = esc_url( \get_preview_post_link( $post ) );
			$preview_link .= '&preview_id=' . $post_id;

		?>
		<a href="<?php echo $preview_link ?>" target="_blank" class='button-primary preview_button' name="preview_button"><?php _e('Preview','maxinbound'); ?></a>
		<?php
		}  // preview_post_link
		?>
<?php

foreach ($pfields as $field_name):
	if ( ! $template->isFieldinTemplate($field_name) )
		continue;

		$output = MI()->editors()->admin($field_name);
		if ($output != '')
		{
		?>
			<div class='field_group'>
			<?php echo $output ?>
			</div>
	<?php
		}

endforeach; // fields
 ?>
 </div> <!-- inside -->
 </div> <!-- tab -->
 <?php
endforeach;  // groups ?>

<?php
$options = $template->getOptions();
//include('preview.php');
include('template_options.php');

?>


<!--	<div class='mb_tab'>
		<div class="title">
			<span class="dashicons dashicons-no"></span>
			<span class="title"><?php _e("Preview","maxinbound"); ?></span>
		</div>
	</div>
-->


<!--
	<div class='mb-preview-window output'>
		<div class="header"><?php _e("Preview", 'maxinbound'); ?>
			<span class="close tb-close-icon"></span>
		</div>
		<div class="mb-preview-wrapper shortcode-container">
			<?php //$template->preview(); ?>
		</div>
	</div>
-->
</div> <!-- // maxbound -->
