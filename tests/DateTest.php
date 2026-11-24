<?php

declare(strict_types=1);

namespace SwissParks\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use SwissParks\Sdk\Date;

final class DateTest extends TestCase
{


	/**
	 * MySQL datetime is split into date, hour and minute for form fields
	 */
	public function testSplitDateTimeSplitsDateAndTime(): void
	{

		$this->assertSame(
			['date' => '2026-11-23', 'hour' => '14', 'minute' => '05'],
			Date::splitDateTime('2026-11-23 14:05:00')
		);
		$this->assertSame([], Date::splitDateTime(''));
	}



	/**
	 * MySQL datetime becomes d.m.Y, optionally with time or as timestamp
	 */
	public function testFormatDateFormatsSwissDate(): void
	{

		$this->assertSame('23.11.2026', Date::formatDate('2026-11-23 14:05:00'));
		$this->assertSame('23.11.2026 14:05:00', Date::formatDate('2026-11-23 14:05:00', true));
		$this->assertSame(mktime(0, 0, 0, 11, 23, 2026), Date::formatDate('2026-11-23', false, true));
	}



	/**
	 * Zero, incomplete or empty dates never turn into 01.01.1970
	 */
	public function testFormatDateReturnsEmptyForZeroOrInvalidDates(): void
	{

		$this->assertSame('', Date::formatDate('0000-00-00 00:00:00'));
		$this->assertSame('', Date::formatDate('2026'));
		$this->assertSame('', Date::formatDate(''));
		$this->assertSame('', Date::formatDate(null));
	}



	/**
	 * Strings and XML attributes are normalized; invalid input becomes an empty string
	 */
	public function testNormalizeDateTime(): void
	{

		$xml = simplexml_load_string('<offer created_at="2026-11-23T14:05:00"/>');

		$this->assertSame('2026-11-23 14:05:00', Date::normalizeDateTime('2026-11-23 14:05'));
		$this->assertSame('2026-11-23 14:05:00', Date::normalizeDateTime($xml['created_at']));
		$this->assertSame('2026-11-23', Date::normalizeDateTime('23.11.2026', 'Y-m-d'));
		$this->assertSame('', Date::normalizeDateTime('not a date'));
		$this->assertSame('', Date::normalizeDateTime(null));
		$this->assertSame('', Date::normalizeDateTime([]));
	}



	/**
	 * Empty time ranges and empty end times are removed from the time list
	 */
	public function testFormatOfferTimesDropsEmptyEndTimes(): void
	{

		$this->assertSame('', Date::formatOfferTimes(''));
		$this->assertSame('', Date::formatOfferTimes('00:00 - 00:00'));
		$this->assertSame('10:00 - 12:00 / 14:00', Date::formatOfferTimes('10:00 - 12:00,14:00 - 00:00'));
	}



	/**
	 * More than three times are shortened to first two, ellipsis and last
	 */
	public function testFormatOfferTimesShortensLongLists(): void
	{

		$times = '08:00 - 09:00,10:00 - 11:00,12:00 - 13:00,14:00 - 15:00,16:00 - 17:00';
		$this->assertSame('08:00 - 09:00 / 10:00 - 11:00 / … / 16:00 - 17:00', Date::formatOfferTimes($times));
	}



	/**
	 * Total minutes are split into hours and remaining minutes
	 */
	public function testSplitHoursAndMinutes(): void
	{

		$this->assertEquals(['hours' => 2, 'minutes' => 15], Date::splitHoursAndMinutes(135));
		$this->assertEquals(['hours' => 0, 'minutes' => 45], Date::splitHoursAndMinutes(45));
		$this->assertSame([], Date::splitHoursAndMinutes(0));
	}



	/**
	 * Single-day events keep their start date and time
	 */
	public function testAdjustDateFromKeepsSingleDayEvent(): void
	{

		$this->assertSame(
			'2099-05-01 10:00',
			Date::adjustDateFrom('2099-05-01 10:00:00', '2099-05-01 12:00:00')
		);
	}



	/**
	 * Multi-day events start at the filter date when it lies within the range
	 */
	public function testAdjustDateFromUsesFilterDateWithinRange(): void
	{

		$this->assertSame(
			'2099-05-05',
			Date::adjustDateFrom('2099-05-01 10:00:00', '2099-05-10 12:00:00', '2099-05-05')
		);
	}



	/**
	 * Multi-day events that already started show today as start date
	 */
	public function testAdjustDateFromUsesTodayForRunningEvent(): void
	{

		$this->assertSame(
			date('Y-m-d'),
			Date::adjustDateFrom('2000-01-01 10:00:00', '2099-12-31 12:00:00')
		);
	}



	/**
	 * Without an end date the start date is returned unchanged
	 */
	public function testAdjustDateFromWithoutEndDateReturnsInput(): void
	{

		$this->assertSame('2099-05-01 10:00:00', Date::adjustDateFrom('2099-05-01 10:00:00'));
	}



	/**
	 * Short weekday prefix per language; unparsable dates stay as they are
	 */
	public function testFormatDateWithShortWeekday(): void
	{

		$this->assertSame('Mo, 23.11.2026', Date::formatDateWithShortWeekday('23.11.2026', TestSdkFactory::create()->lang));
		$this->assertSame('Mo, 23.11.2026', Date::formatDateWithShortWeekday('2026-11-23', TestSdkFactory::create()->lang));
		$this->assertSame('Lun 23.11.2026', Date::formatDateWithShortWeekday('23.11.2026', TestSdkFactory::create([], 'fr')->lang));
		$this->assertSame('invalid', Date::formatDateWithShortWeekday('invalid', TestSdkFactory::create()->lang));
	}



	/**
	 * Date label without weekday when display_weekday_short is disabled
	 */
	public function testFormatOfferDateLabelWithoutWeekday(): void
	{

		$lang = TestSdkFactory::create(['display_weekday_short' => false])->lang;
		$this->assertSame('23.11.2026', Date::formatOfferDateLabel('2026-11-23', $lang));
	}



	/**
	 * Date range across days shows both dates and times with connector
	 */
	public function testFormatOfferDateRangeAcrossDays(): void
	{

		$date = [
			'date_from' => '2026-11-23 10:00:00',
			'date_to' => '2026-11-25 12:30:00',
		];
		$this->assertSame(
			'<strong>Mo, 23.11.2026</strong> 10:00 bis <strong>Mi, 25.11.2026</strong> 12:30',
			Date::formatOfferDate($date, TestSdkFactory::create()->lang)
		);
	}



	/**
	 * Same-day date at midnight shows only the date
	 */
	public function testFormatOfferDateSameDayWithoutTimes(): void
	{

		$date = [
			'date_from' => '2026-11-23 00:00:00',
			'date_to' => '2026-11-23 00:00:00',
		];
		$this->assertSame('<strong>Mo, 23.11.2026</strong> ', Date::formatOfferDate($date, TestSdkFactory::create()->lang));
	}



	/**
	 * Activity duration with long or short labels, falling back to the time category
	 */
	public function testActivityTimeRequired(): void
	{

		$lang = TestSdkFactory::create()->lang;

		$this->assertSame('2 Stunden 15 Minuten', Date::formatActivityTimeRequired((object) ['time_required_minutes' => 135], $lang));
		$this->assertSame('1 Stunde 1 Minute', Date::formatActivityTimeRequired((object) ['time_required_minutes' => 61], $lang));
		$this->assertSame('2 h 15 min', Date::formatActivityTimeRequired((object) ['time_required_minutes' => 135], $lang, true));
		$this->assertSame('45 min', Date::formatActivityTimeRequired((object) ['time_required_minutes' => 45], $lang, true));
		$this->assertSame('2 Stunden', Date::formatActivityTimeRequired((object) ['time_required_minutes' => 120], $lang));
		$this->assertSame('Halbtag', Date::formatActivityTimeRequired((object) ['time_required' => 'Halbtag'], $lang));
		$this->assertSame('', Date::formatActivityTimeRequired((object) [], $lang));
	}
}
