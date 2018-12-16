<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	namespace Webgen;
	use Nette;

	class Pages
	{
		use \Nette\SmartObject;

		/** @var Page[] */
		private $pages = array();


		/**
		 * @return self
		 */
		public function resetPages()
		{
			$this->pages = array();
			return $this;
		}


		/**
		 * @param  string
		 * @param  array
		 * @return self
		 */
		public function addPage($path, array $properties = array())
		{
			if (isset($this->pages[$path])) {
				throw new \Exception("Page '$path' already exists.");
			}

			$this->pages[$path] = new Page($path, $properties);
			return $this;
		}


		/**
		 * @param  string[]
		 * @param  string|Page
		 * @return Page[]
		 */
		public function getPages(array $orderBy = array(), $parent = '')
		{
			if ($parent instanceof Page) {
				$parent = dirname($parent->getPath());
				$parent = $parent !== '.' ? $parent : '';
			}

			if (empty($orderBy) && $parent === '') {
				return $this->pages;
			}

			$pages = $this->pages;

			if ($parent !== '') {
				$pages = array_filter($pages, function (Page $page) use ($parent) {
					return $page->isChildOf($parent);
				});
			}

			if (!empty($orderBy)) {
				$sorts = array();

				foreach ($orderBy as $orderByClause) {
					$lowerClause = strtolower($orderByClause);

					if (substr($lowerClause, -4) === ' asc') {
						$sorts[] = array(trim(substr($orderByClause, 0, -4)), 'ASC');

					} elseif (substr($lowerClause, -5) === ' desc') {
						$sorts[] = array(trim(substr($orderByClause, 0, -5)), 'DESC');
					}
				}

				$collator = new \Collator('cs_CZ'); // TODO

				uasort($pages, function (Page $a, Page $b) use ($sorts, $collator) {
					// -1  a < b
					//  0  a = b
					//  1  a > b
					foreach ($sorts as $sortData) {
						$column = $sortData[0];
						$sorting = $sortData[1];

						if (!isset($a->{$column}) || !isset($b->{$column})) {
							throw new \RuntimeException("Sorting by missing property '{$column}'.");
						}

						$sort = 0; // a = b
						$valA = $a->{$column};
						$valB = $b->{$column};

						if (is_string($valA)) {
							$sort = $collator->compare($valA, $valB);

						// } elseif (is_array($valA)) { // TODO

						} elseif ($valA < $valB) {
							$sort = -1;

						} elseif ($valA > $valB) {
							$sort = 1;
						}

						if ($sort) {
							return ($sorting !== 'ASC') ? ($sort * -1) : $sort;
						}
					}

					return 0;
				});
			}

			return $pages;
		}
	}
