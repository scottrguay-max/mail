<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Support;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Support\Utf8;
use function mb_check_encoding;

class Utf8Test extends TestCase {

	public function testNullIsPassedThrough(): void {
		$this->assertNull(Utf8::sanitize(null));
	}

	public function testEmptyStringIsPassedThrough(): void {
		$this->assertSame('', Utf8::sanitize(''));
	}

	/**
	 * @dataProvider validUtf8Provider
	 */
	public function testValidUtf8IsUntouched(string $value): void {
		$this->assertSame($value, Utf8::sanitize($value));
	}

	public function validUtf8Provider(): array {
		return [
			['plain ascii subject'],
			['café'],
			['台北'],
			['😊'],
		];
	}

	public function testLegacyEightBitTextIsConverted(): void {
		// "café" as windows-1252: no multi-byte UTF-8 sequence, so the characters are
		// recovered rather than dropped.
		$this->assertSame('café', Utf8::sanitize("caf\xe9"));
	}

	public function testWindows1252PunctuationIsConverted(): void {
		$this->assertSame('“quoted”', Utf8::sanitize("\x93quoted\x94"));
	}

	public function testDamagedUtf8KeepsItsValidText(): void {
		// Valid UTF-8 "café" plus one stray byte: converting the whole string from
		// windows-1252 would yield "cafÃ©", so only the stray byte may be dropped.
		$this->assertSame('café', Utf8::sanitize("caf\xc3\xa9\x8c"));
	}

	/**
	 * @dataProvider invalidUtf8Provider
	 */
	public function testResultIsAlwaysValidUtf8(string $value): void {
		$this->assertTrue(mb_check_encoding(Utf8::sanitize($value), 'UTF-8'));
	}

	public function invalidUtf8Provider(): array {
		return [
			["caf\xe9"],
			["caf\xc3\xa9\x8c"],
			["\x8c\x8d\x8e"],
			["subject with \xa4 and \xc3\xa9"],
		];
	}
}
