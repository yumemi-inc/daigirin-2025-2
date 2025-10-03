---
class: content
---

<div class="doc-header">
  <div class="doc-title">PHP で始める振る舞い駆動開発</div>
  <div class="doc-author">うーたん（@uutan1108）</div>
</div>

# PHP で始める振る舞い駆動開発

## はじめに

振る舞い駆動開発（Behaviour-Driven Development、以下 BDD）は、テスト駆動開発（TDD）を拡張した開発手法です。BDD では、自然言語で要求仕様や振る舞いを記述し、顧客が求める具体的な価値に焦点を当てたテストを作成します。

この章では、PHP を使用して BDD を実践する方法について解説します。

## BDDとは

### TDDとの関係

テスト駆動開発（TDD）は、テストファーストでコードを洗練させる開発手法です。振る舞い駆動開発（BDD）は、TDD から派生した、自然言語で要求仕様や振る舞いを記述するテスト駆動開発です。

### BDDにおける「振る舞い」とは

BDD における「振る舞い」は、顧客が求める具体的な価値です。技術的な実装詳細ではなく、顧客が期待する動作とその価値に焦点を当てます。

## 振る舞いを意識したテストコードの例

BDD の概念を理解するために、具体的な例を見てみましょう。良い例と悪い例を比較することで、振る舞いを意識したテストの重要性が明確になります。

BDD では、Gherkin という自然言語ベースの記述形式を使用してテストシナリオを作成します。まず、Gherkin の基本構文について説明します。

### Gherkinの基本構文

Gherkin は、BDD で使用する自然言語ベースの記述形式です。次の要素で構成されています。

#### フィーチャー（Feature）

フィーチャーは、テスト対象の機能や特徴を定義する最上位の要素です。機能の目的と価値を明確に表現します。

```gherkin
Feature: レジシステムでの商品購入
  As a 店員
  I can 商品をスキャンして合計金額を計算する
  so that 顧客が正確な金額を確認できる
```

#### シナリオ（Scenario）

シナリオは、具体的なテストケースを定義します。ユーザーがどのような状況で何を行い、どのような結果を期待するかを記述します。

```gherkin
Scenario: 複数の商品を購入する
  Given レジシステムを起動する
  When "りんご" を "2" 個 スキャンする
  And "みかん" を "1" 個 スキャンする
  Then 合計金額は "400" 円 であること
```

#### ステップ（Step）

ステップは、シナリオ内の各処理を記述する最小単位です。Given、When、Then の 3 つの種類があります。

- Given は、テストの前提条件や初期状態を設定する
- When は、実行するアクションや操作を定義する
- Then は、期待する結果や検証項目を記述する

#### キーワードの説明

<table>
<thead>
<tr>
<th><span class="no-break">キーワード</span></th>
<th>説明</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Feature</strong></td>
<td>テスト対象の機能や特徴を定義します。機能の目的と価値を明確に表現します。</td>
</tr>
<tr>
<td><strong>Scenario</strong></td>
<td>具体的なテストケースを定義します。ユーザーがどのような状況で何を行うかを記述します。</td>
</tr>
<tr>
<td><strong>Given</strong></td>
<td>テストの前提条件を記述します。テストを実行する前の状態や環境を設定します。</td>
</tr>
<tr>
<td><strong>When</strong></td>
<td>ユーザーが実行するアクションを記述します。テスト対象の動作や操作を定義します。</td>
</tr>
<tr>
<td><strong>Then</strong></td>
<td>期待する結果や検証項目を記述します。アクション実行後の期待される状態を表現します。</td>
</tr>
<tr>
<td><strong>And</strong></td>
<td>前のステップと同じ種類のステップを追加する際に使用します。Given、When、Then の後に複数の条件や結果を記述できます。</td>
</tr>
</tbody>
</table>

### 良い例：振る舞いを意識したテスト

```gherkin
Feature: レジシステムでの商品購入
  As a 店員
  I can 商品をスキャンして合計金額を計算する
  so that 顧客が正確な金額を確認できる

  Scenario: 複数の商品を購入する
    Given レジシステムを起動する
    When "りんご" を "2" 個 スキャンする
    And "みかん" を "1" 個 スキャンする
    Then 合計金額は "400" 円 であること
```

#### 良い例の特徴

この例の特徴は次のとおりです。

- `As a 店員 / I can / so that` で明確にビジネス価値を表現
- 技術的詳細（配列操作、メソッド呼び出し、戻り値等）を一切含まない
- 実際の店員の行動と期待や提供価値を表現

### 悪い例：振る舞いを意識していないテスト

```gherkin
Feature: レジシステムでの商品購入

  Scenario: 複数の商品を購入する
    Given 配列$itemsを初期化する
    And 商品価格の連想配列$pricesを作成する
    When addItem("りんご", 2)メソッドを呼び出す
    And addItem("みかん", 1)メソッドを呼び出す
    Then 配列$itemsの要素数は2個であること
    And getTotal()メソッドの戻り値は400であること
```

#### 悪い例の問題点

この例の問題点は次のとおりです。

- 技術的詳細の過度な記述（配列、メソッド名、戻り値等）
- 実際の店員は「商品をスキャンして合計金額を確認する」のであり、配列の要素数やメソッドの戻り値を直接確認することはない
- 店員は「正確な合計金額が表示される」ことを期待するが、内部実装の詳細を意識しない

### 振る舞いを意識したテストの重要性

このように、同じ機能をテストする場合でも、振る舞いを意識したテストとそうでないテストでは、顧客の視点に立った価値の表現に大きな差が生まれます。BDD では、技術的な実装詳細（配列操作、メソッド呼び出し等）ではなく、顧客が求める具体的な価値（正確な合計金額の計算）に焦点を当ててテストを書くことが重要です。

## 理論から実践へ

レジシステムの例を使って、BDD を実践していきます。

1. 必要なツールをインストールして環境を構築する
2. Gherkin でシナリオを作成する
3. Behat を使ってステップ定義を実装する
4. テスト対象となるレジシステムのビジネスロジックを作成する
5. 実際にテストを実行して BDD の体験する

## PHPでBDDを実践する

### 必要なツール

PHP で BDD を実践するために、Behat、PHPUnit、Composer を使用します。Behat は PHP 用の BDD フレームワークで、PHPUnit はユニットテストフレームワーク、Composer はパッケージ管理ツールです。

### プロジェクトのセットアップ

まず、Composer でプロジェクトを初期化し、必要なパッケージをインストールします。

```bash
# 新しいディレクトリを作成
mkdir bdd-sample
cd bdd-sample

# プロジェクトの初期化（対話形式）
composer init
```

`composer init`を実行すると、いくつかの質問が表示されます。デフォルト値で進めても問題ありません。

プロジェクト名や説明を事前に決めている場合は、次のコマンドを使用できます。

```bash
composer init --name=your-name/bdd-sample --description="BDDサンプルプロジェクト" --type=project --require="php:>=8.0" --no-interaction
```

## 必要なパッケージのインストール

次に、BDD とテストに必要なパッケージをインストールします。

```bash
# BDDフレームワークのBehatを開発依存としてインストール
composer require --dev behat/behat

# ユニットテストフレームワークのPHPUnitを開発依存としてインストール
composer require --dev phpunit/phpunit
```

## Behatの初期化

Behat を初期化して、必要なディレクトリとファイルを作成します。

```bash
# Behatの初期化
vendor/bin/behat --init
```

このコマンドを実行すると、次のディレクトリとファイルが作成されます。

- `features/` - フィーチャーファイルを配置するディレクトリ
- `features/bootstrap/` - コンテキストクラスを配置するディレクトリ
- `features/bootstrap/FeatureContext.php` - デフォルトのコンテキストクラス

コンテキストクラスは、Gherkin で記述した自然言語のステップに対応する PHP のテストコードを定義するクラスです。

## オートローダーの設定

最後に、Composer のオートローダーを更新して、作成したクラスを読み込めるようにします。

```bash
# オートローダーの生成
composer dump-autoload
```

## サンプルコードの実装

それでは、実際に BDD のサンプルコードを実装していきましょう。

### フィーチャーファイルの作成

まず、`features/register.feature`ファイルを作成します。このファイルは、レジシステムの振る舞いを Gherkin 形式で記述したテスト仕様書です。

```gherkin
Feature: レジシステムでの商品購入
  As a 店員
  I can 商品をスキャンして合計金額を計算する
  so that 顧客が正確な金額を確認できる

  Scenario: 複数の商品を購入する
    Given レジシステムを起動する
    When "りんご" を "2" 個 スキャンする
    And "みかん" を "1" 個 スキャンする
    Then 合計金額は "400" 円 であること
```

### コンテキストクラスの実装

次に、`features/bootstrap/FeatureContext.php`ファイルを編集します。このファイルには、Gherkin で記述したステップを実行するテストコードを実装します。

#### コードの概要

次のコードは、Gherkin で記述したステップを実行するテストコードです。各メソッドには`@Given`、`@When`、`@Then`などのアノテーションが付いており、フィーチャーファイルのステップと対応しています。シンプルなレジシステムのクラスを使用して、商品の追加と合計計算の動作を検証します。

#### 各ステップの処理内容

| ステップ | テストの処理内容 |
|----------|----------|
| **Given レジシステムを起動する** | 新しいレジシステムのインスタンスを作成し、初期状態にします |
| **When 商品をスキャンする** | 指定された商品と数量をレジシステムに追加します |
| **Then 合計金額は X円 であること** | レジシステムの合計金額が期待する値と一致するかを検証します |

#### テストコード（FeatureContext）

Gherkin で記述したステップを実際のテストコードに変換したものです。

```php
<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Uutan1108\BddSample\RegisterSystem;

class FeatureContext implements Context
{
    private RegisterSystem $register;

    /**
     * @Given レジシステムを起動する
     */
    public function startRegisterSystem(): void
    {
        $this->register = new RegisterSystem();
    }

    /**
     * @When :item を :quantity 個 スキャンする
     */
    public function scanItem(string $item, int $quantity): void
    {
        $this->register->addItem($item, $quantity);
    }

    /**
     * @Then 合計金額は :amount 円 であること
     */
    public function assertTotalAmount(int $amount): void
    {
        $actualTotal = $this->register->getTotal();
        Assert::assertEquals($amount, $actualTotal, "合計金額が期待値と一致しません");
    }
}
```

### レジシステムクラスの実装

最後に、`src/RegisterSystem.php`ファイルを作成します。このクラスは、実際のビジネスロジックを実装したレジシステムです。これはテスト対象となる実装コードです。

#### コードの概要

このクラスは、商品の追加と合計金額の計算をするシンプルなレジシステムです。商品名と価格のマッピングを持ち、商品を追加すると自動的に合計金額が計算されます。

#### 主な機能

このレジシステムには次の機能が含まれている。

- `addItem()`メソッドを使うと、商品名と数量を指定して商品を追加できる
- `getTotal()`メソッドで、現在の商品の合計金額を取得できる
- 事前に定義された商品価格を基に、自動的に計算が実行される

```php
<?php

declare(strict_types=1);

namespace Uutan1108\BddSample;

use InvalidArgumentException;

/**
 * シンプルなレジシステムクラス
 */
class RegisterSystem
{
    private array $items = [];
    private array $prices = [
        'りんご' => 150,
        'みかん' => 100,
        'バナナ' => 200
    ];

    public function addItem(string $itemName, int $quantity): void
    {
        if (!isset($this->prices[$itemName])) {
            throw new InvalidArgumentException("商品 {$itemName} の価格が設定されていません");
        }

        // 既存の商品がある場合は数量を追加
        foreach ($this->items as &$item) {
            if ($item['name'] === $itemName) {
                $item['quantity'] += $quantity;
                return;
            }
        }

        // 新しい商品を追加
        $this->items[] = [
            'name' => $itemName,
            'quantity' => $quantity,
            'price' => $this->prices[$itemName]
        ];
    }

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
}
```

## ファイル構造

作成したファイルの構造は次のようになります。

```
bdd-sample/
├── composer.json
├── composer.lock
├── vendor/
├── features/
│   ├── register.feature
│   └── bootstrap/
│       └── FeatureContext.php
└── src/
    └── RegisterSystem.php
```

## 動作確認

作成したサンプルコードが正しく動作することを確認しましょう。

```bash
# Behatテストの実行
vendor/bin/behat
```

実行結果は次のとおりです。

```
Feature: レジシステムでの商品購入
  As a 店員
  I can 商品をスキャンして合計金額を計算する
  so that 顧客が正確な金額を確認できる

  Scenario: 複数の商品を購入する
    Given レジシステムを起動する
    When "りんご" を "2" 個 スキャンする
    And "みかん" を "1" 個 スキャンする
    Then 合計金額は "400" 円 であること

1 個のシナリオ (1 個成功)
4 個のステップ (4 個成功)
0m0.01s
```

すべてのステップが成功し、レジシステムが期待とおりに動作することが確認できました。

### テスト結果の解説

| ステップ | テストの処理内容 | 状態変化 |
|----------|----------|----------|
| **Given レジシステムを起動する** | 新しい`RegisterSystem`インスタンスが作成され、空の状態で初期化されます | 初期状態：商品0個、合計0円 |
| **When "りんご" を "2" 個 スキャンする** | りんご（150円）が2個追加されます | 商品1種類：りんご2個、小計300円 |
| **And "みかん" を "1" 個 スキャンする** | みかん（100円）が1個追加されます | 商品2種類：りんご2個＋みかん1個、合計400円 |
| **Then 合計金額は "400" 円 であること** | 期待値400円と実際の合計金額400円が一致するかを検証します | テスト成功：期待値と実際値が一致 |

このように、BDD では自然言語で記述したシナリオが、実際のコードの動作と一致することを確認できます。

## BDDの実践的なメリット

### チーム間のコミュニケーション向上

BDD を実践することで、開発者、テスト担当者、ビジネス関係者が共通言語でコミュニケーションを取ることができます。この共通言語は、Gherkin で記述された自然言語の「Given-When-Then」形式のシナリオです。

### 要件の明確化

これまで例示してきたように、Gherkin で記述した日本語シナリオと PHP のテストコードが分離されていることで、非技術者でもテストの意図を理解でき、開発者も実装すべき動作を明確に把握できます。

### 品質保証の自動化

自然言語でテストを書くことで、曖昧な要件が明確になり、実装前に問題点を発見できる。また、BDD で記述したテストは自動化されることで、リグレッションを防ぎ、継続的な品質保証を提供する。

### 生きたドキュメントとしての機能

さらに、BDD で記述したテストはシステムの動作を説明する生きたドキュメントとしても機能する。

## よくある落とし穴と対策

### 実装詳細の混入

BDD を実践する際には、いくつかの落とし穴があります。まず、テストに実装詳細（配列操作、メソッド呼び出し、戻り値等）が混入してしまう問題があります。

### 対策方法

これを防ぐためには、ユーザーの視点でテストを書き、技術的詳細を隠蔽することが重要です。

## まとめ

BDD は、単にフレームワークを使うことではありません。大切なのは、顧客が求める具体的な価値の「振る舞い」を考え、共有し、テストに落とし込むことです。

PHP で BDD を実践することで、顧客が求める価値に焦点を当てた開発、チーム間の共通言語の確立、継続的な品質保証、生きたドキュメントの作成が実現できる。

## サンプルコード

この章で説明したサンプルコードは、次の場所にあります。

https://github.com/OHMORIYUSUKE/php-bdd-sample-daigirin-2025-2

サンプルコードを試すには、README の指示にしたがってください。

## 参考資料

- Behat 公式ドキュメント：https://docs.behat.org/
- PHPUnit 公式ドキュメント：https://phpunit.de/
- PHP Conference Japan 2025：https://fortee.jp/phpcon-2025/proposal/15d8064d-4085-4cf9-ab44-6972f693577b
