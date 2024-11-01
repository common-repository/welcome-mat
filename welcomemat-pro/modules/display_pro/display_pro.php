<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ .'\moduleDisplayPRO', 'init'));

class moduleDisplayPRO extends miModule
{
	static $name = 'display_pro'; 

	public static function init($modules) 
	{
		$modules->register(self::$name, get_called_class() );
	}
	
	public function __construct() 
	{
		parent::__construct(); 
		$this->title = __('Display PRO','maxinbound'); 

	 	MI()->listen('editor/save-options', array($this, 'save_options') ); 
		MI()->listen('editor/metabox/display', array($this, 'options') );

		// listen for asks here 
		MI()->offer('template/decide-load', array($this, 'decide_load') ); 
		
		// for the delay / display options 
		MI()->offer('template/maintag', array($this, 'template_tag')); 
	//	MI()->offer('template/body-class', array($this, 'body_class') ); // no working well via body. 
		MI()->listen('template/scripts', array($this, 'front_script') ); 
		
	}
	
	public function save_options($post)
	{
		$seconds = isset($post['display_seconds']) ? intval($post['display_seconds']) : 0; 
		$noauto = isset($post['display_noautoshow']) ? intval($post['display_noautoshow']) : 0; 
		$custom_posts = isset($post['display_post_select']) ? $post['display_post_select'] : array(); 
		
		$options = array(
				'display_seconds' => $seconds, 
				'display_noautoshow'=> $noauto, 
				'display_post_select' => $custom_posts
		); 
		$this->update_options($options);
	}

 
	public function decide_load($args, $collect_args)
	{
		$template_post_id = isset($collect_args['post_id']) ? $collect_args['post_id'] : false;
		if (! $template_post_id) 
			return null; // null not false, since we can't have an opinion on something not there. 

		$options = $this->get_options($template_post_id); 
		$post_id = MI()->ask('page/post-id'); 
		
		if (isset($options['display_noautoshow']) && $options['display_noautoshow'] == 1) 
		{
			if (! in_array($post_id, $options['display_post_select'] ))
			{
				MI()->tell('template/display/reason', 'Display only on selected posts'); 
				return false; 
			}
		}	
	}
	
	public function template_tag() 
	{
		$args= array(); 
		
		$options = $this->get_options(); 
		
		$delay = isset($options['display_seconds']) ? $options['display_seconds'] : 0; 
		$delay = intval($delay); 
		if ($delay > 0) 
		{
			$args['class'] = ' template-delay'; 
			$args['data']  = array('delay' => $delay); 
			
		}
	
		return $args;
	}
	
	/*public function body_class() 
	{
		$options = $this->get_options(); 	
		$delay = isset($options['display_seconds']) ? $options['display_seconds'] : 0; 
		$delay = intval($delay); 
		
		if ($delay > 0) 
			return 'template-delay'; 		
	} */

	
	public function front_script() 
	{
		$sysslug = MI()->ask('system/slug'); 
		$version = MI()->ask('system/version'); 
		
		$dir_url = trailingslashit(plugin_dir_url(__FILE__)); 
		
		wp_enqueue_script($sysslug .  '-display-pro-front', $dir_url . 'js/front.js', 
						 array($sysslug . '-front'), $version. true );
		wp_enqueue_style($sysslug .  '-display-front', $dir_url . 'css/front.css');
	}
	
	public function ajax_get_terms() 
	{
	
	}
	
	public function get_terms() 
	{
		$terms = get_terms(); 
	
	
	}

	public function options($metabox) 
	{
		$sysslug = MI()->ask('system/slug'); 
		$version = MI()->ask('system/version'); 
		wp_enqueue_style('mi_display_pro', plugin_dir_url(__FILE__) . '/css/display_pro.css'); 	
		
		wp_enqueue_script($sysslug . '-display-pro', plugin_dir_url(__FILE__) . '/js/display_pro.js',
							array($sysslug . '-admin-js', 'underscore'), $version, true ); 
		wp_localize_script($sysslug . '-display-pro', 'miDisplayPro', array(
			'no_title' => __('No Title','maxinbound') ) ); 
				
		$options = $this->get_options(); 
		
		$seconds = isset($options['display_seconds']) ? $options['display_seconds'] : 0; 
		$noauto = isset($options['display_noautoshow']) ? $options['display_noautoshow'] : 0; 
		$custom_posts = isset($options['display_post_select']) ? $options['display_post_select'] : array(); 

		$display_seconds = MI()->editors()->getNewField('display_seconds','text');
		$display_seconds->set('type', 'number');
		$display_seconds->set('title', __('Wait before display in seconds','maxinbound') ); 
		$display_seconds->set('min', 0); 
		$display_seconds->set('value', $seconds);
		
		$metabox->addField('display_seconds', $display_seconds); 
		
		$noautoshow = MI()->editors()->getNewField('display_noautoshow','checkbox');
		$noautoshow->set('label', __('Only show on selected posts and pages', 'maxinbound') ); 
		$noautoshow->set('value', $noauto) ; 
		$noautoshow->set('inputvalue', '1'); 
		$noautoshow->setTemplate('switch.tpl', 'core');
 		$noautoshow->admin();
		
		$metabox->addField('display_noautoshow', $noautoshow); 
		
		if (count($custom_posts) == 0) 
		{
			$selected = '<li class="no-selection"><label>' . __('No Posts Selected','maxinbound') . '</label></li>';
		}
		else
		{
			$selected = ''; 
			
			foreach($custom_posts as $index => $post_id) 
			{
				$post = get_post($post_id); 
				$info = ($post->post_type !== 'post') ? $post->post_type : ''; 
				$post_title = ($post->post_title !== '') ? $post->post_title : __('No Title','maxinbound'); 
				 
				$selected .= '<li><label><i class="icon dashicons dashicons-dismiss"></i><input type="checkbox" name="display_post_select[]" checked value="' . $post_id . '">' . $post_title . '<span class="detail">' . $info  . '</span></label></li>';
			}
		}		

		$selected_post = MI()->editors()->getNewField('custom_selectedpost', 'custom'); 
		$selected_post->set('content',
			wp_nonce_field('internal-linking', '_ajax_linking_nonce',false) .
			'
			<span class="title">' . __('Selected Posts', 'maxinbound') . '</span>
			<ul class="selected_posts"> 
				' . $selected . '
			</ul>');
		
		$metabox->addField('selected_post', $selected_post);

		$search_post_text = MI()->editors()->getNewField('search_post_text', 'text');
		$search_post_text->set('label', __('Search Posts','maxinbound') ); 
		$search_post_text->set('placeholder', __('Search', 'maxinbound') ); 
		
		$metabox->addField('search_post_text', $search_post_text); 

		$search_post = MI()->editors()->getNewField('custom_searchpost', 'custom'); 
		$search_post->set('content',
			'			
			<span class="title">'  . __('Search Posts', 'maxinbound') . '</span>
			<ul class="select_posts">
				<li>' . __('Loading', 'maxinbound') . '</li>
			</ul>');
		
		$metabox->addField('search_post', $search_post);
	}

} // class
