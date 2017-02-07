<?php
	/**
	 * Webgen - static website generator written in PHP
	 *
	 * @author  Jan Pecha, <janpecha@email.cz>
	 */

	namespace Webgen;

	class WebgenPage
	{
		private $generator;

		/** @var array */
		private $properties = array();


		public function __construct($generator)
		{
			$this->generator = $generator;
		}


		/**
		 * @return array
		 */
		public function getProperties()
		{
			return $this->properties;
		}


		public function getProperty($property, $default = NULL)
		{
			if (isset($this->properties[$property])) {
				return $this->properties[$property];
			}

			if (isset($default)) {
				return $default;
			}

			throw new \Exception("Undefined page property '$property'.");
		}


		/**
		 * @param  string
		 * @param  mixed
		 */
		public function setProperty($property, $value)
		{
			if (is_array($value) && count($value) === 1) {
				$value = reset($value);
			}
			$this->properties[$property] = $value;
		}


		public function get($name, $default = NULL)
		{
			return $this->getProperty($name, $default);
		}


		public function __set($name, $value)
		{
			$this->setProperty($name, $value);
		}


		public function __get($name)
		{
			return $this->getProperty($name);
		}


		public function __isset($name)
		{
			return isset($this->properties[$name]);
		}
	}
