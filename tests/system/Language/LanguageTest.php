<?php namespace CodeIgniter\Language;

use Tests\Support\Language\MockLanguage;
use Tests\Support\Language\SecondMockLanguage;

class LanguageTest extends \CIUnitTestCase
{

	public function testReturnsStringWithNoFileInMessage()
	{
		$lang = new MockLanguage('en');

		$this->assertEquals('something', $lang->getLine('something'));
	}

	//--------------------------------------------------------------------

	public function testGetLineReturnsLine()
	{
		$lang = new MockLanguage('en');

		$lang->setData([
			'bookSaved'	 => 'We kept the book free from the boogeyman',
			'booksSaved' => 'We saved some more'
		]);

		$this->assertEquals('We saved some more', $lang->getLine('books.booksSaved'));
	}

	//--------------------------------------------------------------------

	public function testGetLineArrayReturnsLineArray()
	{
		$lang = new MockLanguage('en');

		$lang->setData([
			'booksList' => [
				'The Boogeyman',
				'We Saved'
			]
		]);

		$this->assertEquals([
			'The Boogeyman',
			'We Saved'
				], $lang->getLine('books.booksList'));
	}

	//--------------------------------------------------------------------

	public function testGetLineFormatsMessage()
	{
		// No intl extension? then we can't test this - go away....
		if ( ! class_exists('\MessageFormatter'))
			return;

		$lang = new MockLanguage('en');

		$lang->setData([
			'bookCount' => '{0, number, integer} books have been saved.'
		]);

		$this->assertEquals('45 books have been saved.', $lang->getLine('books.bookCount', [91 / 2]));
	}

	//--------------------------------------------------------------------

	public function testGetLineArrayFormatsMessages()
	{
		// No intl extension? Then we can't test this - go away...
		if ( ! class_exists('\MessageFormatter'))
		{
			return;
		}

		$lang = new MockLanguage('en');

		$lang->setData([
			'bookList' => [
				'{0, number, integer} related books.'
			]
		]);

		$this->assertEquals(['45 related books.'], $lang->getLine('books.bookList', [91 / 2]));
	}

	//--------------------------------------------------------------------

	/**
	 * @see https://github.com/bcit-ci/CodeIgniter4/issues/891
	 */
	public function testLangAllowsOtherLocales()
	{
		$str1 = lang('Language.languageGetLineInvalidArgumentException', [], 'en');
		$str2 = lang('Language.languageGetLineInvalidArgumentException', [], 'ru');

		$this->assertEquals('Get line must be a string or array of strings.', $str1);
		$this->assertEquals('Language.languageGetLineInvalidArgumentException', $str2);
	}

	//--------------------------------------------------------------------

	public function testLangDoesntFormat()
	{
		$lang = new MockLanguage('en');
		$lang->disableIntlSupport();

		$lang->setData([
			'bookList' => [
				'{0, number, integer} related books.'
			]
		]);

		$this->assertEquals(['{0, number, integer} related books.'], $lang->getLine('books.bookList', [15]));
	}

	//--------------------------------------------------------------------

	public function testLanguageDuplicateKey()
	{
		$lang = new Language('en');
		$this->assertEquals('These are not the droids you are looking for', $lang->getLine('More.strongForce', []));
		$this->assertEquals('I have a very bad feeling about this', $lang->getLine('More.cannotMove', []));
		$this->assertEquals('Could not move file {0} to {1} ({2})', $lang->getLine('Files.cannotMove', []));
		$this->assertEquals('I have a very bad feeling about this', $lang->getLine('More.cannotMove', []));
	}

	//--------------------------------------------------------------------

	public function testLanguageFileLoading()
	{
		$lang = new SecondMockLanguage('en');

		$result = $lang->loadem('More', 'en');
		$this->assertTrue(in_array('More', $lang->loaded()));
		$result = $lang->loadem('More', 'en');
		$this->assertEquals(1, count($lang->loaded())); // should only be there once
	}

	//--------------------------------------------------------------------

	public function testLanguageFileLoadingReturns()
	{
		$lang = new SecondMockLanguage('en');

		$result = $lang->loadem('More', 'en', true);
		$this->assertFalse(in_array('More', $lang->loaded()));
		$this->assertEquals(3, count($result));
		$result = $lang->loadem('More', 'en');
		$this->assertTrue(in_array('More', $lang->loaded()));
		$this->assertEquals(1, count($lang->loaded()));
	}

}
