<?php

namespace Nimbly\Limber;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 *
 * An EmptyStream represents a StreamInterface instance that returns an empty string (no content).
 *
 * This is useful for forcing the response body to be empty on certain responses that require an
 * empty body in order to maintain HTTP standards.
 *
 */
class EmptyStream implements StreamInterface
{
	/**
	 * @inheritDoc
	 */
	public function __toString(): string
	{
		return $this->getContents();
	}

	/**
	 * @inheritDoc
	 */
	public function close(): void
	{
		return;
	}

	/**
	 * @inheritDoc
	 */
	public function detach()
	{
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getSize(): int
	{
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function tell(): bool
	{
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function eof(): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isSeekable(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		throw new RuntimeException("Stream not seekable");
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	public function rewind(): void
	{
		$this->seek(0);
	}

	/**
	 * @inheritDoc
	 */
	public function isWritable(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function write(string $string): int
	{
		throw new RuntimeException("Stream not writeable.");
	}

	/**
	 * @inheritDoc
	 */
	public function isReadable(): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function read(int $length): string
	{
		return "";
	}

	/**
	 * @inheritDoc
	 */
	public function getContents(): string
	{
		return "";
	}

	/**
	 * @inheritDoc
	 */
	public function getMetadata(?string $key = null): mixed
	{
		return null;
	}
}