<?php 

// Replacing the composer stuff 

$classmap = array(
		  "IQuery.php", 
          "gan_formatter.php", 
          "gan_node_html.php",
          "gan_tokenizer.php",          
          "gan_parser_html.php",
          "gan_selector_html.php",
          "gan_xml2array.php",
          "pQuery.php");
foreach($classmap as $file)
{
	require_once($file);
}

