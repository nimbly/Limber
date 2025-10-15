<?php

namespace Nimbly\Limber\Tests;

use Nimbly\Limber\EmptyStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(EmptyStream::class)]
class EmptyStreamTest extends TestCase
{
	public function test_casting_to_string_returns_empty_string(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertEquals("", (string) $emptyStream);
	}

	public function test_close_returns_void(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertNull($emptyStream->close());
	}

	public function test_detach_returns_null(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertNull($emptyStream->detach());
	}

	public function test_get_size_returns_zero(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertEquals(0, $emptyStream->getSize());
	}

	public function test_tell_returns_zero(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertEquals(0, $emptyStream->tell());
	}

	public function test_eof_returns_true(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertTrue($emptyStream->eof());
	}

	public function test_is_seekable_returns_false(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertFalse($emptyStream->isSeekable());
	}

	public function test_seek_throws_runtime_exception(): void
	{
		$emptyStream = new EmptyStream;

		$this->expectException(RuntimeException::class);
		$emptyStream->seek(1);
	}

	public function test_rewind_throws_runtime_exception(): void
	{
		$emptyStream = new EmptyStream;

		$this->expectException(RuntimeException::class);
		$emptyStream->rewind();
	}

	public function test_is_writeable_returns_false(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertFalse($emptyStream->isWritable());
	}

	public function test_write_throws_runtime_exception(): void
	{
		$emptyStream = new EmptyStream;

		$this->expectException(RuntimeException::class);
		$emptyStream->write("foo");
	}

	public function test_is_readable_returns_false(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertTrue($emptyStream->isReadable());
	}

	public function test_read_returns_empty_string(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertEquals("", $emptyStream->read(12));
	}

	public function test_get_contents_returns_empty_string(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertEquals("", $emptyStream->getContents());
	}

	public function test_get_metadata_returns_null(): void
	{
		$emptyStream = new EmptyStream;
		$this->assertNull($emptyStream->getMetadata());
	}
}