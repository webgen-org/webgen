<?php
	/**
	 * Webgen - static website generator written in PHP
	 *
	 * @author  Jan Pecha, <janpecha@email.cz>
	 */

	namespace Webgen;

	use CzProject\Logger\ILogger;
	use Nette\Utils;

	class Autoloading
	{
		use \Nette\SmartObject;

		private $composerFile;

		private $libsDir;

		/** @var ILogger */
		private $logger;


		public function __construct($composerFile, $libsDir, ILogger $logger)
		{
			$this->composerFile = $composerFile;
			$this->libsDir = $libsDir;
			$this->logger = $logger;
		}



		public function autoload()
		{
			$files = array();

			// Composer
			if ($this->composerFile !== NULL) {
				$json = @file_get_contents($this->composerFile);

				if (is_string($json)) {
					$data = Utils\Json::decode($json, Utils\Json::FORCE_ARRAY);
					$composerDir = 'vendor';

					if (isset($data['config']['vendor-dir'])) {
						$composerDir = $data['config']['vendor-dir'];
					}

					$files[] = dirname($this->composerFile) . "/$composerDir/autoload.php";
				}
			}

			// Own loader
			$files[] = $this->libsDir . '/autoload.php';

			// Include files
			if (count($files)) {
				$this->logger->log('Autoload files...');

				foreach ($files as $file) {
					if (file_exists($file)) {
						$this->logger->log($file);
						LimitedScope::load($file);
					} else {
						$this->logger->log("[not found] $file");
					}
				}
			}

			return $files;
		}


		/**
		 * @return \Nette\Loaders\RobotLoader|FALSE
		 */
		public function createRobotLoader()
		{
			if (!file_exists($this->libsDir)) {
				return FALSE;
			}

			$loader = new \Nette\Loaders\RobotLoader;
			$loader->setAutoRefresh(TRUE);
			$loader->addDirectory($this->libsDir);
			return $loader;
		}
	}

