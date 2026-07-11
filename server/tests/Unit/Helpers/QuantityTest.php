<?php

use App\Helpers\Quantity;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

// ===== parseQuantityDisplayToNumber() メソッドのテストケース =====

test('5-1-1: 【parseQuantityDisplayToNumber】 分数をパースする', function () {
    expect(Quantity::parseQuantityDisplayToNumber('1/2'))->toBe(0.5);
    expect(Quantity::parseQuantityDisplayToNumber('2/3'))->toBe(round(2 / 3, 3));
    expect(Quantity::parseQuantityDisplayToNumber('3/4'))->toBe(0.75);
});

test('5-1-2: 【parseQuantityDisplayToNumber】 帯分数をパースする', function () {
    expect(Quantity::parseQuantityDisplayToNumber('1 1/2'))->toBe(1.5);
    expect(Quantity::parseQuantityDisplayToNumber('1と1/2'))->toBe(1.5);
    expect(Quantity::parseQuantityDisplayToNumber('2 1/4'))->toBe(2.25);
    expect(Quantity::parseQuantityDisplayToNumber('2と1/4'))->toBe(2.25);
});

test('5-1-3: 【parseQuantityDisplayToNumber】 小数をパースする', function () {
    expect(Quantity::parseQuantityDisplayToNumber('1.5'))->toBe(1.5);
    expect(Quantity::parseQuantityDisplayToNumber('.5'))->toBe(0.5);
});

test('5-1-4: 【parseQuantityDisplayToNumber】 整数をパースする', function () {
    expect(Quantity::parseQuantityDisplayToNumber('2'))->toBe(2.0);
    expect(Quantity::parseQuantityDisplayToNumber('200'))->toBe(200.0);
});

test('5-1-5: 【parseQuantityDisplayToNumber】 前後の空白をトリムする', function () {
    expect(Quantity::parseQuantityDisplayToNumber('  1/2  '))->toBe(0.5);
});

test('5-1-6: 【parseQuantityDisplayToNumber】 空欄・不正値・負数・ゼロ除算は null', function (string $input) {
    expect(Quantity::parseQuantityDisplayToNumber($input))->toBeNull();
})->with([
    'empty' => [''],
    'whitespace' => ['   '],
    'negative' => ['-1'],
    'invalid text' => ['abc'],
    'zero denominator' => ['1/0'],
    'mixed zero denominator' => ['1 1/0'],
    'mixed to zero denominator' => ['1と1/0'],
]);

// ===== formatQuantityDisplay() メソッドのテストケース =====

test('5-1-7: 【formatQuantityDisplay】 既知分数は分数表記にする', function () {
    expect(Quantity::formatQuantityDisplay(0.5))->toBe('1/2');
    expect(Quantity::formatQuantityDisplay(1.5))->toBe('1と1/2');
    expect(Quantity::formatQuantityDisplay(2.0))->toBe('2');
});

test('5-1-8: 【formatQuantityDisplay】 10 以上は小数表記にフォールバックする', function () {
    expect(Quantity::formatQuantityDisplay(200.0))->toBe('200');
});

test('5-1-9: 【formatQuantityDisplay】 null は空文字', function () {
    expect(Quantity::formatQuantityDisplay(null))->toBe('');
});

// ===== normalizeQuantityDisplay() メソッドのテストケース =====

test('5-1-10: 【normalizeQuantityDisplay】 表記種別に応じて正規化する', function () {
    expect(Quantity::normalizeQuantityDisplay('1/2', 0.5))->toBe('1/2');
    expect(Quantity::normalizeQuantityDisplay('1.50', 1.5))->toBe('1.5');
    expect(Quantity::normalizeQuantityDisplay('2', 2.0))->toBe('2');
    expect(Quantity::normalizeQuantityDisplay('1 1/2', 1.5))->toBe('1 1/2');
    expect(Quantity::normalizeQuantityDisplay('1と1/2', 1.5))->toBe('1と1/2');
});

test('5-1-11: 【normalizeQuantityDisplay】 空欄は null', function () {
    expect(Quantity::normalizeQuantityDisplay('', 1.0))->toBeNull();
});

// ===== normalizeQuantityFromDisplay() メソッドのテストケース =====

test('5-1-12: 【normalizeQuantityFromDisplay】 requiresQuantity=false のとき両方 null', function () {
    expect(Quantity::normalizeQuantityFromDisplay('1/2', false))->toBe([
        'quantity' => null,
        'quantityDisplay' => null,
    ]);

    expect(Quantity::normalizeQuantityFromDisplay(null, false))->toBe([
        'quantity' => null,
        'quantityDisplay' => null,
    ]);
});

test('5-1-13: 【normalizeQuantityFromDisplay】 display から quantity と display を導出する', function () {
    expect(Quantity::normalizeQuantityFromDisplay('1/2', true))->toBe([
        'quantity' => 0.5,
        'quantityDisplay' => '1/2',
    ]);

    expect(Quantity::normalizeQuantityFromDisplay('200', true))->toBe([
        'quantity' => 200.0,
        'quantityDisplay' => '200',
    ]);
});

test('5-1-14: 【normalizeQuantityFromDisplay】 不正 display は ValidationException', function () {
    Quantity::normalizeQuantityFromDisplay('abc', true);
})->throws(ValidationException::class);

test('5-1-15: 【normalizeQuantityFromDisplay】 display 未指定は ValidationException', function () {
    Quantity::normalizeQuantityFromDisplay(null, true);
})->throws(ValidationException::class);

test('5-1-16: 【normalizeQuantityFromDisplay】 カスタム errorKey を ValidationException に含める', function () {
    try {
        Quantity::normalizeQuantityFromDisplay('abc', true, 'ingredients.0.quantityDisplay');
        expect(false)->toBeTrue('ValidationException should have been thrown');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('ingredients.0.quantityDisplay');
        expect($e->errors()['ingredients.0.quantityDisplay'][0])->toBe(__('validation.invalid_quantity_display'));
    }
});

// ===== normalizeQuantityPair() メソッドのテストケース =====

test('5-1-17: 【normalizeQuantityPair】 requiresQuantity=false のとき両方 null', function () {
    expect(Quantity::normalizeQuantityPair(1.0, '1', false))->toBe([
        'quantity' => null,
        'quantityDisplay' => null,
    ]);
});

test('5-1-18: 【normalizeQuantityPair】 display から quantity と display を導出する', function () {
    expect(Quantity::normalizeQuantityPair(null, '1/2', true))->toBe([
        'quantity' => 0.5,
        'quantityDisplay' => '1/2',
    ]);
});

test('5-1-19: 【normalizeQuantityPair】 quantity のみのとき display を補完する', function () {
    expect(Quantity::normalizeQuantityPair(1.0, null, true))->toBe([
        'quantity' => 1.0,
        'quantityDisplay' => '1',
    ]);
});

test('5-1-20: 【normalizeQuantityPair】 不正 display は quantity から補完する', function () {
    expect(Quantity::normalizeQuantityPair(0.5, 'abc', true))->toBe([
        'quantity' => 0.5,
        'quantityDisplay' => '1/2',
    ]);
});

test('5-1-21: 【normalizeQuantityPair】 数量なしは両方 null', function () {
    expect(Quantity::normalizeQuantityPair(null, null, true))->toBe([
        'quantity' => null,
        'quantityDisplay' => null,
    ]);
});

test('5-1-22: 【normalizeQuantityPair】 quantity と display が矛盾する場合は quantity を優先する', function () {
    expect(Quantity::normalizeQuantityPair(1.0, '1/2', true))->toBe([
        'quantity' => 1.0,
        'quantityDisplay' => '1',
    ]);

    expect(Quantity::normalizeQuantityPair(1.5, '2 1/2', true))->toBe([
        'quantity' => 1.5,
        'quantityDisplay' => '1 1/2',
    ]);
});

test('5-1-23: 【normalizeQuantityPair】 quantity と display が一致する場合は display から導出する', function () {
    expect(Quantity::normalizeQuantityPair(0.5, '1/2', true))->toBe([
        'quantity' => 0.5,
        'quantityDisplay' => '1/2',
    ]);
});

// ===== stripUnitFromDisplay() メソッドのテストケース =====

test('5-1-24: 【stripUnitFromDisplay】 単位マスタの position に応じて単位名を除去する', function () {
    expect(Quantity::stripUnitFromDisplay('大さじ1', '大さじ', 'prefix'))->toBe('1');
    expect(Quantity::stripUnitFromDisplay('大さじ1/2', '大さじ', 'prefix'))->toBe('1/2');
    expect(Quantity::stripUnitFromDisplay('大さじ1と1/2', '大さじ', 'prefix'))->toBe('1と1/2');
    expect(Quantity::stripUnitFromDisplay('小さじ2', '小さじ', 'prefix'))->toBe('2');
    expect(Quantity::stripUnitFromDisplay('1/2', '大さじ', 'prefix'))->toBe('1/2');
    expect(Quantity::stripUnitFromDisplay('1個', '個', 'suffix'))->toBe('1');
    expect(Quantity::stripUnitFromDisplay('1/2本', '本', 'suffix'))->toBe('1/2');
    expect(Quantity::stripUnitFromDisplay('200g', 'g', 'suffix'))->toBe('200');
    expect(Quantity::stripUnitFromDisplay('1', '個', 'suffix'))->toBe('1');
    expect(Quantity::stripUnitFromDisplay('大さじ1', '個', 'suffix'))->toBe('大さじ1');
    expect(Quantity::stripUnitFromDisplay('1個', '個', 'prefix'))->toBe('1個');
    expect(Quantity::stripUnitFromDisplay('大さじ1', '大さじ', null))->toBe('大さじ1');
    expect(Quantity::stripUnitFromDisplay('大さじ1', null, 'prefix'))->toBe('大さじ1');
});

// ===== 全角入力の正規化（sanitizeQuantityDisplayInput）テストケース =====

test('5-1-25: 【parseQuantityDisplayToNumber】 全角スペース区切りの帯分数をパースする', function () {
    expect(Quantity::parseQuantityDisplayToNumber("1\u{3000}1/2"))->toBe(1.5);
});

test('5-1-26: 【parseQuantityDisplayToNumber】 全角数字・全角スラッシュの分数をパースする', function () {
    expect(Quantity::parseQuantityDisplayToNumber('１/２'))->toBe(0.5);
});

test('5-1-27: 【parseQuantityDisplayToNumber】 全角表記の帯分数をパースする', function () {
    expect(Quantity::parseQuantityDisplayToNumber('１と１／２'))->toBe(1.5);
});

test('5-1-28: 【normalizeQuantityDisplay】 全角スペース区切りの帯分数を半角に正規化する', function () {
    expect(Quantity::normalizeQuantityDisplay("1\u{3000}1/2", 1.5))->toBe('1 1/2');
});

test('5-1-29: 【normalizeQuantityDisplay】 全角表記の帯分数を半角に正規化する', function () {
    expect(Quantity::normalizeQuantityDisplay('１と１／２', 1.5))->toBe('1と1/2');
});

test('5-1-30: 【normalizeQuantityPair】 全角表記の display を半角に正規化する', function () {
    expect(Quantity::normalizeQuantityPair(1.5, "１\u{3000}１/２", true))->toBe([
        'quantity' => 1.5,
        'quantityDisplay' => '1 1/2',
    ]);
});
