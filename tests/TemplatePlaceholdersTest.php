<?php

declare(strict_types=1);

namespace SwissParks\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class TemplatePlaceholdersTest extends TestCase
{


	private const DOC_PATH = __DIR__ . '/../docs/sdk-reference/template-placeholders.md';

	private array $config = [];



	/**
	 * Load the registered template tags and conditions
	 */
	protected function setUp(): void
	{

		$sdk = TestSdkFactory::create();
		(new ReflectionMethod($sdk, 'loadSystemConfig'))->invoke($sdk);
		$this->config = $sdk->config;
	}



	/**
	 * Every registered placeholder is documented, and the docs list no unknown ones
	 */
	public function testPlaceholdersMatchDocs(): void
	{

		preg_match_all('/^\| `__([A-Z_]+)__` \|/m', $this->referenceTables(), $matches);

		$this->assertEqualsCanonicalizing($this->config['template_tags'], $matches[1]);
	}



	/**
	 * Every registered condition is documented, and the docs list no unknown ones
	 */
	public function testConditionsMatchDocs(): void
	{

		preg_match_all('/^\| `\[([A-Z_]+)@start\]/m', $this->referenceTables(), $matches);

		$this->assertEqualsCanonicalizing($this->config['template_conditions'], $matches[1]);
	}



	/**
	 * Docs from the detail template section on, so the syntax examples are not counted
	 */
	private function referenceTables(): string
	{

		$doc = (string) file_get_contents(self::DOC_PATH);

		return (string) strstr($doc, '## Detail template');
	}



	/**
	 * Shipped templates point to the placeholder reference instead of listing tags
	 */
	public function testTemplatesLinkToDocs(): void
	{

		$templates = array_merge(
			glob(__DIR__ . '/../swiss-parks-sdk/templates/*/*.tpl'),
			glob(__DIR__ . '/../examples/*/templates/*.tpl')
		);

		$this->assertNotEmpty($templates);
		foreach ($templates as $template) {
			$this->assertStringContainsString('docs/sdk-reference/template-placeholders.md', (string) file_get_contents($template), $template);
		}
	}
}
