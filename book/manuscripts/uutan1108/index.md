---
class: content
---

<div class="doc-header">
  <div class="doc-title">PHP で始める振る舞い駆動開発（Behaviour-Driven Development）</div>
  <div class="doc-author">うーたん（@uutan1108）</div>
</div>

# PHP で始める振る舞い駆動開発（Behaviour-Driven Development）

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

### 良い例：振る舞いを意識したテスト

Gherkin は、BDD で使用するシナリオ記述フォーマットです。次のコードは、ウェブサイトの検索機能の振る舞いを記述しています。Feature、Scenario、Given、When、Then などのキーワードを使用して、ユーザーの行動と期待する結果を自然な日本語で表現しています。

| キーワード | 説明 |
|------------|------|
| **Feature** | テスト対象の機能や特徴を定義します。この例では「ウェブサイトでの情報検索」という機能を表しています。 |
| **Scenario** | 具体的なテストケースを定義します。ユーザーがどのような状況で何を行うかを記述します。 |
| **Given** | テストの前提条件を記述します。テストを実行する前の状態や環境を設定します。 |
| **When** | ユーザーが実行するアクションを記述します。テスト対象の動作や操作を定義します。 |
| **Then** | 期待する結果や検証項目を記述します。アクション実行後の期待される状態を表現します。 |
| **And** | 前のステップと同じ種類のステップを追加する際に使用します。Given、When、Then の後に複数の条件や結果を記述できます。 |

```gherkin
Feature: ウェブサイトでの情報検索
  As a 学習者
  I can ウェブサイトでキーワード検索を実行する
  so that 必要な技術情報を素早く見つけて学習効率を向上させる

Scenario: 興味のある技術について検索する
  Given ウェブサイトのトップページを開く
  When "PHP" というキーワードで検索する
  Then 検索結果に "PHP" が含まれていること
  And 学習者が求める情報が表示されること
```

この例の特徴は次のとおりです。

- `As a 学習者 / I can / so that` で明確にビジネス価値を表現
- 技術的詳細（URL、HTTP ステータス、DOM 等）を一切含まない
- 実際のユーザーの行動と期待や提供価値を表現

### 悪い例：振る舞いを意識していないテスト

```gherkin
Feature: ウェブサイトでの情報検索
Scenario: 興味のある技術について検索する
  Given データベースに接続する
  And SQLクエリを準備する
  When localhost:8080/search?q=PHPにアクセスする
  And search.phpが実行される
  Then HTTPステータス200が返る
  And DOM要素".results"が1個存在する
```

この例の問題点は次のとおりです。

- 技術的詳細の過度な記述
- 実際のユーザーは「検索ボックスに PHP と入力して検索ボタンを押す」のであり、URL に直接アクセスすることはない
- ユーザーは「正常に動作する」ことを期待するが、DOM 構造を意識しない

このように、同じ機能をテストする場合でも、振る舞いを意識したテストとそうでないテストでは、顧客の視点に立った価値の表現に大きな差が生まれます。BDD では、技術的な実装詳細ではなく、顧客が求める具体的な価値に焦点を当ててテストを書くことが重要です。

## PHPでBDDを実践する

### 必要なツール

PHP で BDD を実践するために、Behat、PHPUnit、Composer を使用します。Behat は PHP 用の BDD フレームワークで、PHPUnit はユニットテストフレームワーク、Composer はパッケージ管理ツールです。

### プロジェクトのセットアップ

まず、Composer でプロジェクトを初期化し、必要なパッケージをインストールします。

```bash
# プロジェクトの初期化
composer init

# BDDフレームワークのBehatを開発依存としてインストール
composer require --dev behat/behat

# ユニットテストフレームワークのPHPUnitを開発依存としてインストール
composer require --dev phpunit/phpunit
```

### フィーチャーファイルの作成

`features/search.feature`ファイルを作成します。このファイルは、検索機能の振る舞いを Gherkin 形式で記述したテスト仕様書です。

```gherkin
Feature: ウェブサイトでの情報検索
  As a 学習者
  I can ウェブサイトでキーワード検索を実行する
  so that 必要な技術情報を素早く見つけて学習効率を向上させる

Scenario: 興味のある技術について検索する
  Given ウェブサイトのトップページを開く
  When "PHP" というキーワードで検索する
  Then 検索結果に "PHP" が含まれていること
  And 学習者が求める情報が表示されること

Scenario: 存在しないキーワードで検索する
  Given ウェブサイトのトップページを開く
  When "存在しない技術" というキーワードで検索する
  Then "検索結果が見つかりませんでした" というメッセージが表示されること
```

### コンテキストクラスの実装

`features/bootstrap/FeatureContext.php`ファイルを作成します。

次のコードは、Gherkin で記述したステップを実際のテストコードに変換したものです。各メソッドには`@Given`、`@When`、`@Then`などのアノテーションが付いており、フィーチャーファイルのステップと対応しています。Selenium WebDriver を使用してブラウザの操作を自動化し、PHPUnit のアサーション機能で期待する結果を検証します。

各ステップで実行される技術的な処理の詳細は次のとおりです。

| ステップ | 技術的な処理内容 |
|----------|------------------|
| **Given ウェブサイトのトップページを開く** | Selenium WebDriverでChromeブラウザを起動し、指定されたURL（http://app:80）にアクセスします。環境変数からSeleniumサーバーのホストとポートを取得し、RemoteWebDriverインスタンスを作成します。 |
| **When "PHP" というキーワードで検索する** | ページ内のname属性が"query"の入力フィールドを検索し、指定されたキーワード（"PHP"）を入力してから、フォームを送信します。`WebDriverBy::name()`を使用して要素を特定し、`sendKeys()`でテキスト入力、submit()でフォーム送信を実行します。 |
| **Then 検索結果に "PHP" が含まれていること** | クラス名が"search-results"の要素を検索し、その要素内のテキスト内容を取得します。取得したテキストに指定されたキーワード（"PHP"）が含まれているかをPHPUnitのassertStringContainsString()で検証します。 |
| **Then 学習者が求める情報が表示されること** | 検索結果エリア（クラス名"search-results"）の存在確認と、結果アイテム（クラス名"result-item"）の存在確認を行います。`assertNotNull()`で要素の存在を、assertGreaterThan()で結果件数が0より大きいことを検証します。 |
| **Then 適切なメッセージが表示されること** | エラーメッセージ要素（クラス名"no-results-message"）の存在を確認します。assertNotNull()を使用して要素が表示されているかを検証します。 |

実際の PHP コードは次のとおりです。

```php
<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use PHPUnit\Framework\Assert;

class FeatureContext extends MinkContext implements Context
{
    private ?RemoteWebDriver $driver = null;

    /**
     * @Given ウェブサイトのトップページを開く
     */
    public function openHomePage(): void
    {
        try {
            $host = getenv('SELENIUM_HOST') ?: 'localhost';
            $port = getenv('SELENIUM_PORT') ?: '4444';
            $serverUrl = "http://{$host}:{$port}";
            $this->driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::chrome());
            $this->driver->get('http://app:80');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @When :keyword というキーワードで検索する
     */
    public function searchWithKeyword(string $keyword): void
    {
        try {
            $searchInput = $this->driver->findElement(WebDriverBy::name('query'));
            $searchInput->sendKeys($keyword);
            $searchInput->submit();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @Then 検索結果に :keyword が含まれていること
     */
    public function assertSearchResultsContain(string $keyword): void
    {
        try {
            // 検索結果エリアから検索
            $searchResultsDiv = $this->driver->findElement(WebDriverBy::className('search-results'));
            $content = $searchResultsDiv->getText();
            Assert::assertStringContainsString($keyword, $content);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @Then 学習者が求める情報が表示されること
     */
    public function assertLearnerInformationIsDisplayed(): void
    {
        try {
            // 検索結果エリアが存在することを確認
            $searchResultsDiv = $this->driver->findElement(WebDriverBy::className('search-results'));
            Assert::assertNotNull($searchResultsDiv, '検索結果エリアが表示されていません');
            
            // 結果アイテムが存在することを確認
            $resultItems = $this->driver->findElements(WebDriverBy::className('result-item'));
            Assert::assertGreaterThan(0, count($resultItems), '検索結果が表示されていません');
        } catch (\Exception $e) {
            throw new \Exception('学習者に有用な情報が表示されていません: ' . $e->getMessage());
        }
    }

    /**
     * @Then :message というメッセージが表示されること
     */
    public function assertMessageIsDisplayed(string $message): void
    {
        try {
            $errorMessageElement = $this->driver->findElement(WebDriverBy::className('no-results-message'));
            Assert::assertNotNull($errorMessageElement, '適切なエラーメッセージが表示されていません');
        } catch (\Exception $e) {
            throw new \Exception('適切なメッセージの確認でエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * @AfterScenario
     */
    public function tearDown(): void
    {
        if ($this->driver) {
            $this->driver->quit();
        }
    }
}
```

## BDDの実践的なメリット

BDD を実践することで、開発者、テスト担当者、ビジネス関係者が同じ言語でコミュニケーションを取ることができるようになります。これまで例示してきたように、Gherkin で記述した日本語シナリオと PHP のテストコードが分離されていることで、非技術者でもテストの意図を理解でき、開発者も実装すべき動作を明確に把握できます。自然言語でテストを書くことで、曖昧な要件が明確になり、実装前に問題点を発見できるようになります。また、BDD で記述したテストは自動化されることで、リグレッションを防ぎ、継続的な品質保証を提供します。さらに、BDD で記述したテストはシステムの動作を説明する生きたドキュメントとしても機能します。

## よくある落とし穴と対策

BDD を実践する際には、いくつかの落とし穴があります。まず、テストに実装詳細（URL、HTTP ステータス、DOM 要素等）が混入してしまう問題があります。これを防ぐためには、ユーザーの視点でテストを書き、技術的詳細を隠蔽することが重要です。

## まとめ

BDD は、単にフレームワークを使うことではありません。大切なのは、「振る舞い」を考え、共有し、テストに落とし込むことです。

PHP で BDD を実践することで、顧客が求める価値に焦点を当てた開発、チーム間の共通言語の確立、継続的な品質保証、生きたドキュメントの作成が実現できるでしょう。

## 参考資料

- Behat 公式ドキュメント：https://docs.behat.org/
- PHPUnit 公式ドキュメント：https://phpunit.de/
- PHP Conference Japan 2025：https://fortee.jp/phpcon-2025/proposal/15d8064d-4085-4cf9-ab44-6972f693577b
