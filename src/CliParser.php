<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use CzProject\Logger\ILogger;
	use Nette;

	class CliParser
	{
		use Nette\SmartObject;

		/** @var string */
		public $timezone = 'Europe/Prague';

		/** @var ILogger */
		private $logger;

		/** @var array */
		private $cliArgs;

		/** @var Runner */
		private $runner;

		/** @var bool */
		private $watchMode = FALSE;

		/** @var string */
		private $currentDirectory;


		public function __construct(ILogger $logger, array $cliArgs = NULL)
		{
			$this->logger = $logger;
			$this->cliArgs = $cliArgs !== NULL ? $cliArgs : $_SERVER['argv'];
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
		 * @return int  status code
		 */
		public function run(Runner $runner)
		{
			if ($this->setup($runner)) {
				if ($this->watchMode) {
					$this->watch($runner);

				} else {
					return $runner->run();
				}
			}

			return 0;
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
					$this->logger->log("Error: $message in $file on $line", ILogger::ERROR);
					ob_flush();
					echo "\n\n";
					exit(2);
				}
				return FALSE;
			});
			set_exception_handler(function($e) {
				$this->logger->log("Error: {$e->getMessage()} in {$e->getFile()} on {$e->getLine()}\n", ILogger::ERROR);
			});
		}


		private function watch(Runner $runner)
		{
			$prev = array();
			$counter = 0;

			while (TRUE) {
				$state = array();

				foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->runner->inputDirectory)) as $file) {
					if (substr($file->getBasename(), 0, 1) !== '.') {
						$state[(string) $file] = md5_file((string) $file);
					}
				}

				if ($state !== $prev) {
					$prev = $state;
					$runner->run();
				}
				echo 'Watching ' . str_repeat('.', ++$counter % 5) . "    \r";
				sleep(2);
			}
		}


		/**
		 * @return void
		 */
		private function setupAutoloading()
		{
			$config = $this->runner->getConfig();
			$dir = $this->currentDirectory;

			$composerFile = is_string($config['input']['composerFile']) ? Helpers::formatPath($config['input']['composerFile'], $dir) : NULL;
			$libsDir = Helpers::formatPath($config['input']['libsDir'], $dir);

			$autoloading = new \Webgen\Autoloading($composerFile, $libsDir, $this->logger);
			$autoloading->autoload();

			if ($robotLoader = $autoloading->createRobotLoader()) {
				$this->logger->log('Register RobotLoader...');
				$this->logger->log('Scan dir: ' . $libsDir);

				$cacheDir = $config['output']['cacheDir'];

				if (is_string($cacheDir)) {
					$cacheDir = Helpers::formatPath($cacheDir, $dir);
					$this->logger->log('Cache dir: ' . $cacheDir);

					// create cache dir
					if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0777, TRUE)) {
						$this->logger->log('Error: Create cache dir failed', ILogger::ERROR);
						$robotLoader->setTempDirectory(sys_get_temp_dir() . '/robot-loader.' . \Nette\Utils\Random::generate(10));
					} else {
						$robotLoader->setTempDirectory($cacheDir);
					}
				} else {
					$this->logger->log('No cache dir for RobotLoader');
					$robotLoader->setTempDirectory(sys_get_temp_dir() . '/robot-loader.' . \Nette\Utils\Random::generate(10));
				}

				$robotLoader->register();
			}
		}


		/**
		 * @return array|FALSE
		 */
		private function loadCliParameters()
		{
			$params = Helpers::parseParams($this->cliArgs);

			if ($params === FALSE || !isset($params['run'])) {
				return FALSE;
			}

			$cwDir = getcwd();
			$dir = isset($params['dir']) ? $params['dir'] : $cwDir;
			$dir = Helpers::formatPath($dir, $cwDir);
			$this->watchMode = isset($params['watch']);

			return array(
				'directory' => $dir,
				'configFile' => "$dir/" . (isset($params['config']) ? $params['config'] : 'config.neon'),
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

			$config = \Nette\Neon\Neon::decode($config);

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
			if ($parameters['productionMode']) {
				$config['output']['productionMode'] = TRUE;
			}

			$this->runner->addConfig($config);
			$config = $this->runner->getConfig();
			$this->runner->inputDirectory = Helpers::formatPath($config['input']['dir'], $dir);
			$this->runner->outputDirectory = Helpers::formatPath($config['output']['dir'], $dir);
			$this->runner->layoutPath = Helpers::formatPath($config['input']['layout'], $dir);
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

Webgen
------

Usage:
	php -f webgen.php -- --run

Parameters:
	--run           enables generating [required]
	--config        name of config file
	--dir           project directory (current working directory by default)
	--production    enables production mode


XX;
		}
	}


	class CliException extends \RuntimeException
	{
	}
