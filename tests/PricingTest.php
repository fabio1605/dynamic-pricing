<?php

use PHPUnit\Framework\TestCase;

class PricingTest extends TestCase {

    public function test_basic_logic() {
        $this->assertEquals(4, 2 + 2);
    }

    public function test_price_function_exists() {
        $this->assertTrue(function_exists('calculate_dynamic_price'));
    }

    public function test_price_output() {
        $price = calculate_dynamic_price('2025-06-01', 'The Oakwood');
        $this->assertIsNumeric($price);
        $this->assertGreaterThan(0, $price);
    }
}
