<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ParksDateTest extends TestCase
{


	/**
	 * MySQL datetime is split into date, hour and minute for form fields
	 */
	public function testMysql2formSplitsDateAndTime(): void
	{

		$this->assertSame(
			['date' => '2026-11-23', 'hour' => '14', 'minute' => '05'],
			ParksDate::parksMysql2form('2026-11-23 14:05:00')
		);
		$this->assertSame([], ParksDate::parksMysql2form(''));
	}



	/**
	 * MySQL datetime becomes d.m.Y, optionally with time or as timestamp
	 */
	public function testMysql2dateFormatsSwissDate(): void
	{

		$this->assertSame('23.11.2026', ParksDate::parksMysql2date('2026-11-23 14:05:00'));
		$this->assertSame('23.11.2026 14:05:00', ParksDate::parksMysql2date('2026-11-23 14:05:00', true));
		$this->assertSame(mktime(0, 0, 0, 11, 23, 2026), ParksDate::parksMysql2date('2026-11-23', false, true));
	}



	/**
	 * Zero, incomplete or empty dates never turn into 01.01.1970
	 */
	public function testMysql2dateReturnsEmptyForZeroOrInvalidDates(): void
	{

		$this->assertSame('', ParksDate::parksMysql2date('0000-00-00 00:00:00'));
		$this->assertSame('', ParksDate::parksMysql2date('2026'));
		$this->assertSame('', ParksDate::parksMysql2date(''));
		$this->assertSame('', ParksDate::parksMysql2date(null));
	}



	/**
	 * Empty time ranges and empty end times are removed from the time list
	 */
	public function testFormatOfferTimesDropsEmptyEndTimes(): void
	{

		$this->assertSame('', ParksDate::formatOfferTimes(''));
		$this->assertSame('', ParksDate::formatOfferTimes('00:00 - 00:00'));
		$this->assertSame('10:00 - 12:00 / 14:00', ParksDate::formatOfferTimes('10:00 - 12:00,14:00 - 00:00'));
	}



	/**
	 * More than three times are shortened to first two, ellipsis and last
	 */
	public function testFormatOfferTimesShortensLongLists(): void
	{

		$times = '08:00 - 09:00,10:00 - 11:00,12:00 - 13:00,14:00 - 15:00,16:00 - 17:00';
		$this->assertSame('08:00 - 09:00 / 10:00 - 11:00 / … / 16:00 - 17:00', ParksDate::formatOfferTimes($times));
	}



	/**
	 * Total minutes are split into hours and remaining minutes
	 */
	public function testSplitHoursAndMinutes(): void
	{

		$this->assertEquals(['hours' => 2, 'minutes' => 15], ParksDate::parksSplitHoursAndMinutes(135));
		$this->assertEquals(['hours' => 0, 'minutes' => 45], ParksDate::parksSplitHoursAndMinutes(45));
		$this->assertSame([], ParksDate::parksSplitHoursAndMinutes(0));
	}



	/**
	 * Single-day events keep their start date and time
	 */
	public function testAdjustDateFromKeepsSingleDayEvent(): void
	{

		$this->assertSame(
			'2099-05-01 10:00',
			ParksDate::parksAdjustDateFrom('2099-05-01 10:00:00', '2099-05-01 12:00:00')
		);
	}



	/**
	 * Multi-day events start at the filter date when it lies within the range
	 */
	public function testAdjustDateFromUsesFilterDateWithinRange(): void
	{

		$this->assertSame(
			'2099-05-05',
			ParksDate::parksAdjustDateFrom('2099-05-01 10:00:00', '2099-05-10 12:00:00', '2099-05-05')
		);
	}



	/**
	 * Multi-day events that already started show today as start date
	 */
	public function testAdjustDateFromUsesTodayForRunningEvent(): void
	{

		$this->assertSame(
			date('Y-m-d'),
			ParksDate::parksAdjustDateFrom('2000-01-01 10:00:00', '2099-12-31 12:00:00')
		);
	}



	/**
	 * Without an end date the start date is returned unchanged
	 */
	public function testAdjustDateFromWithoutEndDateReturnsInput(): void
	{

		$this->assertSame('2099-05-01 10:00:00', ParksDate::parksAdjustDateFrom('2099-05-01 10:00:00'));
	}



	/**
	 * Short weekday prefix per language; unparsable dates stay as they are
	 */
	public function testFormatDateWithWeekdayShort(): void
	{

		$this->assertSame('Mo, 23.11.2026', ParksDate::formatDateWithWeekdayShort('23.11.2026', TestSdkFactory::create()->lang));
		$this->assertSame('Mo, 23.11.2026', ParksDate::formatDateWithWeekdayShort('2026-11-23', TestSdkFactory::create()->lang));
		$this->assertSame('Lun 23.11.2026', ParksDate::formatDateWithWeekdayShort('23.11.2026', TestSdkFactory::create([], 'fr')->lang));
		$this->assertSame('invalid', ParksDate::formatDateWithWeekdayShort('invalid', TestSdkFactory::create()->lang));
	}



	/**
	 * Date label without weekday when display_weekday_short is disabled
	 */
	public function testFormatOfferDateLabelWithoutWeekday(): void
	{

		$lang = TestSdkFactory::create(['display_weekday_short' => false])->lang;
		$this->assertSame('23.11.2026', ParksDate::formatOfferDateLabel('2026-11-23', $lang));
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
			ParksDate::formatOfferDate($date, TestSdkFactory::create()->lang)
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
		$this->assertSame('<strong>Mo, 23.11.2026</strong> ', ParksDate::formatOfferDate($date, TestSdkFactory::create()->lang));
	}



	/**
	 * Activity duration with long or short labels, falling back to the time category
	 */
	public function testActivityTimeRequired(): void
	{

		$lang = TestSdkFactory::create()->lang;

		$this->assertSame('2 Stunden 15 Minuten', ParksDate::activityGetTimeRequired((object) ['time_required_minutes' => 135], $lang));
		$this->assertSame('1 Stunde 1 Minute', ParksDate::activityGetTimeRequired((object) ['time_required_minutes' => 61], $lang));
		$this->assertSame('2 h 15 min', ParksDate::activityGetTimeRequired((object) ['time_required_minutes' => 135], $lang, true));
		$this->assertSame('45 min', ParksDate::activityGetTimeRequired((object) ['time_required_minutes' => 45], $lang, true));
		$this->assertSame('2 Stunden', ParksDate::activityGetTimeRequired((object) ['time_required_minutes' => 120], $lang));
		$this->assertSame('Halbtag', ParksDate::activityGetTimeRequired((object) ['time_required' => 'Halbtag'], $lang));
		$this->assertSame('', ParksDate::activityGetTimeRequired((object) [], $lang));
	}
}
