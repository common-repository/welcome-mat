<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');


try {
  $template = MI()->templates()->get();
}
catch (\Exception $e)
{
  echo __('Template error in system metabox', 'maxinbound');
  return;
}
?>
<div>
<?php _e("Name:","maxinbound"); ?> &nbsp;
<?php echo ucfirst($template->getTemplateName()); ?><br>

<?php _e("Fields:","maxinbound"); echo "&nbsp";  echo basename($template->getFieldsPath() ) ?>
</div>
