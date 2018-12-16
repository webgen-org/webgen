<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use Nette;
	use Nette\Utils\Finder as NFinder;

	class Generator
	{
		use \Nette\SmartObject;

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

		/** @var  Template */
		protected $template;

		/** @var  \Latte\Engine */
		protected $latte;

		/** @var  string|NULL */
		private $currentFile;

		/** @var  string|NULL */
		private $currentOutputFile;

		/** @var  \SplFileInf */
		private $currentFileInfo;

		/** @var  array */
		private $currentFileConfig;

		/** @var  WebgenPage */
		private $currentPage;

		/** @var  array */
		private $currentPageDefaultProperties = array();

		/** @var  int */
		private $currentIteration;

		/** @var  bool */
		private $repeatGenerating;

		/** @var  TexyFilter */
		private $currentTexy;

		/** @var  \Webgen\Highlighter */
		private $highlighter;

		/** @var  Pages */
		private $pages;


		public function __construct()
		{
			$this->highlighter = new \Webgen\Highlighter;
			$this->pages = new \Webgen\Pages;
		}



		public function prepare($purge = FALSE)
		{
			if($this->config === NULL)
			{
				throw new \Exception('No config.');
			}

			// Make output directory
			$this->outputFileDirectory = $this->outputDirectory;

			if ($purge) {
				$this->purgeDir($this->outputFileDirectory, $purge !== TRUE ? $purge : NULL); // purges directory, ignores hidden files (.gitignore, .htaccess)
			} else {
				$this->makeDir($this->outputFileDirectory);
			}

			$this->processCopy();
			$this->pages->resetPages();
		}



		private function processCopy()
		{
			if (isset($this->config['input']['copy']) && $this->config['input']['copy']) {
				$mask = $this->config['input']['copy'];
				if ($mask === TRUE) {
					$mask = NULL;
				}

				\Cli\Cli::log("Copy into output directory:");

				foreach (NFinder::find($mask)->exclude('*.latte', '*.texy')->from($this->inputDirectory) as $file) {
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



		public function getSourceDirectory()
		{
			return $this->inputDirectory;
		}



		public function getCurrentFile()
		{
			return $this->currentFile;
		}



		public function getCurrentOutputFile()
		{
			if (!is_string($this->currentOutputFile)) {
				$directory = dirname($this->currentFile) . '/';

				if ($this->currentFileConfig['filename'] !== NULL) {
					$this->currentOutputFile = self::absolutizePath($directory . $this->currentFileConfig['filename']);
					return $this->currentOutputFile;
				}

				if ($this->currentFileConfig['name'] !== NULL) {
					$this->currentOutputFile = $this->currentFileConfig['name'];
				} else {
					$this->currentOutputFile = $this->currentFileInfo->getBasename('.' . $this->currentFileInfo->getExtension())
						. ($this->currentIteration > 1 ? "-{$this->currentIteration}" : '');
				}

				// add file extension
				$this->currentOutputFile = self::absolutizePath($directory . $this->currentOutputFile . '.' . $this->currentFileConfig['ext']);
			}

			return $this->currentOutputFile;
		}



		public function getCurrentFileLink()
		{
			if (isset($this->currentFileConfig['fileLink'])) {
				return $this->currentFileConfig['fileLink'];
			}
			return $this->getCurrentOutputFile();
		}



		public function getCurrentIteration()
		{
			return $this->currentIteration;
		}



		public function isCurrentPageActive()
		{
			return $this->currentFileConfig['active'];
		}



		public function addCurrentFileConfig(array $config)
		{
			if (isset($config['ext'])) {
				if (!is_string($config['ext']) && !is_numeric($config['ext'])) {
					throw new WebgenException('Output file extension must be string.');
				}

				$this->currentFileConfig['ext'] = (string) $config['ext'];
				$this->currentOutputFile = NULL;
			}

			if (isset($config['repeatGenerating'])) {
				$this->repeatGenerating = (bool) $config['repeatGenerating'];
			}

			if (array_key_exists('filename', $config)) {
				if ($config['filename'] === NULL) {
					$this->currentFileConfig['filename'] = NULL;
				} else {
					if (!is_string($config['filename']) && !is_numeric($config['filename'])) {
						throw new WebgenException('Output filename must be string.');
					}
					$this->currentFileConfig['filename'] = (string) $config['filename'];
				}
				$this->currentOutputFile = NULL;
			}

			if (array_key_exists('name', $config)) {
				if ($config['name'] === NULL) {
					$this->currentFileConfig['name'] = NULL;
				} else {
					if (!is_string($config['name']) && !is_numeric($config['name'])) {
						throw new WebgenException('Output filename must be string.');
					}
					$this->currentFileConfig['name'] = (string) $config['name'];
				}
				$this->currentOutputFile = NULL;
			}

			if (array_key_exists('fileLink', $config)) {
				if ($config['fileLink'] === NULL) {
					$this->currentFileConfig['fileLink'] = NULL;
				} else {
					if (!is_string($config['fileLink']) && !is_numeric($config['fileLink'])) {
						throw new WebgenException('Output filename must be string.');
					}
					$this->currentFileConfig['fileLink'] = (string) $config['fileLink'];
				}
			}

			if (array_key_exists('active', $config)) {
				if (is_string($config['active'])) {
					$config['active'] = strtolower($config['active']);

					if ($config['active'] === 'off' || $config['active'] === 'no' || $config['active'] === 'false') {
						$config['active'] = FALSE;
					}
				}
				$this->currentFileConfig['active'] = $config['active'];
			}
		}


		public function getPages(array $orderBy = array(), $parent = '')
		{
			return $this->pages->getPages($orderBy, $parent);
		}


		public function generate($filePath, \SplFileInfo $fileInfo, Webgen $webgenHelper)
		{
			$this->currentFileConfig = $this->config['output'];
			$this->currentFileConfig['filename'] = NULL;
			$this->currentFileConfig['name'] = NULL;
			$this->currentFileConfig['active'] = TRUE;
			$this->currentPageDefaultProperties = array(
				'date' => new \DateTime,
			);
			$this->currentIteration = 1;
			$this->currentFileInfo = $fileInfo;
			$this->latte = $latte = $this->createLatte();
			$texy = new \Webgen\Texy($this->config['variables']['baseDir']);
			$texy->headingModule->top = 2;
			$this->configureTexy($texy);

			$filters = array();

			$this->template = $template = new Template($filePath, file_get_contents($filePath), array(
				'webgen' => $webgenHelper,
			));

			// set current file
			$this->currentFile = $this->shortPath($filePath, $this->inputDirectory);

			// Validate file ext & specific filter registration (Texy! processing, ...)
			$fileExtension = $fileInfo->getExtension();

			if($fileExtension === 'texy')
			{
				$texyFilter = new TexyFilter($this->config['variables']['baseDir']);
				$texyFilter->addHandler('script', array($this, 'scriptHandler'));
				$this->configureTexy($texyFilter);

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
				$template->addParameters($this->config['variables']);
			}

			// Set default variables
			$template->addParameters(array(
				'productionMode' => $this->config['output']['productionMode'],
			));

			// Register Texy! Helper
			$latte->addFilter('texy', function (\Latte\Runtime\FilterInfo $info, $s, $singleLine = false) use ($texy) {
				return new \Latte\Runtime\Html($texy->process($s, $singleLine));
			});

			// Render to file
			$content = $template->getContent();

			foreach($filters as $filter)
			{
				$content = $filter($content);
			}

			$this->repeatGenerating = FALSE;

			do {
				$this->currentPage = new WebgenPage($this);

				foreach ($this->currentPageDefaultProperties as $defaultProperty => $defaultValue) {
					$this->currentPage->setProperty($defaultProperty, $defaultValue);
				}

				$this->currentOutputFile = NULL;
				$tpl = clone $template;
				$tpl->setParameter('currentPage', $this->currentPage);
				$tpl->setContent($content);
				$latte->getLoader()->setTemplate($tpl);
				$output = $latte->renderToString($tpl->getPath(), $tpl->getParameters()); // render to var

				if ($this->currentFileConfig['active']) {
					$fileName = $this->getCurrentOutputFilePath(); // format output filepath
					$this->saveFile($fileName, $output); // save into file
					$this->pages->addPage($this->getCurrentOutputFile(), $this->currentPage->getProperties());

				} else {
					\Cli\Cli::log("[inactive] {$this->currentFile}");
				}

				$this->currentIteration++;
			} while ($this->repeatGenerating);
		}



		/**
		 * @param Texy\HandlerInvocation  handler invocation
		 * @param string  command
		 * @param array   arguments
		 * @param string  arguments in raw format
		 * @return Texy\HtmlElement|string|FALSE
		 */
		public function scriptHandler($invocation, $cmd, $args, $raw)
		{
			if($cmd[0] === '_')
			{
				$var = substr($cmd, 1);

				if(isset($args[0]))
				{
					$this->template->setParameter($var, $args[0]);

					return '';
				}
				else
				{
					return (string)$this->template->getParameter($var);
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
						$this->template->setParameter($var, $value);
						return '';
					}
				} else {
					$var = trim(substr($cmd, 1));
					return (string) $this->template->getParameter($var);
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
			elseif(substr($cmd, 0, 5) === 'page.' && empty($args))
			{
				$property = trim(substr($cmd, 5));

				if ($property !== '') {
					$parts = explode(' ', $property, 2);

					if (count($parts) === 2) {
						$parts[1] = $this->convertType(trim($parts[1]));

						if (substr($parts[0], -2) === '[]') { // collection
							$parts[0] = trim(substr($parts[0], 0, -2));
							$values = isset($this->currentPageDefaultProperties[$parts[0]]) ? $this->currentPageDefaultProperties[$parts[0]] : array();

							if (!is_array($values)) {
								$values = array($values);
							}

							$values[] = $parts[1];
							$parts[1] = $values;
						}

						$this->currentPageDefaultProperties[$parts[0]] = $parts[1];
						return '';
					}
				}
			}

			return $invocation->proceed();
		}


		private function convertType($value)
		{
			// bool
			$tmp = strtolower($value);

			if ($tmp === 'true' || $tmp === 'on' || $tmp === 'yes') {
				return TRUE;

			} elseif ($tmp === 'false' || $tmp === 'off' || $tmp === 'no') {
				return FALSE;
			}

			// datetime
			if (substr($value, 0, 3) !== '000') { // 0000-00-00
				if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $value)) { // Y-m-d H:i:s
					return new \DateTime($value);

				} elseif (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $value)) { //  // Y-m-d H:i
					$datetime = new \DateTime($value);
					$datetime->setTime($datetime->format('H'), $datetime->format('i'), 0);
					return $datetime;

				} elseif (preg_match('/\d{4}-\d{2}-\d{2}/', $value)) { //  // Y-m-d
					$datetime = new \DateTime($value);
					$datetime->setTime(0, 0, 0);
					return $datetime;
				}
			}

			// int
			$tmp = (int) $value;

			if (((string) $tmp) === $value) {
				return $tmp;
			}

			// floatwebgenHelper
			$tmp = (float) $value;

			if (((string) $tmp) === $value) {
				return $tmp;
			}

			// string
			return $value;
		}



		/**
		 * @param  Texy\HandlerInvocation  handler invocation
		 * @param  Texy\Image
		 * @param  Texy\Link|NULL
		 * @return Texy\HtmlElement|string|FALSE
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
		 * @param  Texy\HandlerInvocation  handler invocation
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
		 * @param  Texy\HandlerInvocation  handler invocation
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
		 * @param  Texy\HandlerInvocation  handler invocation
		 * @param  Texy\Image
		 * @param  Texy\Link|NULL
		 * @param  string
		 * @param  Texy\Modifier
		 * @return Texy\HtmlElement|string|FALSE
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
		 * @param  Texy\Image
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

			\Cli\Cli::log("Purge:\n$directory");
			foreach (NFinder::find($mask)->from($directory)->childFirst() as $entry) {
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


		protected function createLatte()
		{
			$latte = new \Latte\Engine;
			$latte->setLoader(new TemplateLoader);

			// own filters
			$latte->addFilter('highlighter', function (\Latte\Runtime\FilterInfo $info, $s, $lang = 'html') {
				return new \Latte\Runtime\Html($this->highlighter->highlight($s, $lang));
			});

			LatteMacros::install($latte->getCompiler());

			// own macros
			$set = new \Latte\Macros\MacroSet($latte->getCompiler());
			$set->addMacro('webgen', '$webgen->addCurrentFileConfig(%node.array)');
			$set->addMacro('page', '$currentPage->setProperty(%node.word, %node.array)');
			$macroLink = 'echo %escape(%modify($webgen->createRelativeLink(%node.word)))';
			$set->addMacro('link', $macroLink);
			$set->addMacro('href', NULL, NULL, ' ?> href="<?php ' . $macroLink . ' ?>"<?php ');
			$set->addMacro('src', NULL, NULL, ' ?> src="<?php ' . $macroLink . ' ?>"<?php ');
			$set->addMacro('image', NULL, NULL, array($this, 'latteImageMacro'));

			// own providers
			$latte->addProvider('webgenLayoutProvider', function () {
				return is_string($this->layoutPath) ? $this->layoutPath : FALSE;
			});

			return $latte;
		}



		public function latteImageMacro($node, $writer) {
			$word = $writer->write('%node.word');
			$url = substr($word, 1, -1);
			$attrs = NULL;
			if (\Texy::isRelative($url)) {
				$url = Webgen::makeRelativePath($this->currentFile, $url);
				$file = rtrim($this->outputFileDirectory, '/\\') . '/' . rtrim(dirname($this->currentFile), '/\\') . '/' . $url;
				if (@is_file($file)) { // intentionally @
					$size = @getImageSize($file); // intentionally @
					if (is_array($size)) {
						$attrs = ' width="' . ((int) $size[0]) . '"';
						$attrs .= ' height="' . ((int) $size[1]) . '"';
					}
				}
			}
			return $writer->write(' ?> src="<?php echo %escape(%modify($webgen->createRelativeLink(' . $word . '))) ?>"' . $attrs . '<?php ');
		}



		protected function getCurrentOutputFilePath()
		{
			return $this->outputFileDirectory . '/' . $this->getCurrentOutputFile();
		}



		private function shortPath($filepath, $rootDirectory)
		{
			return Helpers::shortPath($filepath, $rootDirectory);
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



		private function solveTexyFigure($invocation, \Texy\Image $image, $link, $content, $mod)
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



		private function configureTexy($texy)
		{
			$texy->addHandler('phrase', array($this, 'phraseHandler'));
			$texy->addHandler('linkReference', array($this, 'linkReferenceHandler'));
			$texy->addHandler('figure', array($this, 'figureHandler'));
			$texy->addHandler('image', array($this, 'imageHandler'));

			if ($this->config['extensions']['syntaxHighlighter']) {
				$texy->addHandler('block', array($this->highlighter, 'blockHandler'));
			}

			if ($this->config['extensions']['syntaxHighlighterPhpBlock']) {
				// add new syntax: <?php ... ? >
				$texy->registerBlockPattern(
					array($this->highlighter, 'codeBlockHandler'),
					'#^<\\?php\n.+?\n\\?>$#ms', // block patterns must be multiline and line-anchored
					'phpBlockSyntax'
				);
			}

			if ($this->config['extensions']['syntaxHighlighterScriptBlock']) {
				// add new syntax: <s c r i p t ...> ... </script>
				$texy->registerBlockPattern(
					array($this->highlighter, 'codeBlockHandler'),
					'#^<script(?: type=.?text/javascript.?)?>\n.+?\n</script>$#ms', // block patterns must be multiline and line-anchored
					'scriptBlockSyntax'
				);
			}
		}


		public static function absolutizePath($path)
		{
			$path = explode('/', $path);
			$buffer = array();

			foreach ($path as $part) {
				if ($part === '' || $part === '.') { // // || /./
					continue;
				} elseif ($part === '..') { // /../
					array_pop($buffer);
				} else {
					$buffer[] = $part;
				}
			}

			return implode('/', $buffer);
		}
	}

