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


		/**
		 * @param	array
		 * @return	array|FALSE
		 */
		public static function parseParams(array $argv)
		{
			$params = array();
			$lastName = NULL;

			if(isset($argv[1]))		// count($argv) > 1
			{
				// remove argv[0]
				array_shift($argv);

				// parsing
				foreach($argv as $argument)
				{
					if($argument{0} === '-')
					{
						$name = trim($argument, '-');
						$lastName = $name;

						if(!isset($params[$name]))
						{
							$params[$name] = TRUE;
						}
					}
					elseif($lastName === NULL)
					{
						throw new \Exception("Bad argument '$argument'");
					}
					else
					{
						if($params[$lastName] === TRUE)
						{
							$params[$lastName] = $argument;
						}
						else	// string || array
						{
							if(is_string($params[$lastName]))
							{
								$newParams = array(
									$params[$lastName],
									$argument,
								);

								$params[$lastName] = $newParams;
							}
							else
							{
								$params[$lastName][] = $argument;
							}

							$lastName = NULL;
						}
					}
				}

				return $params;
			}

			return FALSE;
		}


		/**
		 * @param	string
		 * @param	string
		 * @return	string
		 */
		public static function formatPath($dir, $currentDir)
		{
			if($dir{0} === '/')
			{
				return $dir;
			}

			return $currentDir . '/' . $dir;
		}
	}
