<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use Nette;
	use Nette\Utils\Finder as NFinder;

	class Runner extends Nette\Object
	{
		/** @var string */
		public $inputDirectory;

		/** @var string */
		public $outputDirectory;

		/** @var string */
		public $layoutPath;

		/** @var bool */
		public $forceMode = FALSE;

		/** @var ILogger */
		protected $logger;

		private $config;


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
				),

				'output' => array(
					'dir' => 'output',	// name of output directory
					'ext' => 'html',	// output file extension
					'xhtml' => FALSE,	// enables or disables XHTML output
					'purge' => FALSE,   // purge output directory?
					'onedir' => TRUE,   // disables incremental generating
					'lastBuildInfo' => NULL, // generate last build info file?
					'cacheDir' => 'cache',
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
				$generator->prepare(!$config['output']['onedir'], $config['output']['purge']);

				// Scanning & generating
				$this->log("Scanning...");
				$this->generate($generator, $finder);

				// Save datetime of build
				if ($config['output']['lastBuildInfo']) {
					$this->log("Saving last build infos...");
					\WebGen\Generator::setLastBuildDate($dir . '/lastBuild.dat', new DateTime);
				}

				$this->logger->success("Done.\n");
				return 0;

			} catch (\Exception $e) {
				$this->logger->error(
					"\nFatal error ["
						. get_class($e)
					. "]:\n  "
					. $e->getMessage()
					. "\n\n  file: {$e->getFile()}\n  line: {$e->getLine()}\n"
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
					$this->log("[ignored] $filePath");
					continue;
				}
				$this->log($path);
				$generator->generate($path, $file, $webgenHelper);
			}
		}


		protected function createGenerator()
		{
			$generator = new \Webgen\Generator;
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

			if (!$this->forceMode && !$config['output']['onedir']) {
				$lastBuildDate = \Webgen\Generator::getLastBuildDate($dir . '/lastBuild.dat');

				if ($lastBuildDate !== FALSE) {
					// kdyz nebyl od posledne zmenen layout => muzeme generovat jenom zmenene soubory, jinak vsechny
					if (filemtime($this->layoutPath) < $lastBuildDate->getTimestamp()) {	// TODO: nespolehat se na timestamp
						$this->log('Mode: incremental');
						$finder = $finder->date('>', $lastBuildDate);
					}
				}
			}

			return $finder;
		}


		protected function log($msg)
		{
			$this->logger->log($msg);
		}


		private function check()
		{
			if (!is_file($this->layoutPath)) {
				throw new RunnerException("File '$layoutPath' is not exists.", 10);
			}

			if (!is_dir($this->inputDirectory)) {
				throw new RunnerException("Directory '$inputDirectory' is not exists.", 11);
			}
		}
	}

	class RunnerException extends \RuntimeException
	{
	}
