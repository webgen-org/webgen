<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use Nette;

	class CliParser extends Nette\Object
	{
		/** @var string */
		public $timezone = 'Europe/Prague';

		/** @var ILogger */
		private $logger;

		/** @var Runner */
		private $runner;

		/** @var string */
		private $currentDirectory;


		public function __construct(ILogger $logger)
		{
			$this->logger = $logger;
		}


		/**
		 * @return bool
		 */
		public function setup(Runner $runner)
		{
			$this->runner = $runner;

			if (!$this->loadConfig()) {
				return FALSE;
			}

			$this->setupAutoloading();
			return TRUE;
		}


		/**
		 * @return void
		 */
		public function prepareEnvironment()
		{
			date_default_timezone_set($this->timezone);
			set_time_limit(0);
			set_error_handler(function($severity, $message, $file, $line) {
				if (($severity & error_reporting()) === $severity) {
					\Cli\Cli::error("Error: $message in $file on $line");
					ob_flush();
					echo "\n\n";
					exit(2);
				}
				return FALSE;
			});
			set_exception_handler(function($e) {
				\Cli\Cli::error("Error: {$e->getMessage()} in {$e->getFile()} on {$e->getLine()}\n");
			});
		}


		/**
		 * @return void
		 */
		private function setupAutoloading()
		{
			$config = $this->runner->getConfig();
			$dir = $this->currentDirectory;

			$composerFile = is_string($config['input']['composerFile']) ? \Cli\Cli::formatPath($config['input']['composerFile'], $dir) : NULL;
			$libsDir = \Cli\Cli::formatPath($config['input']['libsDir'], $dir);

			$autoloading = new \Webgen\Autoloading($composerFile, $libsDir);
			$autoloading->autoload();

			if ($robotLoader = $autoloading->createRobotLoader()) {
				$this->logger->log('Register RobotLoader...');
				$this->logger->log('Scan dir: ' . $libsDir);

				$cacheDir = $config['output']['cacheDir'];

				if (is_string($cacheDir)) {
					$cacheDir = \Cli\Cli::formatPath($cacheDir, $dir);
					$this->logger->log('Cache dir: ' . $cacheDir);

					// create cache dir
					if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0777, TRUE)) {
						\Cli\Cli::error('Error: Create cache dir failed');
						$robotLoader->setCacheStorage(new \Nette\Caching\Storages\MemoryStorage);
					} else {
						$robotLoader->setCacheStorage(new \Nette\Caching\Storages\FileStorage($cacheDir));
					}
				} else {
					$this->logger->log('No cache dir for RobotLoader');
				}

				$robotLoader->register();
			}
		}


		/**
		 * @return array|FALSE
		 */
		private function loadCliParameters()
		{
			$params = \Cli\Cli::parseParams($_SERVER['argv']);

			if ($params === FALSE || !isset($params['run'])) {
				return FALSE;
			}

			$cwDir = getcwd();
			$dir = isset($params['dir']) ? $params['dir'] : $cwDir;
			$dir = \Cli\Cli::formatPath($dir, $cwDir);

			return array(
				'directory' => $dir,
				'configFile' => "$dir/" . (isset($params['config']) ? $params['config'] : 'config.neon'),
				'oneDirectoryMode' => isset($params['onedir']),
				'force' => isset($params['force']),
				'productionMode' => isset($params['productionMode']) || isset($params['production-mode']) || isset($params['production']),
			);
		}


		/**
		 * @return array
		 */
		private function loadConfigFile($configFile)
		{
			$this->logger->log('Load config...');
			$config = @file_get_contents($configFile); // intentionally @

			if (!is_string($config)) {
				$this->logger->log('...config file not found.');
				return array();
			}

			$config = \Nette\Utils\Neon::decode($config);

			if (!is_array($config) && !is_null($config)) {
				$this->logger->log('...config file has wrong format');
			}

			return (array) $config;
		}


		private function loadConfig()
		{
			$parameters = $this->loadCliParameters();

			if ($parameters === FALSE) {
				$this->info();
				return FALSE;
			}

			$dir = $parameters['directory'];
			$this->currentDirectory = $dir;

			// Config
			$configFile = $parameters['configFile'];

			// Load NEON config
			$config = $this->loadConfigFile($configFile);

			// Settings
			if ($parameters['oneDirectoryMode']) {
				$config['output']['onedir'] = TRUE;
			}

			if ($parameters['productionMode']) {
				$config['output']['productionMode'] = TRUE;
			}

			if (isset($config['output']) && array_key_exists('lastBuildInfo', $config['output']) && $config['output']['lastBuildInfo'] === NULL /* auto */) {
				if (isset($config['output']['onedir'])) {
					$config['output']['lastBuildInfo'] = !$config['output']['onedir'];
				} else {
					$config['output']['lastBuildInfo'] = TRUE;
				}
			}

			$this->runner->addConfig($config);
			$config = $this->runner->getConfig();
			$this->runner->inputDirectory = \Cli\Cli::formatPath($config['input']['dir'], $dir);
			$this->runner->outputDirectory = \Cli\Cli::formatPath($config['output']['dir'], $dir);
			$this->runner->layoutPath = \Cli\Cli::formatPath($config['input']['layout'], $dir);
			$this->runner->forceMode = $parameters['force'];
			$this->runner->productionMode = $config['output']['productionMode'];
			$this->runner->currentDirectory = $this->currentDirectory;

			return TRUE;
		}


		/**
		 * @return void
		 * @internal
		 */
		private function info()
		{
			echo <<<XX

Webgen 2.1.0-dev
----------------
Usage:
	php -f webgen.php -- --run

Parameters:
	--run           enables generating [required]
	--config        name of config file
	--dir           project directory (current working directory by default)
	--production    enables production mode

	[incremental mode]
	--force         regenerates all files (not only changed)
	--onedir        disables incremental mode


XX;
		}
	}


	class CliException extends \RuntimeException
	{
	}
