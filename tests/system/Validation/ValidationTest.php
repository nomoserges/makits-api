<?php namespace CodeIgniter\Validation;

use CodeIgniter\Validation\Exceptions\ValidationException;
use Config\Services;
use Config\App;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;

class ValidationTest extends \CIUnitTestCase
{

	/**
	 * @var Validation
	 */
	protected $validation;
	protected $config = [
		'ruleSets'		 => [
			\CodeIgniter\Validation\Rules::class,
			\CodeIgniter\Validation\FormatRules::class,
			\CodeIgniter\Validation\FileRules::class,
			\CodeIgniter\Validation\CreditCardRules::class,
			\Tests\Support\Validation\TestRules::class,
		],
		'groupA'		 => [
			'foo' => 'required|min_length[5]',
		],
		'groupA_errors'	 => [
			'foo' => [
				'min_length' => 'Shame, shame. Too short.',
			],
		],
		'groupX'		 => 'Not an array, so not a real group',
		'templates'		 => [
			'list'	 => 'CodeIgniter\Validation\Views\list',
			'single' => 'CodeIgniter\Validation\Views\single'
		],
	];

//--------------------------------------------------------------------

	public function setUp()
	{
		parent::setUp();

		Services::reset();

		$this->validation = new Validation((object) $this->config, \Config\Services::renderer());
		$this->validation->reset();

		$_FILES = [];
	}

//--------------------------------------------------------------------

	public function testSetRulesStoresRules()
	{
		$rules = [
			'foo'	 => 'bar|baz',
			'bar'	 => 'baz|belch',
		];

		$this->validation->setRules($rules);

		$this->assertEquals($rules, $this->validation->getRules());
	}

//--------------------------------------------------------------------

	public function testRunReturnsFalseWithNothingToDo()
	{
		$this->validation->setRules([]);

		$this->assertFalse($this->validation->run([]));
	}

//--------------------------------------------------------------------

	public function testRunDoesTheBasics()
	{
		$data = [
			'foo' => 'notanumber',
		];

		$this->validation->setRules([
			'foo' => 'is_numeric',
		]);

		$this->assertFalse($this->validation->run($data));
	}

//--------------------------------------------------------------------

	public function testRunReturnsLocalizedErrors()
	{
		$data = [
			'foo' => 'notanumber',
		];

		$this->validation->setRules([
			'foo' => 'is_numeric',
		]);

		$this->assertFalse($this->validation->run($data));
		$this->assertEquals('Validation.is_numeric', $this->validation->getError('foo'));
	}

//--------------------------------------------------------------------

	public function testRunWithCustomErrors()
	{
		$data = [
			'foo' => 'notanumber',
		];

		$messages = [
			'foo' => [
				'is_numeric' => 'Nope. Not a number.',
			],
		];

		$this->validation->setRules([
			'foo' => 'is_numeric',
				], $messages);

		$this->validation->run($data);
		$this->assertEquals('Nope. Not a number.', $this->validation->getError('foo'));
	}

//--------------------------------------------------------------------

	public function testCheck()
	{
		$this->assertFalse($this->validation->check('notanumber', 'is_numeric'));
	}

//--------------------------------------------------------------------

	public function testCheckLocalizedError()
	{
		$this->assertFalse($this->validation->check('notanumber', 'is_numeric'));
		$this->assertEquals('Validation.is_numeric', $this->validation->getError());
	}

//--------------------------------------------------------------------

	public function testCheckCustomError()
	{
		$this->validation->check('notanumber', 'is_numeric', [
			'is_numeric' => 'Nope. Not a number.'
		]);
		$this->assertEquals('Nope. Not a number.', $this->validation->getError());
	}

//--------------------------------------------------------------------

	public function testGetErrors()
	{
		$data = [
			'foo' => 'notanumber',
		];

		$this->validation->setRules([
			'foo' => 'is_numeric',
		]);

		$this->validation->run($data);

		$this->assertEquals(['foo' => 'Validation.is_numeric'], $this->validation->getErrors());
	}

//--------------------------------------------------------------------

	public function testGetErrorsWhenNone()
	{
		$_SESSION = [];

		$data = [
			'foo' => 123,
		];

		$this->validation->setRules([
			'foo' => 'is_numeric',
		]);

		$this->validation->run($data);

		$this->assertEquals([], $this->validation->getErrors());
	}

//--------------------------------------------------------------------

	public function testSetErrors()
	{
		$this->validation->setRules([
			'foo' => 'is_numeric',
		]);

		$this->validation->setError('foo', 'Nadda');

		$this->assertEquals(['foo' => 'Nadda'], $this->validation->getErrors());
	}

//--------------------------------------------------------------------

	public function testRulesReturnErrors()
	{
		$this->validation->setRules([
			'foo' => 'customError'
		]);

		$this->validation->run(['foo' => 'bar']);

		$this->assertEquals(['foo' => 'My lovely error'], $this->validation->getErrors());
	}

//--------------------------------------------------------------------

	public function testGroupsReadFromConfig()
	{
		$data = [
			'foo' => 'bar',
		];

		$this->assertFalse($this->validation->run($data, 'groupA'));
		$this->assertEquals('Shame, shame. Too short.', $this->validation->getError('foo'));
	}

//--------------------------------------------------------------------

	public function testGroupsReadFromConfigValid()
	{
		$data = [
			'foo' => 'barsteps',
		];

		$this->assertTrue($this->validation->run($data, 'groupA'));
	}

//--------------------------------------------------------------------

	public function testGetRuleGroup()
	{
		$this->assertEquals([
			'foo' => 'required|min_length[5]',
				], $this->validation->getRuleGroup('groupA'));
	}

//--------------------------------------------------------------------

	public function testGetRuleGroupException()
	{
		$this->expectException(ValidationException::class);
		$this->validation->getRuleGroup('groupZ');
	}

//--------------------------------------------------------------------

	public function testSetRuleGroup()
	{
		$this->validation->setRuleGroup('groupA');

		$this->assertEquals([
			'foo' => 'required|min_length[5]',
				], $this->validation->getRules());
	}

//--------------------------------------------------------------------

	public function testSetRuleGroupException()
	{
		$this->expectException(ValidationException::class);

		$this->validation->setRuleGroup('groupZ');
	}

//--------------------------------------------------------------------

	/**
	 * @dataProvider rulesSetupProvider
	 */
	public function testRulesSetup($rules, $expected, $errors = [])
	{
		$data = [
			'foo' => '',
		];

		$this->validation->setRules([
			'foo' => $rules,
				], $errors);

		$this->validation->run($data);

		$this->assertEquals($expected, $this->validation->getError('foo'));
	}

//--------------------------------------------------------------------

	public function rulesSetupProvider()
	{
		return [
			[
				'min_length[10]',
				'The foo field must be at least 10 characters in length.'
			],
			[
				'min_length[10]',
				'The foo field is very short.',
				['foo' => ['min_length' => 'The {field} field is very short.']]
			],
			[
				['min_length[10]'],
				'The foo field must be at least 10 characters in length.'
			],
			[
				['min_length[10]'],
				'The foo field is very short.',
				['foo' => ['min_length' => 'The {field} field is very short.']]
			],
			[
				['rules' => 'min_length[10]'],
				'The foo field must be at least 10 characters in length.'
			],
			[
				['label' => 'Foo Bar', 'rules' => 'min_length[10]'],
				'The Foo Bar field must be at least 10 characters in length.'
			],
			[
				['label' => 'Foo Bar', 'rules' => 'min_length[10]'],
				'The Foo Bar field is very short.',
				['foo' => ['min_length' => 'The {field} field is very short.']]
			],
			[
				[
					'label'	 => 'Foo Bar',
					'rules'	 => 'min_length[10]',
					'errors' => ['min_length' => 'The {field} field is very short.']
				],
				'The Foo Bar field is very short.',
			],
		];
	}

//--------------------------------------------------------------------

	public function testInvalidRule()
	{
		$this->expectException(ValidationException::class);

		$rules = [
			'foo'	 => 'bar|baz',
			'bar'	 => 'baz|belch',
		];
		$this->validation->setRules($rules);

		$data = [
			'foo' => '',
		];
		$this->validation->run($data);
	}

//--------------------------------------------------------------------

	public function testRawInput()
	{
		$rawstring = 'username=admin001&role=administrator&usepass=0';

		$data = [
			'username'	 => 'admin001',
			'role'		 => 'administrator',
			'usepass'	 => 0
		];

		$config = new App();
		$config->baseURL = 'http://example.com';

		$request = new IncomingRequest($config, new URI(), $rawstring, new UserAgent());
		$request->setMethod('patch');

		$rules = [
			'role' => 'required|min_length[5]',
		];
		$this->validation->withRequest($request)
				->run($data);

		$this->assertEquals([], $this->validation->getErrors());
	}

//--------------------------------------------------------------------

	public function testHasRule()
	{
		$this->validation->setRuleGroup('groupA');

		$this->assertTrue($this->validation->hasRule('foo'));
	}

//--------------------------------------------------------------------

	public function testNotARealGroup()
	{
		$this->expectException(ValidationException::class);
		$this->validation->setRuleGroup('groupX');
		$this->validation->getRuleGroup('groupX');
	}

//--------------------------------------------------------------------

	public function testBadTemplate()
	{
		$this->expectException(ValidationException::class);
		$this->validation->listErrors('obviouslyBadTemplate');
	}

//--------------------------------------------------------------------

	public function testShowNonError()
	{
		$this->validation->setRules([
			'foo' => 'is_numeric',
		]);

		$this->validation->setError('foo', 'Nadda');

		$this->assertEquals('', $this->validation->showError('bogus'));
	}

//--------------------------------------------------------------------

	public function testShowBadTemplate()
	{
		$this->expectException(ValidationException::class);

		$this->validation->setRules([
			'foo' => 'is_numeric',
		]);
		$this->validation->setError('foo', 'Nadda');

		$this->assertEquals('We should never get here', $this->validation->showError('foo','bogus_template'));
	}

//--------------------------------------------------------------------

	public function testNoRuleSetsSetup()
	{
		$this->expectException(ValidationException::class);

		$this->config['ruleSets'] = null;
		$this->validation = new Validation((object) $this->config, \Config\Services::renderer());
		$this->validation->reset();

		$data = [
			'foo' => '',
		];

		$this->validation->run($data);
	}

//--------------------------------------------------------------------

	public function testNotCustomRuleGroup()
	{
		$this->expectException(ValidationException::class);


		$data = [
			'foo' => '',
		];

		$this->validation->run($data, 'GeorgeRules');
	}

//--------------------------------------------------------------------

	public function testNotRealCustomRule()
	{
		$this->expectException(ValidationException::class);


		$data = [
			'foo' => '',
		];

		$this->validation->run($data, 'groupX');
	}

//--------------------------------------------------------------------

	public function testHasError()
	{
		$data = [
			'foo' => 'notanumber',
		];

		$this->validation->setRules([
			'foo' => 'is_numeric',
		]);

		$this->validation->run($data);

		$this->assertTrue($this->validation->hasError('foo'));
	}

}
