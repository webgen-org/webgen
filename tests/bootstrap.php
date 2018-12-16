<?php

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();

@mkdir(__DIR__ . '/tmp');  // @ - adresář již může existovat
define('TEMP_DIR', __DIR__ . '/tmp/' . getmypid());
Tester\Helpers::purge(TEMP_DIR);


function test($cb)
{
	$cb();
}


function webgenTest($projectDirectory, $outputDirectory, $expectedDirectory)
{
	webgenGenerate($projectDirectory, $outputDirectory);
	webgenMatchDirectory($expectedDirectory, $outputDirectory);
}


function webgenGenerate($projectDirectory, $outputDirectory)
{
	$cliArgs = array(
		'webgen',
		'--dir',
		$projectDirectory,
		'--run',
	);
	$logger = new CzProject\Logger\MemoryLogger;
	$runner = new TestRunner($logger);
	$runner->testOutputDirectory = $outputDirectory;
	$cli = new Webgen\CliParser($logger, $cliArgs);
	$cli->run($runner);
	return $logger->getLog();
}


function webgenMatchDirectory($expected, $actual)
{
	$expectedItems = webgenScanDirectory($expected);
	$actualItems = webgenScanDirectory($actual);
	Tester\Assert::same($expectedItems, $actualItems);

	foreach ($expectedItems as $expectedItem) {
		$expectedPath = $expected . '/' . $expectedItem;
		$actualPath = $actual . '/' . $expectedItem;

		if (is_dir($expectedPath)) {
			webgenMatchDirectory($expectedPath, $actualPath);

		} else {
			Tester\Assert::same(
				file_get_contents($expectedPath),
				file_get_contents($actualPath)
			);
		}
	}
}


function webgenScanDirectory($dir)
{
	if (!is_dir($dir)) {
		throw new \RuntimeException("Directory '$dir' not found.");
	}

	$files = scandir($dir, SCANDIR_SORT_ASCENDING);
	return array_filter($files, function ($value) {
		return $value !== '.' && $value !== '..';
	});
}


class TestRunner extends Webgen\Runner
{
	public $testOutputDirectory;


	protected function createGenerator()
	{
		$generator = parent::createGenerator();
		$generator->outputDirectory = $this->testOutputDirectory;
		return $generator;
	}
}
