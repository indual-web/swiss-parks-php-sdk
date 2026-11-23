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



	/**
	 * Slug limit cuts before trimming delimiters; custom delimiter is used
	 */
	public function testUrlSlugLimitTrimsTrailingDelimiter(): void
	{

		$this->assertSame('hello', ParksFormat::urlSlug('Hello World', ['limit' => 6]));
		$this->assertSame('hello_world', ParksFormat::urlSlug('Hello World', ['delimiter' => '_']));
	}



	/**
	 * All newline styles become paragraph breaks
	 */
	public function testNl2p(): void
	{

		$this->assertSame('<p>a</p><p>b</p><p>c</p>', ParksFormat::nl2p("a\nb\r\nc"));
	}



	/**
	 * Bare www hosts get a scheme; a trailing period stays outside the link
	 */
	public function testAutoLinkWwwAndTrailingPeriod(): void
	{

		$this->assertSame(
			'Visit <a href="http://www.parks.swiss">www.parks.swiss</a> now',
			ParksFormat::autoLink('Visit www.parks.swiss now')
		);
		$this->assertSame(
			'See <a href="https://parks.swiss/path" target="_blank" rel="noopener">https://parks.swiss/path</a>.',
			ParksFormat::autoLink('See https://parks.swiss/path.', 'url', true)
		);
	}



	/**
	 * Type url leaves emails alone and type email leaves URLs alone
	 */
	public function testAutoLinkRespectsType(): void
	{

		$this->assertSame('Mail info@parks.swiss', ParksFormat::autoLink('Mail info@parks.swiss', 'url'));
		$this->assertSame('www.parks.swiss', ParksFormat::autoLink('www.parks.swiss', 'email'));
	}



	/**
	 * Address and attributes never appear as plain text in the generated script
	 */
	public function testSafeMailtoHidesPlainAddress(): void
	{

		$html = ParksFormat::safeMailto('info@parks.swiss', '', ['class' => 'email_link']);
		$this->assertStringContainsString('<script', $html);
		$this->assertStringNotContainsString('info@parks.swiss', $html);
		$this->assertStringNotContainsString('email_link', $html);
	}



	/**
	 * Only free text is linked; attributes and existing anchors stay untouched
	 */
	public function testAutoLinkHtmlSkipsTagsAndExistingAnchors(): void
	{

		$html = '<p>Go to <a href="https://x.ch">https://x.ch</a> or www.parks.swiss <img src="https://x.ch/a.jpg"></p>';
		$this->assertSame(
			'<p>Go to <a href="https://x.ch">https://x.ch</a> or <a href="http://www.parks.swiss">www.parks.swiss</a> <img src="https://x.ch/a.jpg"></p>',
			ParksFormat::autoLinkHtml($html)
		);
	}



	/**
	 * Plain text keeps line breaks; HTML keeps its markup; both open links in a new tab
	 */
	public function testOutputTextPlainAndHtml(): void
	{

		$this->assertSame(
			"Line<br />\n" . '<a href="http://www.parks.swiss" target="_blank" rel="noopener">www.parks.swiss</a>',
			ParksFormat::outputText("Line\nwww.parks.swiss")
		);
		$this->assertSame(
			'<p><a href="http://www.parks.swiss" target="_blank" rel="noopener">www.parks.swiss</a></p>',
			ParksFormat::outputText('<p>www.parks.swiss</p>')
		);
	}



	/**
	 * Contact block: strong first line, linked URL, dash lines dropped
	 */
	public function testAutoTextFormatContactBlock(): void
	{

		$this->assertSame(
			'<strong>Park AG<br></strong><a href="http://www.parks.swiss" class="external_link" target="_blank">www.parks.swiss</a><br>Bern<br>',
			ParksFormat::autoTextFormat("Park AG\nwww.parks.swiss\n-\nBern", true)
		);
		$this->assertSame('', ParksFormat::autoTextFormat(''));
	}
}
