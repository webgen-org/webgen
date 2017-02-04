<?php
	/**
	 * Webgen - static website generator written in PHP
	 *
	 * @author  Jan Pecha, <janpecha@email.cz>
	 */

	namespace Webgen;

	class Webgen extends \Nette\Object
	{
		private $generator;


		public function __construct($generator)
		{
			$this->generator = $generator;
		}


		public function getSourceDirectory()
		{
			return $this->generator->getSourceDirectory();
		}


		public function getCurrentFile()
		{
			return $this->generator->getCurrentFile();
		}


		/**
		 * @return string
		 */
		public function getCurrentPath()
		{
			return $this->generator->getCurrentFileLink();
		}


		public function getCurrentIteration()
		{
			return $this->generator->getCurrentIteration();
		}


		public function addCurrentFileConfig(array $config)
		{
			$this->generator->addCurrentFileConfig($config);
		}


		/**
		 * @param  string|string[]
		 * @throws WebgenException
		 * @return bool
		 */
		public function isLinkCurrent($pages/*, ...*/)
		{
			$currentFile = $this->generator->getCurrentFileLink();

			if ($currentFile === NULL) {
				throw new WebgenException('nezpracovava se zadny soubor');
			}

			if (!is_array($pages)) {
				$pages = func_get_args();
			}

			foreach ($pages as $page) {
				if (self::isPathCurrent($currentFile, $page)) {
					return TRUE;
				}
			}
			return FALSE;
		}


		/**
		 * @param  string|string[]
		 * @throws WebgenException
		 * @return bool
		 */
		public function isFileCurrent($pages/*, ...*/)
		{
			$currentFile = $this->getCurrentFile();

			if ($currentFile === NULL) {
				throw new WebgenException('nezpracovava se zadny soubor');
			}

			if (!is_array($pages)) {
				$pages = func_get_args();
			}

			foreach ($pages as $page) {
				if (self::isPathCurrent($currentFile, $page)) {
					return TRUE;
				}
			}
			return FALSE;
		}


		/**
		 * @param  string
		 * @return string
		 */
		public function createRelativeLink($destination)
		{
			$currentFile = $this->generator->getCurrentFileLink();

			if ($currentFile === NULL) {
				throw new WebgenException('nezpracovava se zadny soubor');
			}

			return self::makeRelativePath($currentFile, $destination);
		}


		/**
		 * Modified code from http://stackoverflow.com/a/2638272
		 * @param  string
		 * @param  string
		 * @return string
		 */
		public static function makeRelativePath($source, $destination)
		{
			$source = ltrim($source, '/');
			$destination = ltrim($destination, '/');

			$source = explode('/', $source);
			$sourceCount = count($source);
			$destination = explode('/', $destination);

			// remove first same parts
			$iter = 0;
			foreach ($source as $index => $part) {
				if (isset($destination[$index - $iter]) && $destination[$index - $iter] === $source[$index]) {
					array_shift($destination);
					$sourceCount--;
					$iter++;
					continue;
				}
				break;
			}

			$destinationCount = count($destination);
			$padLeft = $sourceCount - 1;

			if ($padLeft < 0) {
				array_unshift($destination, end($source));
			} else {
				$padLeft += (!$destinationCount) ? 1 : 0;

				if ($destinationCount === 1 && $destination[0] === '') { // remove empty '' (prevents '../', gets '..')
					$destination = array();
					$destinationCount = 0;
				} elseif ($destinationCount === 0) {
					end($source);
					$k = $sourceCount - $destinationCount;
					while ($k) {
						$part = prev($source);
						$k--;
					}
					$destination = array($part);
					$padLeft++;
				}

				$destination = array_pad($destination, ($destinationCount + $padLeft) * -1, '..');
			}

			$destination = implode('/', $destination);
			return $destination !== '' ? $destination : '.';
		}


		/**
		 * @param  string
		 * @param  string
		 * @return boolean
		 */
		public static function isPathCurrent($currentPath, $mask)
		{
			// $path muze obsahovat wildcard (*)
			// Priklady:
			// */contact.html => about/contact.html, ale ne en/about/contact.html
			// en/*/index.html => en/about/index.html, ale ne en/about/references/index.html
			// (tj. nematchuje '/')
			// ALE!
			// about/* => about/index.html i about/references/index.html
			// (tj. wildcard na konci matchuje i '/')

			$currentPath = ltrim($currentPath, '/');
			$mask = ltrim(trim($mask), '/');

			if ($mask === '*') {
				return TRUE;
			}

			// build pattern
			$pattern = strtr(preg_quote($mask, '#'), array(
				'\*\*' => '.*',
				'\*' => '[^/]*',
			));

			// match
			return (bool) preg_match('#^' . $pattern . '\z#i', $currentPath);
		}
	}


	class WebgenException extends \RuntimeException
	{
	}

