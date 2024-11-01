<?php 
namespace MaxInbound; 
defined('ABSPATH') or die('No direct access permitted');

/** Class to find and store session specific data. Data can be requested via whistle. **/ 
class maxData
{
	protected static $instance; 
	
	public static function init() 
	{
		self::$instance = new maxData(); 
	}
	
	public static function getInstance() 
	{
		return self::$instance; 
	}
	
	public function __construct() 
	{

		// front information 
		if (! is_admin() || defined( 'DOING_AJAX' ) && DOING_AJAX ) 
		{			

			$ip = $this->getIP(); 
			$agent = $this->getAgent(); 
			$hash = $this->getHash($ip, $agent); 
			$domain = $this->getDomain() ; 
			$referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : ''; 
			
			MI()->tell('visitor/ip', $ip); 
			MI()->tell('visitor/agent', $agent);
			MI()->tell('visitor/hash',  $hash); 			
			
			MI()->tell('page/domain', $domain);
			MI()->tell('page/referer',$referer);
		
			$last_show = $this->getCookie('show'); 

			MI()->tell('visitor/last-show', $last_show); // tell basic information on last show. 
	
			add_action('template_redirect', array($this,'getCurrentPost'), 5 ); // load the current WordPress page post information. 
			
			// detect mobile devices 
			$detect = new Mobile_Detect;
			MI()->tell('visitor/is_mobile', $detect->isMobile());
			MI()->tell('visitor/is_tablet', $detect->isTablet());
			
			// to keep tab of what has been shown this session
			//MI()->listen('visitor/sessioncookie', array($this, 'checkSessionCookie') );
			
			// at the end of all processing, write updated cookies
			$this->setCookie();
			
			$this->detectCaching(); 
		}
		
		// Add cookie when template is going to be shown. 
		MI()->listen('system/ajax/visit-cookie', array($this, 'setVisitCookie') ); 
					
	}
	
	protected function detectCaching() 
	{
		if ( is_user_logged_in() )  // mostly hopefully people don't cache logged in users. 
		{
			MI()->tell('system/front_caching', false); 
		}
		else if (defined('WP_CACHE') && WP_CACHE) 
		{		
			MI()->tell('system/front_caching', true); 
			MI()->log('Front Caching system found');
		}
		else
		{
			MI()->tell('system/front_caching', false);
		}
	}
	
	protected function getCookie($cookie = 'hash')
	{
		$value = false; 
		$sysslug = MI()->ask('system/slug'); 

		
		if ($cookie == 'hash') 
		{	
			$value = isset($_COOKIE[$sysslug . "_visit_hash"]) ? $_COOKIE[$sysslug . "_visit_hash"] : false; 
		}
		elseif ($cookie == 'show') 
		{
			$value = isset($_COOKIE[$sysslug . "_last_show"]) ? json_decode(stripslashes($_COOKIE[$sysslug . "_last_show"]), true) : false;
  
		}

		return $value;
	}
	
	protected function setCookie() 
	{
		$hash = MI()->ask('visitor/hash'); 
		$domain = MI()->ask('page/domain'); 
		$sysslug = MI()->ask('system/slug'); 
				
		$expire = time() + YEAR_IN_SECONDS; 
		
		// name - value - expire - path - domain 
		setcookie($sysslug . '_visit_hash', $hash, $expire, '/', $domain ); 
	}
	
	/** Function to set Visitor cookie. Called via AJAX */ 
	public function setVisitCookie() 
	{
		$sysslug = MI()->ask('system/slug'); 
		$post_id = MI()->ask('system/ajax/post-id'); 
		$last_show = $this->lastShowbyPost($post_id); 
		$domain = $this->getDomain();

		if (! is_array($last_show)) 
			$last_show = array(); // if not array, assume error and nuke.


		$expire = time() + YEAR_IN_SECONDS; 		
		$last_show[$post_id] = time();

		setcookie($sysslug . '_last_show', json_encode($last_show), $expire, '/', $domain ); 

		echo json_encode( array('status' => true));
		exit(); 
	}
	
	public function checkSessionCookie($post_id) 
	{
		
		$sysslug = MI()->ask('system/slug'); 
		$domain = $this->getDomain();
				
		$cookie = isset($_COOKIE[$sysslug . "_session_" . $post_id]) ? $_COOKIE[$sysslug . '_session_' . $post_id] : false; 
		if ($cookie === false)
		{
			$result = setcookie($sysslug . "_session_" . $post_id , time() , 0, '/', $domain ); 
			return false; // no cookie
		}	
		return true;
		
			
	}
		
	public function getCurrentPost() 
	{
		global $post; 
		global $wp_query; 
		// need is_front_page, has_front_page, is_archive etc implements. 
		
		if (is_admin()) 
			return; // no. 
		
		$post_id = null;
		if (isset($post->ID)) 
			$post_id = $post->ID; 
		elseif (isset($wp_query->post->ID)) 
			$post_id = $wp_query->post->ID; 
		
		MI()->tell('page/post-id', $post_id);
	
	}
	
	/** Checks cookie and other sources. Gets last shown date of template
	*
	*
	* return Array Array with post_ids and last shown information of visitor */ 
	protected function lastShowbyPost($post_id) 
	{
		$last_visit = array(); 
		
		$cvisit = $this->getCookie('show'); 

		if ($cvisit)  // if cookie, we have show.
			return $cvisit; 
		
		// try to find hash. 
		$hash = MI()->ask('visitor/hash'); 
		if (! $hash)
		{
			$last_visit[$post_id] = 0;
			return $last_visit; 
		}
		
		// If nothing found, maybe somebody knows.. 
		$last_visit = MI()->ask('visitor/last-show'); 
		if ( isset($last_visit[$post_id])) 
			return $last_vist[$post_id]; 
			
		return false;
	}
	
	protected function getDomain() 
	{
		$domain = (! empty( $_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
		return $domain;
	}

	protected function getIP() 
	{
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip; 	
	}
	
	protected function getHash($ip, $agent) 
	{
		$chash = $this->getCookie('hash'); 
		if ($chash) 
			return $chash; // take from cookie if av. 
		
		return md5($ip . $agent); 	
	}

	protected function getAgent() 
	{
		
		$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER["HTTP_USER_AGENT"] : false; 
		return $agent; 
		
	}

} // class
