<?php
	/**
	 * Webgen - static website generator written in PHP
	 *
	 * @author  Jan Pecha, <janpecha@email.cz>
	 */

	namespace Webgen;

	use Latte;


	class LatteMacros extends Latte\Macros\MacroSet
	{
		/** @var bool|string */
		private $extends;


		public static function install(Latte\Compiler $compiler)
		{
			$me = new static($compiler);

			$me->addMacro('extends', array($me, 'macroExtends'));
			$me->addMacro('layout', array($me, 'macroExtends'));
		}


		public function initialize()
		{
			$this->extends = FALSE;
		}


		/**
		 * @return array  [prolog, epilog]
		 */
		public function finalize()
		{
			return array($this->extends . 'Webgen\LatteRuntime::initialize($this, $this->parentName, $this->blocks);');
		}


		public function macroExtends(Latte\MacroNode $node, Latte\PhpWriter $writer)
		{
			if ($node->modifiers || $node->parentNode || $node->args !== 'auto') {
				return $this->extends = FALSE;
			}

			$this->extends = $writer->write('$this->parentName = call_user_func($this->global->webgenLayoutProvider);');
		}
	}
