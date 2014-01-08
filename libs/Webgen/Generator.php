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

		/** @var  TexyFilter */
		private $currentTexy;



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
				$this->purgeDir($this->outputFileDirectory, $purge !== TRUE ? $purge : NULL); // purges directory, ignores hidden files (.gitignore, .htaccess)
			} else {
				$this->makeDir($this->outputFileDirectory);
			}

			$this->processCopy();
		}



		private function processCopy()
		{
			if (isset($this->config['input']['copy']) && $this->config['input']['copy']) {
				$mask = $this->config['input']['copy'];
				if ($mask === TRUE) {
					$mask = NULL;
				}

				\Cli\Cli::log("Copy into output directory:");

				foreach (Finder::find($mask)->exclude('*.latte', '*.texy')->from($this->inputDirectory) as $file) {
					$dest = $this->outputFileDirectory . '/' . $this->shortPath((string) $file, $this->inputDirectory);

					if (!$file->isReadable()) {
						\Cli\Cli::log("[skipped] $file");
						continue;
					} elseif ($file->isDir()) {
						$this->makeDir($dest);
					} elseif ($file->isFile()) {
						$this->makeDir(dirname($dest));
						if (@stream_copy_to_stream(fopen($file, 'r'), fopen($dest, 'w')) === FALSE) {
							\Cli\Cli::log("[error]   $file");
							continue;
						}
					}

					\Cli\Cli::log($file);
				}

				\Cli\Cli::log('');
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
				$texyFilter->addHandler('phrase', callback($this, 'phraseHandler'));
				$texyFilter->addHandler('linkReference', callback($this, 'linkReferenceHandler'));
				$texyFilter->addHandler('figure', callback($this, 'figureHandler'));
				$texyFilter->addHandler('image', callback($this, 'imageHandler'));

				$filters[] = $texyFilter;
				$this->currentTexy = $texyFilter;
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



		/**
		 * @param  TexyHandlerInvocation  handler invocation
		 * @param  TexyImage
		 * @param  TexyLink|NULL
		 * @return TexyHtml|string|FALSE
		 */
		public function imageHandler($invocation, $image, $link)
		{
			$this->texyLinkUrl($link);
			$res = $this->texyImageUrl($image);

			if ($res) {
				return $this->solveTexyImage($invocation, $image, $link);
			}
			return $invocation->proceed();
		}



		/**
		 * @param  TexyHandlerInvocation  handler invocation
		 * @param  string
		 * @param  string
		 * @param  TexyModifier
		 * @param  TexyLink|NULL
		 * @return TexyHtml|string|FALSE
		 */
		public function phraseHandler($invocation, $phrase, $content, $modifier, $link)
		{
			$this->texyLinkUrl($link);
			return $invocation->proceed();
		}



		/**
		 * @param  TexyHandlerInvocation  handler invocation
		 * @param  TexyLink
		 * @param  string
		 * @return TexyHtml|string|FALSE
		 */
		public function linkReferenceHandler($invocation, $link, $content)
		{
			$this->texyLinkUrl($link);
			return $invocation->proceed();
		}



		/**
		 * @param  TexyHandlerInvocation  handler invocation
		 * @param  TexyImage
		 * @param  TexyLink|NULL
		 * @param  string
		 * @param  TexyModifier
		 * @return TexyHtml|string|FALSE
		 */
		public function figureHandler($invocation, $image, $link, $content, $modifier)
		{
			$res = $this->texyImageUrl($image);
			$this->texyLinkUrl($link);

			if ($res) {
				return $this->solveTexyFigure($invocation, $image, $link, $content, $modifier);
			}

			return $invocation->proceed();
		}



		/**
		 * @param  TexyLink|NULL
		 * @return bool
		 */
		protected function texyLinkUrl($link)
		{
			if ($link instanceof \TexyLink) {
				if (isset($link->URL[0]) && $link->URL[0] === '@') {
					$link->URL = Webgen::makeRelativePath($this->currentFile, substr($link->URL, 1));
					return TRUE;
				}
			}
			return FALSE;
		}



		/**
		 * @param  TexyImage
		 * @return bool
		 */
		protected function texyImageUrl($image)
		{
			if (isset($image->URL[0]) && $image->URL[0] === '@') {
				$image->URL = Webgen::makeRelativePath($this->currentFile, substr($image->URL, 1));
				return TRUE;
			}
			return FALSE;
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



		protected function purgeDir($directory, $mask)
		{
			@mkdir($directory, 0777, TRUE); // @ - directory may already exist

			\Cli\Cli::log("Purge: \n$directory");
			foreach (Finder::find($mask)->from($directory)->childFirst() as $entry) {
				$res = TRUE;
				if (substr($entry->getBasename(), 0, 1) === '.') { // . or .. or .gitignore (hidden files)
					// ignore
					\Cli\Cli::log("[ignored] $entry");
					continue;
				} elseif ($entry->isDir()) {
					$res = @rmdir($entry);
				} else {
					$res = @unlink($entry);
				}

				if (!$res) {
					\Cli\Cli::log("[skipped] $entry");
					continue;
				}
				\Cli\Cli::log("[removed] $entry");
			}
			\Cli\Cli::log(''); // empty line
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



		private function solveTexyImage($invocation, $image, $link)
		{
			if ($image->URL == NULL) return FALSE;

			$tx = $this->currentTexy;

			$mod = $image->modifier;
			$alt = $mod->title;
			$mod->title = NULL;
			$hAlign = $mod->hAlign;
			$mod->hAlign = NULL;

			$el = \TexyHtml::el('img');
			$el->attrs['src'] = NULL; // trick - move to front
			$mod->decorate($tx, $el);
			$el->attrs['src'] = $image->URL;//;Texy::prependRoot($image->URL, $this->root);
			if (!isset($el->attrs['alt'])) {
				if ($alt !== NULL) $el->attrs['alt'] = $tx->typographyModule->postLine($alt);
				else $el->attrs['alt'] = $tx->imageModule->defaultAlt;
			}

			if ($hAlign) {
				$var = $hAlign . 'Class'; // leftClass, rightClass
				if (!empty($tx->imageModule->$var)) {
					$el->attrs['class'][] = $tx->imageModule->$var;

				} elseif (empty($tx->alignClasses[$hAlign])) {
					$el->attrs['style']['float'] = $hAlign;

				} else {
					$el->attrs['class'][] = $tx->alignClasses[$hAlign];
				}
			}

			if (!is_int($image->width) || !is_int($image->height) || $image->asMax) {
				// autodetect fileRoot
				if ($tx->imageModule->fileRoot === NULL && isset($_SERVER['SCRIPT_FILENAME'])) {
					$tx->imageModule->fileRoot = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $tx->imageModule->root;
				}

				// detect dimensions
				// absolute URL & security check for double dot
				if (\Texy::isRelative($image->URL)) {
					$file = rtrim($this->outputFileDirectory, '/\\') . '/' . rtrim(dirname($this->currentFile), '/\\') . '/' . $image->URL;
					if (@is_file($file)) { // intentionally @
						$size = @getImageSize($file); // intentionally @
						if (is_array($size)) {
							if ($image->asMax) {
								$ratio = 1;
								if (is_int($image->width)) $ratio = min($ratio, $image->width / $size[0]);
								if (is_int($image->height)) $ratio = min($ratio, $image->height / $size[1]);
								$image->width = round($ratio * $size[0]);
								$image->height = round($ratio * $size[1]);

							} elseif (is_int($image->width)) {
								$ratio = round($size[1] / $size[0] * $image->width);
								$image->height = round($size[1] / $size[0] * $image->width);

							} elseif (is_int($image->height)) {
								$image->width = round($size[0] / $size[1] * $image->height);

							} else {
								$image->width = $size[0];
								$image->height = $size[1];
							}
						}
					}
				}
			}

			$el->attrs['width'] = $image->width;
			$el->attrs['height'] = $image->height;

			// onmouseover actions generate
			if ($image->overURL !== NULL) {
				$overSrc = \Texy::prependRoot($image->overURL, $tx->imageModule->root);
				$el->attrs['onmouseover'] = 'this.src=\'' . addSlashes($overSrc) . '\'';
				$el->attrs['onmouseout'] = 'this.src=\'' . addSlashes($el->attrs['src']) . '\'';
				$el->attrs['onload'] = str_replace('%i', addSlashes($overSrc), $tx->imageModule->onLoad);
				$tx->summary['preload'][] = $overSrc;
			}

			$tx->summary['images'][] = $el->attrs['src'];

			if ($link) return $tx->linkModule->solve(NULL, $link, $el);

			return $el;
		}



		private function solveTexyFigure($invocation, \TexyImage $image, $link, $content, $mod)
		{
			$tx = $this->currentTexy;

			$hAlign = $image->modifier->hAlign;
			$image->modifier->hAlign = NULL;

			$elImg = $this->solveTexyImage(NULL, $image, $link); // returns TexyHtml or false!
			if (!$elImg) return FALSE;

			$el = \TexyHtml::el('div');
			if (!empty($image->width) && $tx->figureModule->widthDelta !== FALSE) {
				$el->attrs['style']['width'] = ($image->width + $tx->figureModule->widthDelta) . 'px';
			}
			$mod->decorate($tx, $el);

			$el[0] = $elImg;
			$el[1] = \TexyHtml::el('p');
			$el[1]->parseLine($tx, ltrim($content));

			$class = $tx->figureModule->class;
			if ($hAlign) {
				$var = $hAlign . 'Class'; // leftClass, rightClass
				if (!empty($tx->figureModule->$var)) {
					$class = $tx->figureModule->$var;

				} elseif (empty($tx->alignClasses[$hAlign])) {
					$el->attrs['style']['float'] = $hAlign;

				} else {
					$class .= '-' . $tx->alignClasses[$hAlign];
				}
			}
			$el->attrs['class'][] = $class;

			return $el;
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

