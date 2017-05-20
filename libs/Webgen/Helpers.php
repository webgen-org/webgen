<?php
	/**
	 * Webgen - static website generator written in PHP
	 *
	 * @author  Jan Pecha, <janpecha@email.cz>
	 */

	namespace Webgen;

	class Helpers
	{
		public static function shortPath($path, $rootDirectory)
		{
			$rootDirectory = rtrim($rootDirectory, '/') . '/';
			return substr($path, strlen($rootDirectory));
		}
	}
