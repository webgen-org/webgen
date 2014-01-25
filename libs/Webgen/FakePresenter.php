<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use Nette;

	class FakePresenter extends Nette\Application\UI\Presenter
	{
		/** @var  \Webgen\Generator */
		private $generator;


		public function __construct($generator)
		{
			$this->generator = $generator;
		}


		public function findLayoutTemplateFile()
		{
			return $this->generator->layoutPath;
		}
	}

