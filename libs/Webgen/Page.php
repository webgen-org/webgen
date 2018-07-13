<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use Nette;

	class Page
	{
		/** @var string */
		private $path;

		/** @var array */
		private $properties;


		/**
		 * @param  string
		 * @param  array
		 */
		public function __construct($path, array $properties = array())
		{
			$this->path = $path;
			$this->properties = $properties;
		}


		/**
		 * @return string
		 */
		public function getPath()
		{
			return $this->path;
		}


		/**
		 * @return string
		 */
		public function getShortPath()
		{
			$baseName = basename($this->path);

			if ($baseName === 'index.html' || $baseName === 'index.php') {
				return substr($this->path, 0, strlen($baseName) * -1);
			}

			return $this->path;
		}


		/**
		 * @return mixed|NULL
		 */
		public function get($property)
		{
			return $this->getProperty($property);
		}


		/**
		 * Gets value of property.
		 * @return mixed|NULL
		 */
		public function getProperty($property)
		{
			return isset($this->properties[$property]) ? $this->properties[$property] : NULL;
		}


		/**
		 * @param  string
		 * @return bool
		 */
		public function has($property)
		{
			return $this->hasProperty($property);
		}


		/**
		 * @param  string
		 * @return bool
		 */
		public function hasProperty($property)
		{
			return isset($property, $this->properties);
		}


		/**
		 * @return string
		 */
		public function getProperties()
		{
			return $this->properties;
		}


		/**
		 * @param  string  path prefix
		 * @return bool
		 */
		public function isChildOf($parent)
		{
			$parent = trim($parent);

			if ($parent === '') {
				return TRUE;
			}

			return substr($this->getPath(), 0, strlen($parent)) === $parent;
		}


		/**
		 * @return bool
		 */
		public function isIndex()
		{
			return pathinfo($this->path, PATHINFO_FILENAME) === 'index';
		}


		public function __get($name)
		{
			if (isset($this->properties[$name])) {
				return $this->properties[$name];
			}
			throw new \Exception("Undefined property '$name' for page {$this->path}.");
		}


		public function __isset($name)
		{
			return isset($this->properties[$name]);
		}
	}
