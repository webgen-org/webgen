<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;

	class FileTemplate extends \Nette\Templating\FileTemplate
	{
	    /** @var string|NULL */
	    protected $fileSource = NULL;



		public function getSource()
		{
			if($this->fileSource === NULL)
			{
				$this->fileSource = parent::getSource();
			}

			return $this->fileSource;
		}



		public function setSource($s)
		{
			$this->fileSource = $s;

			return $this;
		}



		public function __clone()
		{
			$this->fileSource = NULL;
		}
	}

