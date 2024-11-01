<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/** Error Class. */
class maxError
{
	protected $errors = array(); 


	public function __construct() 
	{

	}
	
	public function hasErrors() 
	{
		if (count($this->errors) > 0) 
			return true; 
		else
			return false; 
	}
	
	public function add(\Exception $e)
	{
	 	$this->errors[] = $e; 
	 	MI()->log('Error', array( $e->getMessage(),
	 							  $e->getLine(), 
	 							  $e->getFile(), 
	 							 )
	 			);
	 	
	} 
	
	public function admin_notice() 
	{
		add_action('admin_notice',  array($this, 'do_notice'));
	}

	/** Function to push all current errors to the admin notice. This should only be done on *life or death* errors **/ 	
	public function do_notice() 
	{
		$heading = __("Welcome Mat encounted important errors","maxinbound") ;
		$message = ''; 
		foreach($this->errors as $error) 
		{
			$message .= '<p>' . $e->getMessage . ' ( ' . $e->getLine() . ' - ' . $e->getFile() . '</p>'; 
		}		
		echo"<div class='error'> <h4>$heading</h4>
			 $message
		</div>"; 
		
	
	}


}
