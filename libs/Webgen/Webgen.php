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


		public function getCurrentFile()
		{
			return $this->generator->getCurrentFile();
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
			$currentFile = $this->getCurrentFile();

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
			$relativePath = $destination = explode('/', $destination);
			$relativePathCount = count($relativePath);

			foreach ($source as $index => $part) {
				// remove first same parts
				if (isset($destination[$index]) && $destination[$index] === $part) {
					// ignore this part
					array_shift($relativePath);
					$relativePathCount--;
				} else {
					$remaining = $sourceCount - $index;
					if ($remaining > 1) {
						// add traversals up to first matching dir
						$padLength = ($relativePathCount + $remaining - 1) * -1;
						$relativePath = array_pad($relativePath, $padLength, '..');
						break;
					} else {
						$relativePath[0] = /*'./' . */$relativePath[0];
					}
				}
			}

			$relativePath = implode('/', $relativePath);

			if (!$relativePathCount || $relativePath === '') {
				return '.';
			}

			return $relativePath;
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
			// (tj. nematchuje ̈́'/')
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

