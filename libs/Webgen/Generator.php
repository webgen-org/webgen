<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 * @version		2012-08-08-1
	 */
	
	namespace Webgen;
	
	class Generator extends \Nette\Object
	{
		/** @var array */
		public $config = NULL;
		
		/** @var string */
		public $outputDirectory;
		
		/** @var string */
		public $inputDirectory;
		
		/** @var string */
		public $layoutPath = FALSE;
		
		/** @var string */
		public $outputFileDirectory;
		
		/** @var  string|NULL */
		public $topTitle = NULL;
		
		/** @var  FileTemplate */
		protected $template;
		
		
		
		public function prepare()
		{
			if($this->config === NULL)
			{
				throw new \Exception('No config.');
			}
			
			// Make output directory
			$this->outputFileDirectory = $this->outputDirectory . '/' . date('Y-m-d_H-i-s');
			$this->makeDir($this->outputFileDirectory);
			
		}
		
		
		
		public function generate($filePath, \SplFileInfo $fileInfo)
		{
			$this->topTitle = NULL;
			$this->template = $template = $this->createTemplate();
			$texy = new \Webgen\Texy($this->config['variables']['baseDir']);
			
			$titleBlock = '';
			$filters = array();
			
			$template->setFile($filePath);
			
			// Validate file ext & specific filter registration (Texy! processing, ...)
			$fileExtension = $fileInfo->getExtension();
			
			if($fileExtension === 'texy')
			{
				$texyFilter = new TexyFilter($this->config['variables']['baseDir']);
				$texyFilter->addHandler('script', callback($this, 'scriptHandler'));
				$texyFilter->addHandler('heading', callback($this, 'headingHandler'));
				
				$filters[] = $texyFilter;
				$filters[] = callback($this, 'texyAfterFilter');
			}
			elseif($fileExtension === 'latte')
			{
				$filters[] = function ($s) {
					return strtr($s, array(
						'{texy}' => '{block |texy:$baseDir}',
						'{/texy}' => '{/block}',
					));
				};
			}
			else
			{
				throw new \Exception("Unknow file extension '{$fileInfo->getExtension()}'");
			}
			
			// Set template variables
			if(isset($this->config['variables']))
			{
				foreach($this->config['variables'] as $name => $value)
				{
					$template->$name = $value;
				}
			}
			
			// Register Texy! Helper
			$template->registerHelper('texy', array($texy, 'process'));
			
			// Register MagicFilter (code modification for Latte, for example add layout macro,...) TODO: better name?
			$filters[] = callback($this, 'templateMagicFilter');
			
			// Render to file
			$fileName = $this->formatOutputFilePath($filePath, $fileInfo);
			
			// -- Create subdirectory
			$this->makeDir(dirname($fileName));
			
			// -- Apply filters
			$content = $template->getSource();
			
			foreach($filters as $filter)
			{
				$content = $filter($content);
			}
			
			$template->setSource($content);
			
			// -- Save to file
			$template->save($fileName);
		}
		
				
		
		/**
		 * @param  TexyHandlerInvocation  handler invocation
		 * @param  int
		 * @param  string
		 * @param  TexyModifier
		 * @param  bool
		 * @return TexyHtml|string|FALSE
		 */
		public function headingHandler($invocation, $level, $content, $modifier, $isSurrounded)
		{
			if($this->topTitle === NULL/* && $level == 0*/)
			{
				$this->topTitle = $content;
#				echo "\na\n";
				return '';
			}
			
			return $invocation->proceed();
		}
		
		
		
		/**
		 * @param TexyHandlerInvocation  handler invocation
		 * @param string  command
		 * @param array   arguments
		 * @param string  arguments in raw format
		 * @return TexyHtml|string|FALSE
		 */
		public function scriptHandler($invocation, $cmd, $args, $raw)
		{
#			echo 'CMD: ', $cmd, "\n";
#				echo 'ARGS: ', $args[0], "\n";
#				echo 'RAW: ', $raw, "\n";
#				echo "$var\n--\n";
			if($cmd[0] === '_')
			{
				$var = substr($cmd, 1);
				
				if(isset($args[0]))
				{
					$this->template->$var = $args[0];
					
					return '';
				}
				else
				{
					return (string)$this->template->$var;
				}
			}
			
			return $invocation->proceed();
		}
		
		
		
		public function texyAfterFilter($s)
		{
			return '{block #title}'
				. $this->topTitle
				. "{/block}\n"
				. $s;
		}
		
		
		
		protected function makeDir($directory)
		{
			if(is_dir($directory))
			{
				return true;
			}
			
			return mkdir($directory, 0777, true);
		}
		
		
		
		protected function createTemplate()
		{
			$template = new FileTemplate;
			$template->setCacheStorage(new \Nette\Caching\Storages\MemoryStorage);
			
			// Register Latte
			$template = $this->templateRegisterLatte($template);
			
			// Register helpers
			$template->registerHelperLoader('\Nette\Templating\Helpers::loader');
			
			return $template;
		}
		
		
		
		protected function templateRegisterLatte($template)
		{
			return $template->registerFilter(new \Nette\Latte\Engine);
		}
		
		
		
		public function templateMagicFilter($s)
		{
			$content = "{layout '{$this->layoutPath}'}\n" . $s;
			
			return $content;
		}
		
		
		
		protected function formatOutputFilePath($filePath, \SplFileInfo $fileInfo)
		{
			$name = $this->outputFileDirectory;
			
			$name .= dirname(substr($filePath, strlen($this->inputDirectory)));
			$name .= '/' . $fileInfo->getBasename($fileInfo->getExtension());
			
			return $name . $this->config['output']['ext'];
		}
		
		
		
		/**
		 * @param	string
		 * @return	\DateTime|FALSE
		 */
		public static function getLastBuildDate($filePath)
		{
			$lastBuild = @file_get_contents($filePath);
			
			if($lastBuild === FALSE)
			{
				//throw new \Exception('File error - load last build date'); - poprve nemusi existovat!!!
				return FALSE;
			}
			
			$date = FALSE;
			
			try
			{
				$date = new \DateTime($lastBuild);
			}
			catch(\Exception $e)
			{
				return FALSE;
			}
			
			return $date;
		}
		
		
		
		/**
		 * @param	string
		 * @param	\DateTime
		 * @return	void
		 */
		public static function setLastBuildDate($filePath, \DateTime $datetime)
		{
			file_put_contents($filePath, $datetime->format(\DateTime::ISO8601));
		}
	}

