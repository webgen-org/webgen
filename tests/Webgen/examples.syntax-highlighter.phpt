<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


webgenTest(
	__DIR__ . '/examples/syntax-highlighter',
	TEMP_DIR,
	__DIR__ . '/examples/syntax-highlighter/expected.output'
);
