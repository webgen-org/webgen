<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;

	class Texy extends \Texy
	{
		public function __construct($baseDir)
		{
			parent::__construct();

			$this->setOutputMode(self::HTML5);

			$this->mergeLines = TRUE;
			$this->tabWidth = 4;

			// htmlOutputModule settings
			$this->htmlOutputModule->lineWrap = FALSE;
			$this->htmlOutputModule->indent = FALSE;


			// linkModule settings
			$this->linkModule->root = $baseDir;


			// headingModule settings
			$this->headingModule->generateID = true;
			$this->headingModule->idPrefix = '';
			$this->headingModule->top = 2;


			// scriptModule settings
			$this->allowed['script'] = TRUE;
		}
	}

