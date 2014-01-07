<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use Nette;

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

		/** @var  FileTemplate */
		protected $template;

		/** @var  string|NULL */
		private $currentFile;

		/** @var  array */
		private $currentFileConfig;



		public function prepare($versioned, $purge = FALSE)
		{
			if($this->config === NULL)
			{
				throw new \Exception('No config.');
			}

			// Make output directory
			$this->outputFileDirectory = $this->outputDirectory;
			if ($versioned) {
				$this->outputFileDirectory .= '/' . date('Y-m-d_H-i-s');
			}

			if ($purge) {
				$this->purgeDir($this->outputFileDirectory); // purges directory, ignores hidden files (.gitignore, .htaccess)
			} else {
				$this->makeDir($this->outputFileDirectory);
			}
		}



		public function getCurrentFile()
		{
			return $this->currentFile;
		}



		public function addCurrentFileConfig(array $config)
		{
			if (isset($config['ext'])) {
				if (!is_string($config['ext']) && !is_numeric($config['ext'])) {
					throw new WebgenException('Output file extension must be string.');
				}

				$this->currentFileConfig['ext'] = (string) $config['ext'];
			}
		}



		public function generate($filePath, \SplFileInfo $fileInfo, Webgen $webgenHelper)
		{
			$this->currentFileConfig = $this->config['output'];
			$this->template = $template = $this->createTemplate();
			$texy = new \Webgen\Texy($this->config['variables']['baseDir']);

			$titleBlock = '';
			$filters = array();

			$template->setFile($filePath);
			$template->webgen = $webgenHelper;

			// set current file
			$this->currentFile = $this->shortPath($filePath, $this->inputDirectory);

			// Validate file ext & specific filter registration (Texy! processing, ...)
			$fileExtension = $fileInfo->getExtension();

			if($fileExtension === 'texy')
			{
				$texyFilter = new TexyFilter($this->config['variables']['baseDir']);
				$texyFilter->addHandler('script', callback($this, 'scriptHandler'));

				$filters[] = $texyFilter;
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
			$content = $template->getSource();

			foreach($filters as $filter)
			{
				$content = $filter($content);
			}

			$template->setSource($content);

			$content = $template->__toString(TRUE); // render to var
			$fileName = $this->formatOutputFilePath($filePath, $fileInfo); // format output filepath
			$this->saveFile($fileName, $content); // save into file
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
			elseif($cmd[0] === '$') {
				$pos = strpos($cmd, ':');

				if ($pos === FALSE) {
					$pos = strpos($cmd, '=');
				}

				if ($pos !== FALSE) {
					$var = trim(substr($cmd, 1, $pos - 1));
					$value = trim(substr($cmd, $pos + 1));

					if ($var !== '') {
						$this->template->$var = $value;
						return '';
					}
				} else {
					$var = trim(substr($cmd, 1));
					return (string) $this->template->$var;
				}
			}
			elseif($cmd === 'webgen')
			{
				$config = array();

				foreach ($args as $arg) {
					$pos = strpos($arg, ':');

					if ($pos !== FALSE) {
						$name = trim(substr($arg, 0, $pos));
						$value = trim(substr($arg, $pos + 1));
						$config[$name] = $value;
					}
				}

				$this->addCurrentFileConfig($config);
				return '';
			}

			return $invocation->proceed();
		}



		protected function makeDir($dir)
		{
			if (!is_dir($dir) && !@mkdir($dir, 0777, TRUE)) { // intentionally @; not atomic
				throw new Nette\IOException("Unable to create directory '$dir'.");
			}
		}



		protected function saveFile($file, $content)
		{
			$this->makeDir(dirname($file));

			if (file_put_contents($file, $content) === FALSE) {
				throw new Nette\IOException("Unable to save file '$file'.");
			}
		}



		protected function purgeDir($directory)
		{
			@mkdir($directory, 0777, TRUE); // @ - directory may already exist
			foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::CHILD_FIRST) as $entry)
			{
				if (substr($entry->getBasename(), 0, 1) === '.') // . or .. or .gitignore (hidden files)
				{
					// ignore
				}
				elseif ($entry->isDir())
				{
					rmdir($entry);
				}
				else
				{
					unlink($entry);
				}
			}
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
			$latte = new \Nette\Latte\Engine;
			$xhtml = isset($this->config['output']['xhtml']) && $this->config['output']['xhtml'];

			if(!$xhtml)
			{
				$latte->getCompiler()->defaultContentType = \Nette\Latte\Compiler::CONTENT_HTML;
			}

			\Nette\Utils\Html::$xhtml = $xhtml;

			// own macros
			$set = new Nette\Latte\Macros\MacroSet($latte->compiler);
			$set->addMacro('webgen', '$webgen->addCurrentFileConfig(%node.array)');
			$macroLink = 'echo %escape(%modify($webgen->createRelativeLink(%node.word)))';
			$set->addMacro('link', $macroLink);
			$set->addMacro('href', NULL, NULL, ' ?> href="<?php ' . $macroLink . ' ?>"<?php ');

			return $template->registerFilter($latte);
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

			return $name . $this->currentFileConfig['ext'];
		}



		private function shortPath($filepath, $rootDirectory)
		{
			$rootDirectory = rtrim($rootDirectory, '/') . '/';
			return substr($filepath, strlen($rootDirectory));
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

