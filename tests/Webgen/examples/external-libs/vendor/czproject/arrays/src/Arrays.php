<?php

	namespace CzProject;


	class Arrays
	{
		/**
		 * @param  array|\Traversable
		 * @return array
		 */
		public static function flatten($arr)
		{
			$res = array();

			static::recursiveWalk($arr, function ($val, $key) use (&$res) {
				if (is_scalar($val)) {
					$res[] = $val;
				}
			});

			return $res;
		}


		/**
		 * @param  array|\Traversable
		 * @return void
		 */
		public static function recursiveWalk($arr, $callback)
		{
			foreach ($arr as $key => $value) {
				if (is_array($value) || $value instanceof \Traversable) {
					static::recursiveWalk($value, $callback);

				} else {
					call_user_func_array($callback, array($value, $key));
					// $callback($value, $key);
				}
			}
		}


		/**
		 * @param  array|object[]
		 * @param  string|callback
		 * @param  string|callback
		 * @return array
		 */
		public static function fetchPairs($data, $key, $value)
		{
			$list = array();

			foreach ($data as $row) {
				$itemKey = NULL;
				$itemLabel = NULL;

				if (is_callable($key)) {
					$itemKey = call_user_func_array($key, array($row));

				} else {
					$itemKey = is_array($row) ? $row[$key] : $row->{$key};
				}

				if (is_callable($value)) {
					$itemLabel = call_user_func_array($value, array($row));

				} else {
					$itemLabel = is_array($row) ? $row[$value] : $row->{$value};
				}

				$list[$itemKey] = $itemLabel;
			}

			return $list;
		}


			/**
			 * Merges arrays. Left has higher priority than right one.
			 * @param  array|NULL
			 * @param  array|NULL
			 * @return array|string
			 * @see    https://github.com/nette/di/blob/master/src/DI/Config/Helpers.php
			 */
			public static function merge($left, $right)
			{
				if (is_array($left) && is_array($right)) {
					foreach ($left as $key => $val) {
						if (is_int($key)) {
							$right[] = $val;

						} else {
							if (isset($right[$key])) {
								$val = static::merge($val, $right[$key]);
							}
							$right[$key] = $val;
						}
					}
					return $right;

				} elseif ($left === NULL && is_array($right)) {
					return $right;

				} else {
					return $left;
				}
			}
	}
