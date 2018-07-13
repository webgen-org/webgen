<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use Nette;

	class Template
	{
		/** @var string */
		private $path;

		/** @var string */
		private $content;

		/** @var array */
		private $parameters;


		/**
		 * @param  string
		 * @param  array
		 */
		public function __construct($path, $content, array $parameters = array())
		{
			$this->path = \CzProject\PathHelper::absolutizePath($path, '/');
			$this->content = $content;
			$this->parameters = $parameters;
		}


		public function getPath()
		{
			return $this->path;
		}


		public function addParameters(array $parameters)
		{
			foreach ($parameters as $name => $value) {
				$this->parameters[$name] = $value;
			}

			return $this;
		}


		public function setParameter($name, $value)
		{
			$this->parameters[$name] = $value;
			return $this;
		}


		public function getParameters()
		{
			return $this->parameters;
		}


		public function getParameter($name)
		{
			return isset($this->parameters[$name]) ? $this->parameters[$name] : NULL;
		}


		public function setContent($content)
		{
			$this->content = $content;
			return $this;
		}


		public function getContent()
		{
			return $this->content;
		}
	}
