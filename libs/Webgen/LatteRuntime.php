<?php
	/**
	 * Webgen - static website generator written in PHP
	 *
	 * @author  Jan Pecha, <janpecha@email.cz>
	 */

	namespace Webgen;

	use Latte;


	class LatteRuntime
	{
		public static function initialize(Latte\Runtime\Template $template, &$parentName, array $blocks)
		{
			$providers = $template->global;
			$blocks = array_filter(array_keys($blocks), function ($s) { return $s[0] !== '_'; });

			if ($parentName === NULL && !empty($blocks) && !$template->getReferringTemplate() && isset($providers->webgenLayoutProvider)) {
				$parentName = call_user_func($providers->webgenLayoutProvider);
			}
		}
	}
