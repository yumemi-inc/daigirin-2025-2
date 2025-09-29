---
class: content
---

<div class="doc-header">
  <div class="doc-title">Rustのパターンマッチってアートだヨネ</div>
  <div class="doc-author">namnium</div>
</div>

こんにちわ！Rust大好きなサーバーサイドエンジニアのnamniumと申します。

Rustのソースコードはしばしばアートのように振る舞います。その中でもRustの**パターンマッチ**は言語を代表する機能だけあって、綺麗に書けた時の美しさといえば筆舌に尽くしがたいです！今回はそんなRustパターンマッチの世界を皆様に紹介したく無理やり筆を握りました！

次の"アート"に違和感を持った方はぜひ本記事を読んでみてほしいです[^magia]！

[^magia]: 本記事のタイトルはマギアレコードに登場する某キャラのセリフ風に...まどドラハーフアニバーサリーおめでとうございます！

- `let Point { x, y } = p;`
- `let c @ 'A'..'z' = v else { return; };`
- `let () = {};`
- `let ((Some(n), _) | (_, n)) = (opt, default);`

読み終わる頃には美しく感じるはず！(多分 💦 )

> 本記事はQiitaに投稿した記事 [【Let chains実装記念】パターンマッチテクニック集【Rust】 (https://qiita.com/namn1125/items/4845aaa6ee19eaf8081b)](https://qiita.com/namn1125/items/4845aaa6ee19eaf8081b) をゆめみ大技林に寄稿するためにブラッシュアップした記事です ✨
>
> また紙媒体につきウェブ版から一部をオミットした形になっています。Webの方もぜひ見ていただけたら幸いです！
>
> Web版: (推敲完了後リンク挿入)
>
> 以上、あらかじめご了承ください 🙇

今年の6月、 Rust 1.88 がリリースされました 🥳 🥳 (ちなみに現在の最新は 1.90.0 です 😅 )

- [Announcing Rust 1.88.0 | Rust Blog (https://blog.rust-lang.org/2025/06/26/Rust-1.88.0/)](https://blog.rust-lang.org/2025/06/26/Rust-1.88.0/)
- [Rust 1.88を早めに深掘り - あずんひの日 (https://aznhe21.hatenablog.com/entry/2025/06/27/rust-1.88)](https://aznhe21.hatenablog.com/entry/2025/06/27/rust-1.88)

1.88では特に「 `if let` 式で `let` を `&&` で繋げられる」 Let chains というのが目玉機能として挙げられています！

```rust
fn jsons_json_in_json_article_checker(contents: &str) -> anyhow::Result<bool> {
    // if let 式
    if let Contents {
        author,
        content: Content::Other { body: json_str },
        ..
    } = serde_json::from_str(contents)?
    // 1.88からは、さらに条件をくっつけられる！
    && author == "Json"
    // 1.88からは、let もくっつけられる！
    && let JsonInJsonContent::Article { body } = serde_json::from_str(&json_str)? {
        println!("It's Json's Article: {}", body);
        Ok(true)
    } else {
        Ok(false)
    }
}
```

とても嬉しい機能が追加されたので、 [let-else文のノリで (https://qiita.com/namn1125/items/ccedf9cc06b8cef71557)](https://qiita.com/namn1125/items/ccedf9cc06b8cef71557) 記事を書こうかと最初は思ったのですが、素晴らしい [先達記事 (https://zenn.dev/msakuta/articles/rust-let-chains)](https://zenn.dev/msakuta/articles/rust-let-chains) がありましたので本記事では `if let` 式の**周辺**知識、という位置付けで**パターンマッチのテクニック集**を紹介したいと思います！

> 基礎事項についてはほぼ公式ドキュメントのまとめ直しです。
>
> 一次ソースに当たりたい方は以下も見てみてください！
>
> [パターンとマッチング - The Rust Programming Language 日本語版 (https://doc.rust-jp.rs/book-ja/ch18-00-patterns.html)](https://doc.rust-jp.rs/book-ja/ch18-00-patterns.html)

冒頭に挙げたような謎な書き方も、意味がわかると美しいアートに見えること請け合いです。それではRustパターンマッチの世界を見ていきましょう！

# Rustのパターンマッチ

`if let` 式の `let` 部分ではRustの**パターンマッチ**機能が利用できます。

本節ではそもそもパターンマッチってなんだっけ...？というところからおさらいしたいと思います。

---

基本としては「 `match` 式の左側に列挙するもの」という認識で大体あっています。

他の言語でもあるように列挙体で分岐する場合もあれば...

```rust
#[derive(Clone, Copy)]
enum Fruits {
    Apple,
    Banana,
    Orange,
}

impl Fruits {
    fn get_price(self) -> u32 {
        match self {
            Fruits::Apple => 200,
            Fruits::Banana => 100,
            Fruits::Orange => 250,
        }
    }
}
```

プリミティブ型[^primitive]の値で分岐する場合もあります。

[^primitive]: Rustの組み込み型のうち、 `Copy` トレイトが付与されている型(スタックに保存できる型)という認識で大体あっています。

```rust
let age = 10;

let s = match age {
    0 => "生まれたばかり",
    1 | 2 => "1, 2歳",
    3..=6 => "幼稚園",
    7..=15 => "義務教育",
    16..=18 => "未成年",
    _ => "成人",
};

println!("あなたの現在: {s}"); // 義務教育

let chr = '@';

match chr {
    'a' | 'A' => println!("エー"),
    c @ 'A'..'z' => println!("{c} はアルファベット"),
    c if c.is_ascii() => println!("{c} はアスキー範囲の文字"),
    c => println!("その他: {c}"),
}
```

もっと面白い例もあるのですがネタが尽きてしまうので記事の後半で...

この先 `match` 式に限らない色々な構文を紹介していく予定ですが、その前にパターンマッチおよび "パターン" について解説します！

## パターンマッチの役割: 「分解」「束縛」「合致判定」

パターンマッチには大体3つの役割があります。

まず2つは「**分解**」と「**束縛**」です。TSなどにもある「**分割代入**」と同じ機能ですね。

```rust
fn get_current_location() -> (f64, f64) { /* 省略 */ }

let (x, y) = get_current_location();
```

この `let` 文では、 `get_current_location` の返り値を `(x, y)` というタプルに**分解**しています。ついでに、 `x` , `y` という変数に**束縛**され、以降同スコープ内で変数 `x` と `y` が使えるようになっています。

分解はタプルの他にも構造体・列挙体・配列など色々な構文要素で可能です！

```rust
#[derive(Clone, Copy, Debug)]
struct Point {
    x: f64,
    y: f64,
}

fn main() {
    // 通常の変数宣言
    let p = Point { x: 0., y: 10. };

    let Point { x: a, y: b } = p; // 分解して変数 a, b に束縛
    println!("{a} {b}");

    // 変数名がフィールド名と同じで良い場合
    let Point { x, y } = p;

    println!("{x} {y}");
}
```

### {隙有場合合致|すきあらばパターンマッチ}

ここで逆転の発想として押さえておいてもらいたいのは、Rustで **「変数の束縛(あるいは変数宣言)ができるならその箇所はパターンマッチである」** という事実です。

以下は後述の論駁不可能パターンしか取れませんが全て「**パターンマッチチャンス** ✨✨」なのです！

```rust
#[derive(Clone, Copy, Debug)]
struct Point {
    x: f64,
    y: f64,
}

impl Point {
    fn dist(
        self,
        // 関数の引数でパターンマッチ！
        Point { x: x1, y: y1 }: Point,
    ) -> f64 {
        // もちろんletでパターンマッチ！
        let Point { x: x0, y: y0 } = self;

        ((x0 - x1).powi(2) + (y0 - y1).powi(2)).sqrt()
    }
}

fn main() {
    // ただの束縛 (でもパターンマッチの一つと見れる！)
    let points = [
        Point { x: 0., y: 0. },
        Point { x: 1., y: 2. },
        Point { x: 2., y: 3. },
        Point { x: 5., y: 0. },
        Point { x: 0., y: 0. },
    ];

    let mut total_dist = 0.;
    let mut pre_p = points[0];
    // for でもパターンマッチ！
    for &p @ Point { x, y } in &points[1..] {
        println!("now: ({x}, {y})");

        total_dist += p.dist(pre_p);

        // let がない時は流石に束縛(もとい再代入)しかできない
        pre_p = p;
    }
    
    let total_dist_1 = points[1..]
        .iter()
        .fold(
            (0., points[0]),
            // クロージャ引数でもパターンマッチ！
            |(total, pre_p), &p| (p.dist(pre_p) + total, p)
        ).0;

    println!("{total_dist}, {total_dist_1}");
    assert_eq!(total_dist, total_dist_1);
}
```

恣意的な例で少し冗長ですが、上記ソースコードでは以下のパターンマッチが登場しています。

- 関数引数でのパターンマッチ: `Point { x: x1, y: y1 }: Point`
- `let` 文でのパターンマッチ: `let Point { x: x0, y: y0 } = self;`
- `for` 文でのパターンマッチ: `for &p @ Point { x, y } in &points[1..] {...}`
- クロージャ引数でのパターンマッチ: `|(total, pre_p), &p| {...}`

本記事後半ではパターンマッチが使える事例を個別に一応紹介していこうと考えていますが、 **そもそも変数束縛ができる箇所は大体パターンマッチだ** という風に捉えておいた方が自然に見えるんじゃないかと思います。

ただし、以下に示す様な例外もあります。

- `let` がないミュータブル変数への再代入
- `static` / `const` による定数宣言

### 合致判定

最後の役割が名前通り**パターンにマッチするかどうかの検証**です。ただし、この役割は後述の「論駁可能パターン」のみが持ちます。

```rust
let mut v: Vec<usize> = (1..10).collect();

while let Some(n) = v.pop() { // パターンにマッチしている間のみ実行
    match n {
        m if m % 2 == 0 => println!("{m}は偶数"),
        m => println!("{m}は奇数"),
    }
}
```

ここで示した `while let` 文や `match` 式は論駁可能パターンによるパターンマッチで、論駁可能パターンの特権である「パターン合致検証」を行ない、合致する時のみスコープ内の文を実行しています。これらのパターンマッチも、前節で紹介した「分解」と「束縛」の役割も担っている点にも注目です。

#### まとめ: パターンマッチの役割

- 分解
- 束縛
- 合致判定

## 論駁可能パターンと論駁不可能パターン

参考: [論駁可能性: パターンが合致しないかどうか (https://doc.rust-jp.rs/book-ja/ch18-02-refutability.html)](https://doc.rust-jp.rs/book-ja/ch18-02-refutability.html)

ここまでの説明ですでに何度か顔を出していましたが、パターンマッチのパターンは、論駁(可能|不可能)パターンの2つに分類されます！

1. 論駁可能パターン ( refutable pattern )
1. 論駁不可能パターン ( irrefutable pattern )

### なぜ「論駁」？

論駁というのは「反論」と大体似た意味の言葉です。 `refutable` という英単語が割り当てられているのですが、それの直訳が「論駁」なので和訳ドキュメントは論駁なのだろうと思います。

...かっこいいからヨシ！

### 論駁可能パターン

**受け入れ・束縛に失敗することがある** パターンマッチのパターンを論駁可能なパターンといいます。 `match` 式のアームや `if let` 式に用いられるパターンは(基本的に[^match])論駁可能パターンです。

[^match]: `match` 式のデフォルトパターンは論駁不可能パターンですし、論駁不可能パターンが一つだけある `match` 式も書けます。ただ後者に関しては `let` 文で良いかなと思います。

```rust
#[derive(Clone, Copy, Debug)]
enum Fruits {
    Apple,
    Banana,
    Orange,
}

struct PurchaseResult {
    fruits: Option<Fruits>,
    change: u32,
}

impl Fruits {
    fn get_price(self) -> u32 {
        match self {
            // Appleじゃないこともある
            // よって論駁可能パターン
            Fruits::Apple => 200,
            
            // Bananaじゃないことも当然ある
            // 論駁可能パターン
            Fruits::Banana => 100,

            // Orangeに当てはまるとも限らない
            // 論駁可(ry
            Fruits::Orange => 250,
        } // ただしどれかでは絶対ある
    }

    fn try_purchase(self, payment: u32) -> PurchaseResult {
        let price = self.get_price();
        if payment >= price {
            PurchaseResult {
                fruits: Some(self),
                change: payment - price,
            }
        } else {
            PurchaseResult {
                fruits: None,
                change: payment
            }
        }
    }
}

fn main() {
    let res = Fruits::Banana.try_purchase(300);

    // 買えない時もあればお釣りが0円の時もあり、以下のパターンマッチは失敗する可能性がある
    // 論駁可能パターン
    if let PurchaseResult { fruits: Some(f), change: ch @ 1.. } = res {
        println!("{f:?} が購入できてお釣りは {ch} 円だった！");
    }
}
```

`Fruits::Apple` や `PurchaseResult { fruits: Some(f), change: ch @ 1.. }` は常に受け入れられる・束縛できるとは限らないので、全て論駁可能パターンというわけです。

`PurchaseResult {...}` の例を見るとわかる通り、論駁可能かどうかは**パターンの複雑さには特に関係なく**、純粋に **「常に受け入れ・束縛可能かどうか」** のみで決まります。

> 補足: 「束縛」ではなく「受け入れ・束縛」と書いたのは、 `Fruits::Apple` のように変数が一切生まれないパターンもあるためです。広義的にこれを束縛と捉えても良いかも...？

```rust
#[derive(Clone, Copy)]
struct Point {
    x: u32,
    y: u32,
}

fn main() {
    let point = Point { x: 10, y: 20 };

    // こちらは後述の論駁不可能パターン
    let Point { x, y } = point;

    println!("x = {}, y = {}", x, y);

    // xが常に0とは限らないので、論駁可能パターン
    // let Point { x: 0, y } = point;
    /* refutable pattern in local binding: `Point { x: 1_u32..=u32::MAX, .. }` not covered
       `let` bindings require an "irrefutable pattern", ...
       と怒られる
    */
    // if letやmatchなら論駁可能パターンを取れる
    if let Point { x: 0, y } = point {
        println!("x = 0, y = {}", y);
    }
}
```

### 論駁不可能パターン

**どんな場面でも絶対に受け入れ・束縛に成功する** パターンマッチを論駁不可能なパターンと言います。

通常の変数束縛はある意味で絶対成功するパターンマッチです。その他にも、構造体の分解なども必ず成功するため論駁不可能パターンとして利用可能です。

通常の `let` 文が取れるパターンは論駁不可能パターンである必要があります。逆に言えば **論駁不可能なら意外になんでも書くことができます**。以下例です。

```rust
// 普通の変数宣言
let p = Point { x: 0., y: 0. };

// 分解構文用途
let Point { x, y } = p;

// 配列もいけます
let [a, b, c, ..] = [0, 1, 2, 3, 4];

// 以下は処理としてはnop
let 0..=255_u8 = 10; // u8 の変数は必ず0から255
let () = {}; // ユニット値は当然ユニット値に束縛できる
```

# パターンマッチ全体のテクニック

ここまででパターンマッチで必ず押さえておかなければならない基礎事項もとい直感については説明できたと思います。ここからは、いよいよパターンマッチのテクニック集を見せていきたいと思います！

## ✨テクニック1✨ `_` や `..` で値を無視する

分解構文的にパターンマッチを見た際、利用しないフィールドがある時もあるでしょう。 `_` や `..` はそんな時に利用できたりします！

```rust
#[derive(Clone, Copy, Debug)]
struct Point {
    x: f64,
    y: f64,
}

impl Point {
    fn dist_of_x(
        self,
        Point { x: x1, y: _ }: Point,
    ) -> f64 {
        let Point { x: x0, y: _ } = self;

        (x0 - x1).abs()
    }
}
```

上記の例では、 `y` の値は使用しないため `_` としています。

`..` を使えば、「以降のフィールドを無視」といったことも可能です。たくさんフィールドがある際などに便利です。

```rust
struct Settings {
    interval: Duration,
    repeat_num: usize,
    hensuumei: String,
    kanngaeruno: bool,
    mendoukusakunatta: char,
}

let Settings {
    interval,
    repeat_num,
    ..
} = s;
```

### `_` と `_x` の違い

変数の接頭辞に `_` をつけることで未使用変数として扱えます。

実はこれは `let _ = ...` の `_` とは挙動が少し違います。

- `_x`: **実際に束縛は行われる**、すなわち**所有権が奪われたり**する
- `_`: **束縛すら行われない**。所有権も奪われない

`_` は特別な記法として覚えておいて損はないでしょう。

## ✨テクニック2✨ `|` でパターン連結(し論駁不可能にする)

`|` を使うことで複数パターンを結合することができます。`match` 式のアームでよく使える記法です！

```rust
let c = 'a';

match c {
    'a' | 'A' => println!("a か A"), // 'a' と 'A' のパターンを結合
    c => println!("{c}"),
}
```

ところで、 `let` 文は論駁不可能なパターンしか用いることができないのでした。しかし、 **論駁不可能なら文句なし** なわけです。というわけで、実は次のソースコードは問題ありません！

```rust
fn hoge(opt: Option<usize>, default: usize) {
    let ((Some(n), _) | (_, n)) = (opt, default);

    // ↑↓ 処理的には同じ

    let n = opt.unwrap_or(default);

    println!("{n}");
}
```

`Some(n)` みたいなものを利用して束縛を行う場合 `if let` 式や後述の `let else` 文が必要になりそうなものですが、次の2点を守っていれば論駁不可能なので例の様な `let` 文が書けます！

- `|` でくっつけたパターンのどれかには該当するか
- どのパターンに該当したとしても、同じ型の同じ変数(例では `n: usize` )が同じだけ用意されること

## ✨テクニック3✨ 整数型と `char` 型は `..` / `..=` で範囲指定できる

Rustでは `1..10` で1以上10未満、 `1..=10` で1以上10以下、といった具合に範囲型を作ることができます。

実はパターンマッチにおいて、整数型と `char` 型(文字型のこと)のみこの記法を用いたパターンを書くことが可能です！

```rust
let chr = 'Z';

match chr {
    'A'..'z' => println!("アルファベット"),
    c => println!("その他: {c}"),
}
```

ちなみに全パターンを網羅(exhaustive)する必要がある `match` 式のアームに使った場合、この記法を使ってもしっかりと網羅性チェックは行われるので安心です 👍

使用頻度は高くないと思いますが、知っておいて損はないでしょう。

## ✨テクニック4✨ `@` で値を束縛しつつパターン検証

「ある値があるパターンにマッチするかを調べたい」、でも、「元の値を利用したい」、そんな時に利用できるのが `@` です！

```rust
let age = 15;

match age {
    ..13 => println!("子供"),
    n @ 13..=19 => println!("{n} 歳はティーンエイジャー"),
    n => println!("{n} 歳は大人"),
}
```

上記では範囲指定でのみ使っていますが、列挙型を深いネストと共にパターンマッチさせる場合などにも便利だったりします。

```rust
enum Gender {
    Male,
    Female
}

struct Person {
    first_name: String,
    last_name: String,
    gender: Gender,
}

use Gender::*;

impl Person {
    fn greet(self) {
        match self {
            // パターンマッチで男性であることを確かめつつ、 p に全体を入れる
            p @ Person { gender: Male, .. } => println!("Hello, Mr. {}", p.name()),
            p @ Person { gender: Female, .. } => println!("Hello, Ms. {}", p.name()),
        }
    }
    
    fn name(&self) -> String {
        format!("{} {}", self.first_name, self.last_name)
    }
}
```

## ✨テクニック5✨ `ref`, `ref mut`, `&`

`ref`, `ref mut`, `&` を利用することで、参照化/参照外しが可能になります！

- `ref` / `ref mut`: 変数の束縛時に、 `ref` なら不変参照、 `ref mut` なら可変参照とする
- `&`: 評価される値が参照の時、参照を外した値にする ( `Copy` トレイト付与型を基本に捉えておいた方がよい)

`ref` や `ref mut` は分割代入時に変数を参照化するのに使うと便利です。

```rust
fn count(mut self) -> Self {
    let Counter {
        ref mut counter,
        ref diff, 
    } = self;
        
    *counter += *diff;
    
    self
}
```

とはいえ「`ref` / `ref mut` でないと絶対に実現できない処理」というのに筆者は出会ったことがなく、普通にRustを書いている分には必要としない機能かもしれません。

`&` の方はクロージャの引数でしばしば目にすることがあります。

```rust
fn main() {
    let v: Vec<usize> = (1..=10).collect();

    // iter だと v: &usize なので &v と書いて参照外し
    let s: usize = v.iter().map(|&v| v * v).sum();
    
    println!("{} = {s}",
        v.iter()
            .map(|i| format!("{i}*{i}"))
            .collect::<Vec<_>>()
            .join(" + ")
    );
}
```

イメージとしては `&10` みたいな値がある時、 `&` と `10` に分解して `v` の方に `10` を束縛している感じでしょうかね...？

# 論駁不可能パターンが使える構文集

記事冒頭で「分割代入のあるところにパターンマッチあり」と紹介しました。とはいえ、結局具体的にどのような構文が使えるかの言及はしていなかったので、記事の残りではパターンマッチを使える構文集を論駁不可能・可能に分けて紹介していきたいと思います。

数が少ない & 基本的なものであるという理由から論駁不可能パターンからです。

## `let` 文

```rust
let 論駁不可能パターン = 式;
```

もう何度も登場したお馴染みの束縛構文です。もはや説明不要！

```rust
let Point { x, y } = p;
```

良い機会なので「ちなみに」として書きますが、 `let` には変数宣言だけを行う機能もあります。

```rust
fn func(flag: bool) -> usize {
    let (val, _): (usize, usize);

    // val には一度だけ代入可能
    if flag {
        val = 10;
    } else {
        val = 20;
    }

    val * val
}

struct Point {
    x: u64,
    y: u64,
}

fn gunc() {
    let Point {
        x,
        y
    };

    x = 10;
    y = 10;

    println!("({}, {})", x, y);
}
```

例で書いた様に、パターンを利用して変数宣言を書くと非常にシュールですね... 😓

## 関数・クロージャの引数

```rust
fn 関数名(論駁不可能パターン: 型) -> 返り値型 {...}

|論駁不可能パターン| -> 返り値型 {...}
```

先に挙げた例の通り、関数やクロージャの引数部分でパターンマッチを行うことができます！

```rust
#[derive(Clone, Copy, Debug)]
struct Point {
    x: f64,
    y: f64,
}

impl Point {
    fn dist(
        self,
        // 関数の引数でパターンマッチ！
        Point { x: x1, y: y1 }: Point,
    ) -> f64 {
        // もちろんletでパターンマッチ！
        let Point { x: x0, y: y0 } = self;

        ((x0 - x1).powi(2) + (y0 - y1).powi(2)).sqrt()
    }
}
```

クロージャはともかく、関数引数部分での実用的な使い方例としては、Rustでオプショナル引数的なものを用意したくなった際、引数用構造体があると捗るのですが、その構造体を分解したい時に多少可読性が上がるかもという感じです。(未使用変数がわかるため)

```rust
struct FuncInput {
    arg1: usize,
    arg2: u32,
    arg3: u64,
    arg4: u128,
    arg5: i32,
    arg6: i64,
    arg7: i128,
}

impl Default for FuncInput {
    fn default() -> Self {
        Self {
            arg1: 10,
            // arg2 以降略
        }
    }
}

// ここで分解構文！
fn func(FuncInput {
    arg1,
    arg2,
    arg3,
    arg4,
    arg5,
    arg6,
    arg7,
}: FuncInput) {
    // 略
}

fn main() {
    // 引数が多い関数は引数用構造体を用意してDefaultをimplさせておくのが吉
    func(Default::default());
}
```

クロージャの方はワンライナーにするためによく見かける気がします。

```rust
let total_dist_1 = points[1..]
    .iter()
    .fold(
        (0., points[0]),
        // クロージャ引数でパターンマッチ
        |(total, pre_p), &p| (p.dist(pre_p) + total, p)
    ).0;
```

## `for` 文

```rust
for 論駁不可能パターン in イテレータ {...}
```

`for xxx in vvv {...}` の `xxx` 部分にパターンを書けます！

```rust
struct Point {
    x: usize,
    y: usize,
}

fn main() {
    let points = [
        Point { x: 0, y: 0 },
        Point { x: 1, y: 1 },
    ];
    
    for Point { x, y } in points {
        println!("x: {x} y: {y}");
    }
}
```

それだけなのですけども、 `let` 同様便利ですね。

特にイテレータに対して `enumerate` を呼んだ時などには、クロージャにしろ `for` 文にしろインデックスと値についてタプルでの受け取りが発生してしばしば書くんじゃないかと思います！

# 論駁可能パターンが使える構文集

残りは、論駁可能パターンを使う構文、すなわち条件分岐的な要素が入ってくる構文集です！論駁可能パターンを受け取る構文、不可能よりも結構ありますね 👀

## `match` 式

```rust
match 式 {
    論駁(可能|不可能)パターン => マッチした時の枝,
}
```

いわずもがな。パターンマッチといえば `match` 式、 `match` 式といえばパターンマッチですね。まとめていて気付きましたが、論駁可能パターン、不可能パターンの両方を活用する構文は地味にこの `match` 式ぐらいかもしれません。

`match` アームは「(同じ型のパターンであるなら)どんな形のアームでも良い(論駁可能・不可能か問わないしタプルや構造体でも良い)」わけですが、そのことを説明するたびに `match` 式を用いたRust版fizzbuzzをいつも連想するので、本記事でついでに紹介しておきたいと思います！

```rust
#[derive(Debug)]
enum FizzBuzzValue {
    FizzBuzz,
    Fizz,
    Buzz,
    Other(usize)
}

use FizzBuzzValue::*;

fn fizz_buzz(n: usize) -> FizzBuzzValue {
    match (n % 3, n % 5) {
        (0, 0) => FizzBuzz,
        (0, _) => Fizz,
        (_, 0) => Buzz,
        _ => Other(n)
    }
}
```

「15で割る → 5で割る → 3で割る」という順で確認する、というのがよくやる手だと思います。

一方で、上記のように `(3で割ったあまり, 5で割ったあまり)` のタプルでパターンマッチすることで、 `3 * 5 = 15` みたいなワンクッションを置かなくても自然言語でのFizzBuzzのルールをそのままに条件分岐に落としこむことができています。

FizzBuzzだと実感が沸きにくいですが、直感的なパターンマッチを書きたい時、 `match` 式は強い味方になってくれるというわけです！

### 網羅性 (exhaustive)

`match` 式は論駁可能/不可能パターン両方を枝のパターンマッチに用いることができると書きました。

そんな `match` 固有の機能がいくつかあります。「**パターンの網羅性(exhaustive)をチェックしてくれる**」はその一つでしょう。

網羅性チェッカーは、 `match` 式のすべての枝をかき集めたときに論駁不可能であることを確認してくれます！

次のコードはコンパイルエラーになりません。

```rust
let n: u8 = 5;
let m: u8 = 5;

let (b1, b2) = match (n, m) {
    (0..=128, 0..=128) => (false, false),
    (0..=128, 129..=255) => (false, true),
    (129..=255, 0..=128) => (true, false),
    (129..=255, 129..=255) => (true, true),
};
```

一方で少しでも網羅性が崩れる(論駁可能である)とコンパイルエラーになります！

```rust
let n: u8 = 5;
let m: u8 = 5;

let (b1, b2) = match (n, m) {
    (0..=128, 0..=128) => (false, false),
    (0..=128, 129..=255) => (false, true),
    (129..=255, 10..=128) => (true, false), // 129..=255, 0..=9 の時が抜けている
    (129..=255, 129..=255) => (true, true),
};
```

```plaintext
error[E0004]: non-exhaustive patterns: `(129_u8..=u8::MAX, 0_u8..=9_u8)` not covered
 --> src/main.rs:5:26
  |
5 |     let (b1, b2) = match (n, m) {
  |                          ^^^^^^ pattern `(129_u8..=u8::MAX, 0_u8..=9_u8)` not covered
  |
  = note: the matched value is of type `(u8, u8)`
```

この網羅性チェッカーはかなり優秀なので、 `match` 式は背中を預けるような心持ちで使えるというわけです！

### if ガード

もう一つ、 `match` 式に固有の機能として **ifガード** があります。こちらは、パターンの後に `if` 式に似た何かを記載できる機能です。

他言語に寄せて普通にfizzbuzzを書く例がわかりやすいでしょう。

```rust
fn fizz_buzz(n: usize) -> FizzBuzzValue {
    match n {
        n if n % 15 == 0 => FizzBuzz,
        n if n % 3 == 0 => Fizz,
        n if n % 5 == 0 => Buzz,
        _ => Other(n)
    }
}
```

`if else if else ...` を避けられるほか、「他言語の `switch` 文だと書ける[^otherswitch]のにRustの `match` 式だと書けない(書きにくい)！」みたいなこともこのifガードのおかげで起きないんじゃないかなと思います。

[^otherswitch]: 他言語の `switch` 文のポテンシャルを失念したので、すぐにはよい例が思いつかないですが...

一つ注意点としては、当たり前といえばそうですが、ifガードの中身までは網羅性の確認が行き届かない点です。

以下の書き方では、網羅的なはずですがコンパイルエラーになります。網羅性の担保にはそのほかの論駁不可能パターン等が必要になるでしょう。

```rust
fn fizz_buzz(n: usize) -> FizzBuzzValue {
    match n {
        n if n % 15 == 0 => FizzBuzz,
        n if n % 3 == 0 => Fizz,
        n if n % 5 == 0 => Buzz,
        n if n % 3 != 0 && n % 5 != 0 => Other(n),
    }
}
```

```plaintext
error[E0004]: non-exhaustive patterns: `0_usize..` not covered
  --> src/main.rs:12:11
   |
12 |     match n {
   |           ^ pattern `0_usize..` not covered
   |
   = note: the matched value is of type `usize`
   = note: match arms with guards don't count towards exhaustivity
help: ensure that all possible cases are being handled by adding a match arm with a wildcard pattern or an explicit pattern as shown
   |
16 ~         n if n % 3 != 0 && n % 5 != 0 => Other(n),
17 ~         0_usize.. => todo!(),
   |
```

## `if let` 式

```rust
if let 論駁可能パターン = 式 { /* パターンに一致時実行 */ }

// または

if let 論駁可能パターン = 式 { /* パターンに一致時実行 */ } else { /* パターンに合致しない時実行 */ }
```

Rust 1.88 の let chains 機能追加に関連して、 `if let` は冒頭のソースコードから顔を出してきました！

```rust
fn jsons_json_in_json_article_checker(contents: &str) -> anyhow::Result<bool> {
    // if let 式
    if let Contents {
        author,
        content: Content::Other { body: json_str },
        ..
    } = serde_json::from_str(contents)?
    // 1.88からは、さらに条件をくっつけられる！
    && author == "Json"
    // 1.88からは、let もくっつけられる！
    && let JsonInJsonContent::Article { body } = serde_json::from_str(&json_str)? {
        println!("It's Json's Article: {}", body);
        Ok(true)
    } else {
        Ok(false)
    }
}
```

`if let` 式それ自体は「論駁可能パターンに合致していたら真の節を実行し、(もし枝があれば)合致していないときは `else` 節を実行する」という、ある意味で論駁可能パターンによるパターンマッチを最もよく説明してくれる構文です。特殊ながら扱いやすい感がありますね！

よく `if let Some(n) = ... {}` という形で `Option` 型の値で分岐する例ばかり見ますが、ここまでの解説からわかる通り論駁可能パターンならば何でも大丈夫です！

```rust
// 普通はこうは書かないかもですが...
if let 4 = n {
    println!("ミスタ「4はやめてくれ...」");
}
```

ちなみに論駁不可能パターンも指定すること自体はできるのですが、警告が出ます。

```rust
if let m = n {
    println!("{m}");
}
```

```plaintext
warning: irrefutable `if let` pattern
 --> src/main.rs:4:8
  |
4 |     if let m = n {
  |        ^^^^^^^^^
  |
  = note: this pattern will always match, so the `if let` is useless
  = help: consider replacing the `if let` with a `let`
  = note: `#[warn(irrefutable_let_patterns)]` on by default
```

### Let chains

Rust 1.88 記念で書いた記事なので一応もちろん(？)Let chainsに言及しておきます。

- [2497-if-let-chains - The Rust RFC Book (https://rust-lang.github.io/rfcs/2497-if-let-chains.html)](https://rust-lang.github.io/rfcs/2497-if-let-chains.html)

パターンや真偽値を `&&` で繋げられるようになったのがLet chainsです！

```rust
if let Some(m) = n {
    if m % 2 == 0 {
        println!("{m} が存在していて偶数だった");
    }
}
```

今までは、 `if let` 式でマッチした後にさらにその中身で条件分岐する場合、上記の通り `if` をネストするしかありませんでした。

1.88 からはLet Chainsが追加されたので、これを一つの `if let` 式の中で書けるようになりました！

```rust
if let Some(m) = n
    && m % 2 == 0
{
    println!("{m} が存在していて偶数だった");
}
```

#### `||` (or) は使えないことに注意

Let chains は見た目の上でこそ条件式を `&&` でつなぐように書きますが、真偽値を結ぶ `&&` と全く同じかと聞かれるとそうではないので、「同じ `if` の条件節部分で連続してパターンマッチできる」以上のことは期待しないほうがよさそうでしょう。

そのことを一番実感できる例として、 `if let` 式では `||` (or) を使ったパターンマッチの連結はできないことが挙げられます。

```rust
enum Mode {
    Mode1(usize),
    Mode2(usize),
    Other
}

use Mode::*;

if let Mode1(n) = m || let Mode2(n) = m {
    println!("Mode1 or Mode2 called with {}.", n);
}
```

```plaintext
error: `||` operators are not supported in let chain conditions
  --> src/main.rs:12:25
   |
12 |     if let Mode1(n) = m || let Mode2(n) = m {
   |                         ^^
```

そもそも論理値演算とは関係なく、パターン同士は `|` で連結できるのでした

```rust
enum Mode {
    Mode1(usize),
    Mode2(usize),
    Other
}

use Mode::*;

if let Mode1(n) | Mode2(n) = m {
    println!("Mode1 or Mode2 called with {}.", n);
}
```

Let chains の `&&` は見た目的にわかりやすいから使われているのであって、論理値演算ではないことを覚えておきましょう。

## `while let` 文

```rust
while let 論駁可能パターン = 式 {...}
```

筆者が地味に好きな構文です。「パターンにマッチする間だけ」繰り返したい時に使えます。

次はmpscでの活用例です。 `rx.recv()` が `Ok` の間、すなわちチャネル間の通信が有効でまだ有効な値が `rx` にやってくる間、処理されます。

```rust
use std::sync::mpsc::channel;

fn main() {
    let (tx, rx) = channel();
    
    std::thread::spawn(move || {
        for n in 0..10 {
            tx.send(n).unwrap();
        }
    });
    
    // 値を取り出せている間だけ繰り返し
    while let Ok(n) = rx.recv() {
        println!("{n}");
    }
}
```

ただ実は `std::sync::mpsc::channel::Receiver` は `IntoIterator` を実装しているので、わざわざ `while let` を利用しなくても `for` 文で同じ処理を書けちゃったりします😅

```rust
use std::sync::mpsc::channel;

fn main() {
    let (tx, rx) = channel();
    
    std::thread::spawn(move || {
        for n in 0..10 {
            tx.send(n).unwrap();
        }
    });
    
    // 値を取り出せている間だけ繰り返し
    for n in rx {
        println!("{n}");
    }
}
```

`tokio::sync::mpsc::Receiver` あたりは `IntoIterator` を実装していないので、こちらを利用する場合は `while let` が引き続き効果的かもしれません。

### `while let` が輝く例: ダイクストラ法

`while let` を使うことで、「`tokio::sync::mpsc::Receiver` で有効値が返ってくる間だけループ」みたいなことができると紹介しました。

それ以外にも、値の取り出し元がループ内で変化する場合などはもっと `while let` が輝くシーンだったりします。

その代表例にダイクストラ法 [^dijkstra]があります。アルゴリズムの詳細はここでは省略しますが、次のようなソースコードになります！

[^dijkstra]: <https://ja.wikipedia.org/wiki/%E3%83%80%E3%82%A4%E3%82%AF%E3%82%B9%E3%83%88%E3%83%A9%E6%B3%95>

```rust
fn dijkstra(graph: &Graph, start: Node) -> HashMap<Node, Cost> {
    let mut resolved: HashMap<Node, Cost> = HashMap::new();
    let mut priority_queue: BinaryHeap<Move> = BinaryHeap::new();

    // スタート地点を追加
    priority_queue.push(Move {
        to: start,
        total_cost: 0,
    });

    // ヒープ木から最小コストで到達可能なノードを探索
    while let Some(Move {
        to: current_node,
        total_cost,
    }) = priority_queue.pop()
    {
        // 既に到達済みならスキップ
        if resolved.contains_key(&current_node) {
            continue;
        }

        // 最短距離で到達したノードを記録
        resolved.insert(current_node, total_cost);

        // 全ノードに到達していたら終了
        if resolved.len() >= graph.len() {
            break;
        }

        // 隣接する頂点への移動候補を追加
        if let Some(edges) = graph.get(&current_node) {
            for &Edge { to, cost } in edges {
                if !resolved.contains_key(&to) {
                    priority_queue.push(Move {
                        to,
                        total_cost: total_cost + cost,
                    });
                }
            }
        }
    }

    resolved
}
```

ダイクストラ法では、「優先度付きキュー (ヒープ木) から移動候補が取り出せるうちはループ」という処理が必要です。一方で、優先度付きキューにはループ内にて動的に移動候補が追加されていきます。「 `IntoIterator` 等でイテレータにして `for` で回す」みたいに単純には書けないため、ダイクストラ法みたいなアルゴリズムを記述する際は `while let` が活躍するわけです！

`while let` でダイクストラ法を記述するのが好きなため、本記事に例として載せさせていただきました！

## `let else` 文

```rust
let 論駁可能パターン = 式 else {
    never型を返す処理
};
```

構文として最後に紹介するのは `let else` 文です！ `let else` 文も比較的新しい構文で、1.65にて追加されました。

論駁可能パターンに合致する時はパターン内の変数に値を束縛します。もし合致しない場合は、束縛を諦め `else` 節を実行します。 `else` 節はnever型( `!` )を返す(最後に発散する)処理のみしか書けないため、論駁可能パターンの束縛が失敗した際も矛盾なく記述できます。

```rust
for i in 1..=15 {
    let v = fizz_buzz(i);

    let FizzBuzzValue::Other(j) = v else {
        continue; // never 型の処理
    };

    println!("{j}");
}

// ↑ を if let 式で書くと ↓

for i in 1..=15 {
    let v = fizz_buzz(i);

    let j = if let FizzBuzzValue::Other(k) = v {
        k
    } else {
        continue; // never 型の処理
    };

    println!("{j}");
}
```

本記事のここまでを踏まえて一言で言えば、「**論駁可能パターン用の `let` 文**」というポジションなので、ある意味で `let` 文や `if let` 並にプリミティブな構文と言えるかもしれません。

ところが、 `if let` を利用する機会が多い `Option` 型や `Result` 型には `let else` を利用するより便利なメソッドが用意されていたり、 `else` 節には `return` や `continue`, `panic!(...)` といった発散する(never型の)処理しか書けないという使い勝手の悪さが影響して、なかなか利用機会が少ない構文だったりします。

マクロを書くために `syn` クレートを使う際などは結構お世話になるのですがね...ベストな使いどころを見つけたらスマートに使ってみたいというロマン構文です✨

> 以前書いた記事の方が詳しいのでそちらもぜひご一読いただけると幸いです！
>
> [Rustのlet-else文気持ち良すぎだろ #Rust - Qiita (https://qiita.com/namn1125/items/ccedf9cc06b8cef71557)](https://qiita.com/namn1125/items/ccedf9cc06b8cef71557)

## `matches!` マクロ

最後におまけで、パターンマッチを利用できるマクロ `matches!` を紹介します！

```rust
matches!(式, パターン)
```

このマクロは次に展開されるシンプルなものです↓

```rust
match 式 {
    パターン => true,
    _ => false
}
```

「パターンマッチに合致するかどうか」だけを返すメソッド等を定義する際に、可読性を向上させてくれるでしょう！

```rust
#[derive(Debug)]
enum FizzBuzzValue {
    FizzBuzz,
    Fizz,
    Buzz,
    Other(usize)
}

impl FizzBuzzValue {
    fn is_not_other(&self) -> bool {
        !matches!(self, Self::Other(_))
    }
}
```

# まとめ

パターンマッチの諸々を紹介してきました！ `let () = {};` や `let (val, _): (usize, usize);` みたいな普段絶対書かないような変な例をいろいろ出してきましたが、むしろこの変な例のおかげでRustのパターンマッチチャンス✨✨は至る所に潜んでいることを示せたのではないでしょうか...？

本記事を通してパターンマッチを強い味方に付けていただけたならば幸いです！💪

ここまで読んでいただきありがとうございました！🙇🙇

# おまけ: Rust 1.88とhooqクレート

ここからはQiita掲載時には載せなかったおまけという名の宣伝を載せたいと思います！

筆者的には、実はRust 1.88にアップデートされて一番嬉しかったのはLet chains**ではなく**、**`proc_macro::Span::line` メソッド等の安定化**でした！というお話です。

## hooqクレート

業務でのRust利用を通じ、Rustは `?` 位置でのトレースを得られにくいことに気がついた筆者は、 `?` 位置に自動的に `Result` 型(や `Option` 型)のメソッドを挿入してくれるマクロを作ろうと思い立ちました。

そこで半年ほどかけて作成したのが以下のURLで示しているhooqクレートです！

- [anotherhollow1125/hooq: The simple macro that inserts a method before `?`. (`?` 前にメソッドを挿入するシンプルなマクロ) https://github.com/anotherhollow1125/hooq](https://github.com/anotherhollow1125/hooq)

hooqクレートができる前は、どの位置にある `?` でエラーが発生したかは、anyhowクレートの `with_context` メソッドを挟んだり `std::backtrace::Backtrace` をエラーに含めるなどをしなければわかりませんでした。

以下例です。なお、 `.get(...)` は `Option` 型を返すメソッドですが `with_context` のおかげで `Result` 型を返すようになっています。

```rust
use anyhow::Context;
use anyhow::Result;

fn display_description(val: &toml::Value) -> Result<()> {
    let description = val
        .get("package")
        .with_context(|| format!("package not found. (L{})", line!()))?
        .get("description")
        .with_context(|| format!("description not found. (L{})", line!()))?
        .as_str()
        .with_context(|| format!(".as_str() return None. (L{})", line!()))?;

    println!("description: {description}");

    Ok(())
}

fn main() -> Result<()> {
    let cargo_toml = toml::from_str(&std::fs::read_to_string("Cargo.toml")?)?;

    display_description(&cargo_toml)?;

    Ok(())
}
```

```bash
$ cargo run -q
Error: description not found. (L9)
```

どの行でエラーが発生したかはエラー解析において重要な情報でしょう。しかし**本筋とは無縁なのに毎回 `.with_context(|| format!(".as_str() return None. (L{})", line!()))?` などと書くのははっきり言って苦痛**です。そこで、hooqの出番です！属性風マクロでこの記述を省略できます！

```rust
use anyhow::Context;
use anyhow::Result;
use hooq::hooq;

#[hooq(anyhow)]
fn display_description(val: &toml::Value) -> Result<()> {
    let description = val.get("package")?.get("description")?.as_str()?;

    println!("description: {description}");

    Ok(())
}

#[hooq(anyhow)]
fn main() -> Result<()> {
    let cargo_toml = toml::from_str(&std::fs::read_to_string("Cargo.toml")?)?;

    display_description(&cargo_toml)?;

    Ok(())
}
```

```bash
$ cargo run -q
Error: [main.rs:L18] ...iption(& cargo_toml)

Caused by:
    [main.rs:L7] ... .get("description")
```

どの `?` でエラーが起きたかは相変わらず詳細に得られる上に、プログラムの本筋はとても読みやすくなったと思います！

## 1.88 `proc_macro::Span::line` 安定化のおかげでstableに

hooq実装中にあった大きな課題に、「フック対象の `?` が存在する行の取得が難しい」というものがありました。

`with_context` を利用した例では、 `line!()` マクロを利用していましたが、なんと **`line!()` マクロは属性風マクロ内で呼ぶとマクロ付与位置を指し続ける** という性質があり、今回の目的では全く使い物になりません！

属性風マクロにおいて付与対象構文の行数を得るには、どうしても `proc_macro::Span::line` の情報にアクセスする必要がありました。

hooqの開発を始めた当初はこのメソッドはnightlyでないと利用できず、コンパイラの都合上nightlyビルドは利用者側のクレートでも必要になるという辛い現状がありました...

が、 🥳🥳 **1.88でこのAPIが安定化しました** 🥳🥳！

おかげさまで(MSRVは1.88になってしまいましたが)hooqは全ての機能をstableで供給できるようになりました。Let chainsが霞むレベルで嬉しかったです！

...そんなわけで、気になった方はぜひhooqを使ってみてください！以上、hooqの宣伝記事でした！(ｵｲ
