<?php

namespace Tests\Unit;

use App\Services\ConversionParser;
use Tests\TestCase;

class ConversionParserTest extends TestCase
{
    private ConversionParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ConversionParser();
    }

    public function test_parse_standard_arrow_format(): void
    {
        $result = $this->parser->parse('100 USD -> UZS');

        $this->assertNotNull($result);
        $this->assertEquals(100, $result['amount']);
        $this->assertEquals('USD', $result['from']);
        $this->assertEquals('UZS', $result['to']);
    }

    public function test_parse_to_keyword_format(): void
    {
        $result = $this->parser->parse('50 EUR to RUB');

        $this->assertNotNull($result);
        $this->assertEquals(50, $result['amount']);
        $this->assertEquals('EUR', $result['from']);
        $this->assertEquals('RUB', $result['to']);
    }

    public function test_parse_russian_format(): void
    {
        $result = $this->parser->parse('100 долларов в сумы');

        $this->assertNotNull($result);
        $this->assertEquals(100, $result['amount']);
        $this->assertEquals('USD', $result['from']);
        $this->assertEquals('UZS', $result['to']);
    }

    public function test_parse_compact_format(): void
    {
        $result = $this->parser->parse('500USD-EUR');

        $this->assertNotNull($result);
        $this->assertEquals(500, $result['amount']);
        $this->assertEquals('USD', $result['from']);
        $this->assertEquals('EUR', $result['to']);
    }

    public function test_parse_simple_format_defaults_to_uzs(): void
    {
        $result = $this->parser->parse('100 USD');

        $this->assertNotNull($result);
        $this->assertEquals(100, $result['amount']);
        $this->assertEquals('USD', $result['from']);
        $this->assertEquals('UZS', $result['to']);
    }

    public function test_parse_amount_with_spaces(): void
    {
        $result = $this->parser->parse('1 000 000 UZS to USD');

        $this->assertNotNull($result);
        $this->assertEquals(1000000, $result['amount']);
    }

    public function test_parse_amount_with_commas(): void
    {
        $result = $this->parser->parse('1,500.50 EUR -> UZS');

        $this->assertNotNull($result);
        $this->assertEquals(1500.50, $result['amount']);
    }

    public function test_parse_currency_symbols(): void
    {
        $result = $this->parser->parse('100 $ -> сум');

        $this->assertNotNull($result);
        $this->assertEquals('USD', $result['from']);
        $this->assertEquals('UZS', $result['to']);
    }

    public function test_parse_russian_currency_names(): void
    {
        $result = $this->parser->parse('500 рублей в доллары');

        $this->assertNotNull($result);
        $this->assertEquals('RUB', $result['from']);
        $this->assertEquals('USD', $result['to']);
    }

    public function test_parse_uzbek_format(): void
    {
        $result = $this->parser->parse("1000 dollar ga so'm");

        $this->assertNotNull($result);
        $this->assertEquals('USD', $result['from']);
        $this->assertEquals('UZS', $result['to']);
    }

    public function test_parse_returns_null_for_invalid_input(): void
    {
        $this->assertNull($this->parser->parse('hello world'));
        $this->assertNull($this->parser->parse(''));
        $this->assertNull($this->parser->parse('just text'));
    }

    public function test_is_conversion_request(): void
    {
        $this->assertTrue($this->parser->isConversionRequest('100 USD -> UZS'));
        $this->assertTrue($this->parser->isConversionRequest('50 евро'));
        $this->assertFalse($this->parser->isConversionRequest('hello'));
    }

    public function test_parse_case_insensitive(): void
    {
        $result1 = $this->parser->parse('100 usd -> uzs');
        $result2 = $this->parser->parse('100 USD -> UZS');

        $this->assertEquals($result1['from'], $result2['from']);
        $this->assertEquals($result1['to'], $result2['to']);
    }
}

