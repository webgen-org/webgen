<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;


	class TemplateLoader extends \Latte\Loaders\FileLoader
	{
		/** @var Template|NULL */
		private $template;


		public function setTemplate(Template $template)
		{
			$this->template = $template;
		}


		public function getContent($file)
		{
			if ($this->hasTemplateForFile($file)) {
				return $this->template->getContent();
			}

			return parent::getContent($file);
		}


		public function isExpired($file, $time)
		{
			if ($this->hasTemplateForFile($file)) {
				return TRUE;
			}

			return parent::isExpired($file, $time);
		}


		private function hasTemplateForFile($file)
		{
			return $this->template && \CzProject\PathHelper::absolutizePath($file, '/') === $this->template->getPath();
		}
	}
