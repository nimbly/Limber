<?php

namespace Limber;

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
	public function __toString()
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
	public function getSize()
	{
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function tell()
	{
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function eof()
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isSeekable()
	{
		return false;
	}

	/**
	 * @inheritDoc
	 * @param int $offset
	 * @param int $whence
	 * @return void
	 */
	public function seek($offset, $whence = SEEK_SET)
	{
		throw new RuntimeException("Stream not seekable");
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	public function rewind()
	{
		$this->seek(0);
	}

	/**
	 * @inheritDoc
	 */
	public function isWritable()
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function write($string)
	{
		throw new RuntimeException("Stream not writeable.");
	}

	/**
	 * @inheritDoc
	 */
	public function isReadable()
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function read($length)
	{
		return "";
	}

	/**
	 * @inheritDoc
	 */
	public function getContents()
	{
		return "";
	}

	/**
	 * @inheritDoc
	 */
	public function getMetadata($key = null)
	{
		return null;
	}
}