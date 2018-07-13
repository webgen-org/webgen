<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;


	class LimitedScope
	{
		public static function load($file)
		{
			include $file;
		}
	}
