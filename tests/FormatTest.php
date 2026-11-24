<?php

declare(strict_types=1);

namespace SwissParks\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use SwissParks\Sdk\Format;

final class FormatTest extends TestCase
{


	/**
	 * Slug is lowercased and words are joined with dashes
	 */
	public function testUrlSlugLowercasesAndDelimits(): void
	{

		$this->assertSame('hello-world', Format::urlSlug('Hello World'));
		$this->assertSame('', Format::urlSlug(''));
	}



	/**
	 * Umlauts are transliterated to ASCII when requested
	 */
	public function testUrlSlugHandlesUmlautsWithTransliterate(): void
	{

		$this->assertSame(
			'aaoouu',
			Format::urlSlug('ÄäÖöÜü', ['transliterate' => true])
		);
	}



	/**
	 * HTML tags are detected; plain text is not
	 */
	public function testContainsHtmlTags(): void
	{

		$this->assertTrue(Format::containsHtmlTags('<p>x</p>'));
		$this->assertFalse(Format::containsHtmlTags('plain text'));
	}



	/**
	 * First multibyte character is uppercased
	 */
	public function testUcfirstUtf8(): void
	{

		$this->assertSame('Äpfel', Format::ucfirstUtf8('äpfel'));
	}



	/**
	 * Slug limit cuts before trimming delimiters; custom delimiter is used
	 */
	public function testUrlSlugLimitTrimsTrailingDelimiter(): void
	{

		$this->assertSame('hello', Format::urlSlug('Hello World', ['limit' => 6]));
		$this->assertSame('hello_world', Format::urlSlug('Hello World', ['delimiter' => '_']));
	}



	/**
	 * All newline styles become paragraph breaks
	 */
	public function testNl2p(): void
	{

		$this->assertSame('<p>a</p><p>b</p><p>c</p>', Format::nl2p("a\nb\r\nc"));
	}



	/**
	 * Bare www hosts get a scheme; a trailing period stays outside the link
	 */
	public function testAutoLinkWwwAndTrailingPeriod(): void
	{

		$this->assertSame(
			'Visit <a href="http://www.parks.swiss">www.parks.swiss</a> now',
			Format::autoLink('Visit www.parks.swiss now')
		);
		$this->assertSame(
			'See <a href="https://parks.swiss/path" target="_blank" rel="noopener">https://parks.swiss/path</a>.',
			Format::autoLink('See https://parks.swiss/path.', 'url', true)
		);
	}



	/**
	 * Type url leaves emails alone and type email leaves URLs alone
	 */
	public function testAutoLinkRespectsType(): void
	{

		$this->assertSame('Mail info@parks.swiss', Format::autoLink('Mail info@parks.swiss', 'url'));
		$this->assertSame('www.parks.swiss', Format::autoLink('www.parks.swiss', 'email'));
	}



	/**
	 * Address and attributes never appear as plain text in the generated script
	 */
	public function testSafeMailtoHidesPlainAddress(): void
	{

		$html = Format::safeMailto('info@parks.swiss', '', ['class' => 'email_link']);
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
			Format::autoLinkHtml($html)
		);
	}



	/**
	 * Plain text keeps line breaks; HTML keeps its markup; both open links in a new tab
	 */
	public function testOutputTextPlainAndHtml(): void
	{

		$this->assertSame(
			"Line<br />\n" . '<a href="http://www.parks.swiss" target="_blank" rel="noopener">www.parks.swiss</a>',
			Format::outputText("Line\nwww.parks.swiss")
		);
		$this->assertSame(
			'<p><a href="http://www.parks.swiss" target="_blank" rel="noopener">www.parks.swiss</a></p>',
			Format::outputText('<p>www.parks.swiss</p>')
		);
	}



	/**
	 * Contact block: strong first line, linked URL, dash lines dropped
	 */
	public function testAutoTextFormatContactBlock(): void
	{

		$this->assertSame(
			'<strong>Park AG<br></strong><a href="http://www.parks.swiss" class="external_link" target="_blank">www.parks.swiss</a><br>Bern<br>',
			Format::autoTextFormat("Park AG\nwww.parks.swiss\n-\nBern", true)
		);
		$this->assertSame('', Format::autoTextFormat(''));
	}
}
