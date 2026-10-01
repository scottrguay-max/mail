<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Support;

use function iconv;
use function is_string;
use function mb_check_encoding;
use function preg_match;

class Utf8 {

	/**
	 * Make IMAP-supplied text safe to store without mangling what is already correct.
	 *
	 * Mail can carry invalid UTF-8: a header may hold raw 8-bit bytes, and a part may
	 * declare utf-8 while actually containing windows-1252. PostgreSQL rejects such
	 * values ("invalid byte sequence for encoding UTF8"), which aborts the sync of the
	 * whole mailbox.
	 *
	 * Converting everything from windows-1252 is not safe either: text that is mostly
	 * valid UTF-8 with a few broken bytes would come out as mojibake. Hence three cases:
	 *
	 * - valid UTF-8 is returned unchanged;
	 * - text without a single well-formed multi-byte sequence is treated as
	 *   windows-1252, which preserves accented characters instead of dropping them;
	 * - anything else is UTF-8 with damage, so only the invalid sequences are dropped.
	 */
	public static function sanitize(?string $value): ?string {
		if ($value === null || $value === '' || mb_check_encoding($value, 'UTF-8')) {
			return $value;
		}

		if (!self::hasMultiByteUtf8($value)) {
			$converted = @iconv('windows-1252', 'UTF-8', $value);
			if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
				return $converted;
			}
		}

		$cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

		return is_string($cleaned) ? $cleaned : '';
	}

	/**
	 * Whether the value holds at least one well-formed multi-byte UTF-8 sequence, which
	 * means the text is UTF-8 with damage rather than legacy single-byte text.
	 */
	private static function hasMultiByteUtf8(string $value): bool {
		return preg_match(
			'/[\xC2-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF4][\x80-\xBF]{3}/',
			$value
		) === 1;
	}
}
