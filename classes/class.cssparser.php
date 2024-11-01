<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');


// Class to expose CSSParser to the plugin. 
class cssParser
{
	protected $cssDoc = null;
	protected $asset_path = null; 

	protected $block = array();
	protected $selectors = array();
	
	protected $cached_declarations = array();

	public static function autoload($className) 
	{
		if (strpos($className, 'Sabberworm') === false) 
			return;
			
		$path_name = str_replace('\\','/', $className) . '.php';
		$asset_path = plugin_dir_path(WM_ROOT_FILE) . 'assets/libraries/' . $path_name;

		require_once($asset_path);
	}

	/** Parse CSS Rule declarations into a PHP array */ 
	public function parse($css)
	{	
		$parser = new \Sabberworm\CSS\Parser($css); 
		$cssDoc = $parser->parse();
		$this->cssDoc = $cssDoc;
		$this->parseData();		
	}

	protected function parseData()
	{
		//$contents = $cssDoc->getContents(); 
		
		$blocks = $this->cssDoc->getAllDeclarationBlocks(); 
	
		$selectorData = array(); 
		
		foreach($blocks as $block_index => $oBlock) 
		{	
			foreach($oBlock->getSelectors() as $oSelector)
			{
				$selector = trim($oSelector->getSelector()); 

				if (! isset($selectorData[$selector]))
					$selectorData[$selector] = array($block_index); 
				else
					$selectorData[$selector][] = $block_index;
			}
		}

		$this->blocks = $blocks;
		$this->selectors = $selectorData;
	
	}
	
	/** Function to get rule declaration based on selector. */
	public function findWithSelector($selector)
	{
		$selectors = $this->selectors; 
		$data = array(); 
	
		$keys = array_keys ($this->selectors);

		//$pattern = "/(?<![\w-])$selector(?![\w-])/i"; // full word based search - can't handle dots - of course 
		$pattern = "/$selector/i"; 
		$matches = preg_grep('' . $pattern . '', $keys);
		//$matches = 
		//array_search($selector, $keys); 
		
		$indexes = array(); 
		foreach($matches as $match)
		{
			$indexes = array_merge($indexes, $selectors[$match]); 
		}
		return $indexes;			
	}
	
	/** Find the most appropiate CSS style for multiple classes combined */ 
	
	public function getNearestDeclaration($tag_class = '')
	{
 		if (isset($this->cached_declarations[$tag_class]))  // cache since every field does this query a few times - for each rule.
 			return $this->cached_declarations[$tag_class]; 		
 
 		$class_array = explode(" ", trim($tag_class));

		$declarations = array(); 
		foreach($class_array as $class) 
		{
			if ($class == '')  // no empties please
				continue;
			
			$class = '.' . $class; // Classes are classes here, noted with a dot in css. 

			$rules = array();
			$indexes = $this->findWithSelector($class); 			

			if ( count($indexes) > 0)
			{

				foreach($indexes as $index) 
				{
					$oBlock = $this->blocks[$index];
					$selectors = $oBlock->getSelector(); 
					 
					foreach($selectors as $oSelector) 
					{					
						$selector = $oSelector->getSelector(); 
						//$spec = $oSelector->getSpecificity();  // non-comprehensible function
						$score = $this->scoreSelector($selector, $tag_class);
						if ($score <= 0) // zero or lower is either mismatch or no occurence.
							continue; // nope
													
						foreach($oBlock->getRules() as $rule) 
						{

							$value = $rule->getValue();
							$declarations[] = array(
							//	'pseudo' => $spec,  
								'score' => $score, 
								'selector' => $selector,
								'rule' => $rule->getRule(), 
								'value' => (string) $rule->getValue()
							 ); 
						}
					}
				}
			}	
		} 
		
 		if (count($declarations) > 0) 
 		{
			$this->cached_declarations[$tag_class] = $declarations;			 			
 		}

 			
		return $declarations;
	}
	
	/** Get all rules by a specific rulename ( e.g. 'font-family or font-'  ) */ 
	public function getAllOfRule($rule)
	{
		$ruleset = $this->cssDoc->getAllRuleSets(); 
		$i = 0; 
		$ruleArray = array(); 
		
		foreach($ruleset as $oSet)
		{
			$rules = $oSet->getRules($rule); 

			foreach($rules as $oRule) 
			{

				$ruleArray[$i][(string)$oRule->getRule()] = (string) $oRule->getValue(); 
			}
			
			$i++;

		}
		
		return $ruleArray;
		
	}
	
	public function scoreSelector($selector, $tagClass) 
	{
		$score = 0; 
		$penalty = 0; 
		
		$tag_pseudo = $this->hasPseudo($tagClass);
		
		/* Check for pseudo (hover etc) on the tag and selector. If the tag has no pseudo it's a mismatch. If the tag *has* a pseudo but the selector doesn't apply penalty but allow to 'trickle' down if the pseudo itself doesn't exist or doesn't have required css properties
		*/
		if ( $tag_pseudo  !== $this->hasPseudo($selector) )
		{
			if (! $tag_pseudo)
				return -1; // pseudo mismatch
			else
				$penalty = strlen($tag_pseudo);
		}
		
		$tag_array = explode(" ", trim($tagClass) );
		$tag_array = array_filter($tag_array); 
 
		foreach($tag_array as $tag ) 
		{
			
			$pos = strpos($selector, $tag);  // assume if tag is found on higher pos, it's lower ( and more specific ) in the CSS tree
			if ($pos === false) $pos = 0; 
			if ($pos == 0) $pos = 1; // get some score for starting on first pos. 
			$score += $pos; 
		}
		
		return $score - $penalty;
		
	}
	
	public function hasPseudo($item)
	{
		$pseudo_pattern = '/:(.*)$/i';
		preg_match($pseudo_pattern, $item, $matches); 

		if (count($matches) > 0)
			return $matches[1]; 
		else
			return false;
		
	}

	public function render() 
	{
		return $this->cssparser->render(); 
	}


} // class


spl_autoload_register(__NAMESPACE__ . '\cssParser::autoload');
