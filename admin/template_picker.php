<?php namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

$template_types = MI()->collect('system/template_types');
$types = array();
foreach($template_types as $index => $entry)
{
	$types = array_merge($types, $entry);
}

$templates = MI()->templates()->getTemplates();

$type_count = count($types);

$data_view = ($type_count > 1) ? 'tabs' : 'list';

?>
<div id="maxinbound" class="wrap template_picker list" data-view='<?php echo $data_view ?>'>
	<h1 class='wp-heading-inline'><?php _e("Pick your template","maxinbound"); ?>
	</h1>

	<hr class='wp-header-end'>
	<span> <!-- this span is to ensure :nth-child works correct -->
<?php
	if ($data_view == 'tabs')
		$container_class = 'mb_tab';
	else
		$container_class = 'list';

foreach ($types as $key => $heading) :


?>
	

	<?php
	foreach ($templates as $tdata):

		$nice_name = trim(($tdata["nicename"] != '') ? $tdata["nicename"] : $tdata["name"]);
		$url = $tdata["url"];
		$description = trim($tdata["description"]);
		$name = $tdata["name"];
		$image = $tdata['preview_image'];

	?>

	<div class='item' data-template='<?php echo $name ?>'>
		<div class='preview_image'><img src="<?php echo $image ?>">
			<span class='title'><?php echo $nice_name ?></span>
			<span class='description'><?php echo $description ?></span>
		</div>
	</div>

	<?php endforeach; ?>
	</span>


<?php endforeach; ?>

</div> <!-- main -->
