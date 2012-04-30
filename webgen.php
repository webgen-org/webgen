<?php
	/**
	 * @author		Jan Pecha	<janpecha@email.cz>
	 * @copyright	Jan Pecha, 2011
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/webgen/
	 * @package		WebGen - mini web generator
	 * @version		1.0.0.0
	 */
	
	/**
	 *  Variables:
	 *	
	 *  {%title} - page title (H1)
	 *  {%content} - escaped HTML content
	 *	
	 *  {%updateDay}
	 *  {%updateMon}
	 *  {%updateYear}
	 *  {%updateHour}
	 *  {%updateMin}
	 *  {%updateSec}
	 *	
	 *  {%tocUl}	- TOC in HTML <ul><li><a href="#toc">heading</a></li> - NOT IMPLEMENTED
	 *  {%tocOl}	- TOC in HTML <ul><li><a href="#toc">heading</a></li> - NOT IMPLEMENTED
	 *  {%tocA}		- TOC in HTML <a href="#toc">heading</a>
	 *
	 *  {%baseDir}	- Document Root
	 */
	
	include_once 'nette.min.php';
	include_once 'texy.min.php';
	
	use Nette\Utils\Finder;
	
	$dir = getcwd();
	
	@include_once $dir . '/texyConfig.php';
	
	if($dir !== false && is_dir($dir . '/input'))
	{
		$configDefault = array(
			'output' => array(
				'dir' => '/output',
				'ext' => '.php',
				'dirNameFormat' => 'Y-m-d_H-i-s',	//or 'd.m.Y_H.i.s'
			),
			
			'input' => array(
				'mask' => '*.texy',
				'ext' => '.texy',
				'layout' => '@layout.html',
				'cssDir' => false,
				'minifyCSS' => false,
				'baseDir' => '',
			),
		);
		
		$config = @parse_ini_file($dir.'/config.ini', true);
		
		if($config !== false)
		{
			$config = $configDefault + $config;
		}
		else
		{
			$config = $configDefault;
		}
		
		$lastBuild = @file_get_contents($dir.'/lastBuild.dat');
		
		if($lastBuild !== false)
		{
			try
			{
				$lastBuild = new DateTime($lastBuild);
			}
			catch(Exception $e)
			{
				echo $e->getMessage . "\n";
				
				$lastBuild = false;
			}
		}
		
		$layout = @file_get_contents($dir . '/' . $config['input']['layout']);
		
		if($layout === false)
		{
			echo "Fatal - @layout.html not found\n";
			exit;
		}
		
		$texy = new Texy;
		
		texyDefaultConfig($texy);
		
		if(function_exists('texyConfig'))
		{
			texyConfig($texy);
		}
		
		$finder = Finder::findFiles($config['input']['mask']);
		
		if($lastBuild !== false)
		{
			if(filemtime($dir . '/' . $config['input']['layout']) < $lastBuild->getTimestamp())	// TODO: nespolehat se na timestamp
			{
				$finder = $finder->date('>', $lastBuild);
			}
		}
		
		$finder = $finder->from($dir . '/input');
		
		$now = new DateTime;
		
		$nowDir = $now->format($config['output']['dirNameFormat']);
		
		foreach($finder as $key => $file)
		{
#			echo '-----------------------------' . "\n";
#			echo $dir . "\n";
#			echo $config['output']['dir'] . "\n";
			$newDir = $dir . $config['output']['dir'] . '/' . $nowDir . dirname(substr($key, strlen($dir . '/input')));
#			echo "\n";
			echo "File: $key\n";
			
			if(makeDir($newDir))
			{
				$content = file_get_contents($key);
				
				if($content !== false)
				{
					$content = $texy->process($content);
					
					$vars = array(
						'title' => $texy->headingModule->title,
						'content' => $content,
						
						'updateDay' => date('d'),
						'updateMon' => date('m'),
						'updateYear' => date('Y'),
						'updateHour' => date('H'),
						'updateMin' => date('i'),
						'updateSec' => date('s'),
						
						'tocUl' => makeTocUl($texy->headingModule->TOC),
						'tocOl' => makeTocOl($texy->headingModule->TOC),
						'tocA' => makeTocA($texy->headingModule->TOC),
						
						'baseDir' => $config['input']['baseDir'],
					);
					
					$variables = array();
					$values = array();
					
					foreach($vars as $var => $val)
					{
						$variables[] = "{%$var}";
						$values[] = $val;
					}
					
					$page = str_replace($variables, $values, $layout);
					
					if($page !== false)
					{
						file_put_contents($newDir . '/' . $file->getBasename($config['input']['ext']) . $config['output']['ext'], $page);
					}
				}
				else
				{
					echo "Error - process file '$key'";
				}
			}
			else
			{
				echo "Error - make dir for file '$key'\n";
			}
		}
		
		$now = new DateTime;
		
		@file_put_contents($dir.'/lastBuild.dat', $now->format(DateTime::ISO8601));
	}
	else
	{
		echo "Fatal - bad directory (PHP getcwd() error)\n";
	}
	
	function texyDefaultConfig(&$texy)
	{
		$texy->headingModule->generateID = true;
	}
	
	function makeDir($dir)
	{
		echo "Dir: $dir\n";
		if(is_dir($dir))
		{
			return true;
		}
		//sorry
		return mkdir($dir, 0777, true);
	}
	
	function makeTocUl(array $toc)	// not completed
	{
		return '';
		
		$html = "<ul class=\"toc\">\n";
		
		foreach($toc as $item)
		{
			
		}
		
		return $html . "</ul>";
	}
	
	function makeTocOl(array $toc)	// not implemented 
	{
		return '';
	}
	
	function makeTocA(array $toc)
	{
		$html = '';
		
		foreach($toc as $item)
		{
			if(isset($item['el']->attrs['id']))
			{
				$html.= "<a href=\"#".htmlspecialchars($item['el']->attrs['id'])."\">".htmlspecialchars($item['el']->getText())."</a>\n";
			}
		}
		
		return $html;
	}
	
	
