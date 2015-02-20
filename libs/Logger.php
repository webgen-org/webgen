<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use Nette;

	class Logger implements ILogger
	{
		public function log($msg)
		{
			\Cli\Cli::log($msg);
		}


		public function success($msg)
		{
			\Cli\Cli::success($msg);
		}


		public function error($msg)
		{
			\Cli\Cli::error($msg);
		}
	}
