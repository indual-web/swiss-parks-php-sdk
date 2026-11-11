<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ParksFormatTest extends TestCase
{


	public function testUrlSlugLowercasesAndDelimits(): void
	{

		$this->assertSame('hello-world', ParksFormat::urlSlug('Hello World'));
		$this->assertSame('', ParksFormat::urlSlug(''));
	}



	public function testUrlSlugHandlesUmlautsWithTransliterate(): void
	{

		$this->assertSame(
			'aaoouu',
			ParksFormat::urlSlug('ÄäÖöÜü', ['transliterate' => true])
		);
	}



	public function testContainsHtmlTags(): void
	{

		$this->assertTrue(ParksFormat::containsHtmlTags('<p>x</p>'));
		$this->assertFalse(ParksFormat::containsHtmlTags('plain text'));
	}



	public function testUcfirstUtf8(): void
	{

		$this->assertSame('Äpfel', ParksFormat::ucfirstUtf8('äpfel'));
	}
}
