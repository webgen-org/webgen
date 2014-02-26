<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Cli;

	class Cli
	{
		/** @var  bool|NULL */
		protected static $coloredOutput = NULL;



		/**
		 * @param	string
		 * @return	void
		 */
		public static function log($str)
		{
			echo "$str\n";
		}



		/**
		 * @param	string
		 * @return	void
		 */
		public static function error($str)
		{
			if(self::$coloredOutput === NULL)
			{
				self::detectOs();
			}

			if(self::$coloredOutput)
			{
				$str = "\033[31m" . $str . "\033[37m\r\n";
			}

			fwrite(STDERR, $str);
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



		/**
		 * @return	void
		 */
		protected static function detectColors()
		{
			self::$coloredOutput = (getenv('ConEmuANSI') === 'ON'
				|| getenv('ANSICON') !== FALSE
				|| (defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT)));
		}
	}

