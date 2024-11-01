<?php namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

$post_id = MI()->ask('template/post-id'); 

if (! $post_id) 
{
	MI()->tell('template/load_error', $post_id); 
	return;
}

$template = MI()->templates()->load($post_id); 

if ($template) 
{

	MI()->editors()->view();	
	$template->output();
	MI()->tell('template/queued', true);		
}
else 
{
	MI()->tell('template/queued', false); 
}
