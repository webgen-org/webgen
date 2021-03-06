<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use CzProject\Logger\ILogger;
	use Nette;
	use Nette\Utils\Finder as NFinder;

	class Runner
	{
		use Nette\SmartObject;

		/** @var string */
		public $inputDirectory;

		/** @var string */
		public $outputDirectory;

		/** @var string */
		public $layoutPath;

		/** @var bool */
		public $productionMode = FALSE;

		/** @var string @internal */
		public $currentDirectory;

		/** @var ILogger */
		protected $logger;

		private $config;

		/** @var array */
		private $deferredFiles = array();


		public function __construct(ILogger $logger)
		{
			$this->logger = $logger;

			// default config
			$this->config = array(
				'input' => array(
					'layout' => '@layout.latte',	// name of layout file
					'dir' => 'input', // name of input directory
					'copy' => FALSE, // copy another (images, JS, CSS) files into output directory
					'libsDir' => 'libs', // name of libs directory, handled by RobotLoader
					'composerFile' => 'composer.json',
					'deferredFiles' => array(),
				),

				'output' => array(
					'dir' => 'output',	// name of output directory
					'ext' => 'html',	// output file extension
					'cacheDir' => NULL,
					'productionMode' => FALSE,
				),

				'extensions' => array(
					'syntaxHighlighter' => FALSE, // enable syntax highlighter via FSHL (in Texy & Latte)
					'syntaxHighlighterPhpBlock' => FALSE, // enable highlighter between <?php ? > (in Texy)
					'syntaxHighlighterScriptBlock' => FALSE, // enable highlighter between <script></script> (in Texy)
				),

				'variables' => array(
					'baseDir' => '',	// baseDir variable, etc. '/my_web_subdir'
				),
			);
		}


		/**
		 * @return self
		 */
		public function addConfig(array $config)
		{
			$sections = array(
				'input' => TRUE,
				'output' => TRUE,
				'extensions' => TRUE,
				'variables' => FALSE,
			);

			foreach ($sections as $section => $whitelisted) {
				$user = array();

				if (isset($config[$section])) {
					$user = $config[$section];
				}

				$new = $user + $this->config[$section];

				if ($whitelisted) {
					$new = array_intersect_key($new, $this->config[$section]);
				}

				$this->config[$section] = $new;
			}

			return $this;
		}


		/**
		 * @return array
		 */
		public function getConfig()
		{
			return $this->config;
		}


		/**
		 * @return int  status code
		 */
		public function run()
		{
			$this->check();

			try {
				// Generating
				$this->log('Generating...');
				$generator = $this->createGenerator();
				$finder = $this->createFinder();
				$config = $this->getConfig();

				// Make output directory
				$this->log("Preparing...");
				$generator->prepare();

				// Scanning & generating
				$this->deferredFiles = array();
				$this->log("Scanning...");
				$this->generate($generator, $finder);
				$this->generateDefferedFiles($generator);

				$this->logger->log("Done.\n", ILogger::SUCCESS);
				return 0;

			} catch (\Exception $e) {
				$this->logger->log(
					"\nFatal error ["
						. get_class($e)
					. "]:\n  "
					. $e->getMessage()
					. "\n\n  file: {$e->getFile()}\n  line: {$e->getLine()}\n",
					ILogger::ERROR
				);
				return 1;
			}
		}


		protected function generate(Generator $generator, NFinder $finder)
		{
			$webgenHelper = new \Webgen\Webgen($generator);

			$files = $finder->from($generator->inputDirectory)
				->exclude('.git');

			foreach ($files as $path => $file) {
				$filename = $file->getFilename();

				if ($filename[0] === '@') {
					$this->log("[ignored] $path");
					continue;
				}

				if ($this->isDefferedFile($file)) {
					$shortPath = Helpers::shortPath($path, $this->inputDirectory);
					$this->deferredFiles[$shortPath] = $file;
					continue;
				}

				$this->log($path);
				$generator->generate($path, $file, $webgenHelper);
			}
		}


		protected function generateDefferedFiles(Generator $generator)
		{
			$webgenHelper = new \Webgen\Webgen($generator);
			$config = $this->getConfig();

			if (isset($config['input']['deferredFiles']) && is_array($config['input']['deferredFiles'])) {
				foreach ($config['input']['deferredFiles'] as $path) {
					if (!isset($this->deferredFiles[$path])) {
						continue;
					}

					$this->log("[deferred] $path");
					$file = $this->deferredFiles[$path];
					$generator->generate((string) $file, $file, $webgenHelper);
				}
			}
		}


		protected function createGenerator()
		{
			$generator = new \Webgen\Generator($this->logger);
			$generator->config = $this->getConfig();
			$generator->inputDirectory = $this->inputDirectory;
			$generator->outputDirectory = $this->outputDirectory;
			$generator->layoutPath = $this->layoutPath;

			return $generator;
		}


		protected function createFinder()
		{
			$config = $this->getConfig();
			$finder = NFinder::findFiles('*.texy', '*.latte');
			return $finder;
		}


		protected function log($msg)
		{
			$this->logger->log($msg);
		}


		protected function isDefferedFile($path)
		{
			$config = $this->getConfig();

			if (isset($config['input']['deferredFiles']) && is_array($config['input']['deferredFiles'])) {
				$shortPath = Helpers::shortPath($path, $this->inputDirectory);
				return in_array($shortPath, $config['input']['deferredFiles'], TRUE);
			}

			return FALSE;
		}


		private function check()
		{
			if (!is_file($this->layoutPath)) {
				throw new RunnerException("File '{$this->layoutPath}' is not exists.", 10);
			}

			if (!is_dir($this->inputDirectory)) {
				throw new RunnerException("Directory '{$this->inputDirectory}' is not exists.", 11);
			}
		}
	}

	class RunnerException extends \RuntimeException
	{
	}
