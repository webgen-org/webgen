<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;

	class TexyFilter extends Texy
	{
		#public function __construct($baseDir)
		#{
		#	parent::__construct($baseDir);
		#
		#	$this->headingModule->top = 1;
		#}



		public function __invoke($s)
		{
			return $this->transform($s);
		}



		public function transform($s)
		{
			// Texy! Process & transform to Latte
			return $this->transformToLatte($this->process($s));
		}



		protected function transformToLatte($html)
		{
			// Add #title
			$source = '{block #title}'
				. $this->headingModule->title
				. "{/block}\n";

			// Add #content
			$source .= '{block #content}' . $html;

			return $source;
		}
	}

