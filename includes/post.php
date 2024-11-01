<?php

if (isset($_POST) && count($_POST) > 0) 
{
	$post = array_filter($_POST); 
	MI()->tell('post-form', $post); 	

	MI()->tell('template/process-end');
}

exit();
