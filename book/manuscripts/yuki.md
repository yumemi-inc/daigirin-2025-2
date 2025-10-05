---
class: content
---

<div class="doc-header">
  <div class="doc-title">Reactエンジニア向けFlutter入門</div>
  <div class="doc-author">ふぁ（@faa0311）</div>
</div>

# Reactエンジニア向けFlutter入門

## はじめに

はじめまして、ゆめみ25卒のふぁと申します。
業務では主にFlutter開発を行っていますが、趣味でReactを触っているのでその知見をもとに
Reactエンジニア向けFlutter入門を執筆しました。

本章では、ReactとFlutterの共通点や違いを比較しながら解説します。
普段Reactを使っている方がFlutterに興味を持っていただけたらと思います。
また、ReactやFlutterの経験がなくても理解できるように構成しています。

## そもそもFlutterとは

FlutterとはUIフレームワークで、Dart言語によって記述します。
DartはもともとJavaScriptの代替を目指して設計された静的型付け言語ですが、現在ではFlutterを中心にクロスプラットフォーム開発で広く使われています。
そのためJavaScriptと構文が似ており、JavaScriptの不便な部分が改良されています。

デメリットでもメリットでもありますが、DartとFlutterはどちらもGoogleによって開発されています。

ReactはDOMやブラウザの描画エンジンを前提としていますが、FlutterはSkia/Impellerという描画エンジンで直接レンダリングします。そのためプラットフォームに依存しない一貫したUIを提供できるのが大きな特徴です。
Web向けビルドではデフォルトでCanvasKit（SkiaベースのWebAssemblyレンダラーでキャンバスに描画される）が使われますが、軽量なHTMLレンダラーも選択可能です。

また、FlutterもReactと同じくHot Reloadを備えており、状態を保持したままコードの変更を即座に反映できます。これにより開発効率が大幅に向上します。

## ローカルステート管理

まずはサンプルアプリとしておなじみの「カウンターアプリ」を例にします。
Reactでは宣言的なUIと単方向データフローを使って、シンプルかつ直感的にステート管理を記述できます。

```jsx
import { useState } from "react";

const Counter = () => {
  const [count, setCount] = useState(0);
  return (
    <button onClick={() => setCount((c) => c + 1)}>
      count: {count}
    </button>
  );
}
```

Reactでは関数コンポーネントと`useState`を組み合わせて状態を扱いますが、Flutterでは`StatefulWidget`クラスと`setState`を使って同じことを実現します。
書き方は異なりますが「状態を変更するとUIが更新される」という考え方は同じです。

```dart
import 'package:flutter/material.dart';

class Counter extends StatefulWidget {
  const Counter({super.key});
  @override
  State<Counter> createState() => _CounterState();
}

class _CounterState extends State<Counter> {
  int count = 0;
  @override
  Widget build(BuildContext context) {
    return ElevatedButton(
      onPressed: () => setState(() => count++),
      child: Text('count: $count'),
    );
  }
}
```

Reactに比べると記述がやや冗長に感じられるかもしれません。次に紹介する、`flutter_hooks`というステート管理ライブラリを利用すればReactに近くなります。
これはReact Hooksに着想を得たライブラリで、`useState`や`useEffect`などReactに馴染みのある書き方でFlutterのステート管理を記述できます。

```dart
import 'package:flutter/material.dart';
import 'package:flutter_hooks/flutter_hooks.dart';

class Counter extends HookWidget {
  const Counter({super.key});

  @override
  Widget build(BuildContext context) {
    final count = useState(0);
    return TextButton(
      onPressed: () => count.value++,
      child: Text('count: ${count.value}'),
    );
  }
}
```

このように、`flutter_hooks`を使うとReactに近い感覚でステート管理が可能になります。
Reactの`useState`は配列を返しますが、Flutterの`useState`は`ValueNotifier`オブジェクトを返します。
`ValueNotifier`の`value`プロパティに代入すると自動的に再レンダリングが発生します。
Reactでは「関数の実行」で状態の更新を通知しますが、Flutterでは「オブジェクトのプロパティ更新」で状態の更新を通知します。

## グローバルステート管理

ローカルステートは小規模なアプリには便利ですが、画面をまたいでデータを共有するには限界があります。
そこで登場するのがグローバルステート管理で、Flutterでは`Riverpod`がデファクトスタンダードです。
`Riverpod`はRecoilやJotaiのように「小さな状態を組み合わせる」設計思想を持っています。
違いとして、`Riverpod`はDartの型安全性やコード生成を活用でき、依存関係の追跡や非同期処理をより明示的に扱えるのが特徴です。

まずはReact（Jotai + Valibot）でGitHub APIを呼び出すサンプルを見てみましょう。
`atomFamily`で「ユーザー名ごと」の状態を作り、`loadable`で非同期状態を安全に扱います。

```jsx
import { atom, useAtomValue } from 'jotai';
import { atomFamily, loadable } from 'jotai/utils';
import * as v from 'valibot';

// オブジェクトを定義する
const UserSchema = v.object({
  avatar_url: v.string(),
});

// グローバルステート管理を定義する
const githubUserAtomFamily = atomFamily((username: string) =>
  atom(async () => {
    const res = await fetch(`https://api.github.com/users/${username}`);
    if (!res.ok) throw new Error('Failed to fetch user');
    const data = await res.json();
    return v.parse(UserSchema, data);
  })
);

const githubUserLoadable = (username: string) =>
  loadable(githubUserAtomFamily(username));

// コンポーネントを定義する
export const GithubUser = () => {
  const userLd = useAtomValue(githubUserLoadable('octocat'));
  if (userLd.state === 'loading') return <p>loading...</p>;
  if (userLd.state === 'hasError') return <p>error: {(userLd.error as Error).message}</p>;
  return <img src={userLd.data.avatar_url} alt="avatar" />;
};
```

このコードでは、まず`Valibot`でAPIレスポンスの型を定義しています。
次に、`atom`を組み合わせてユーザーごとに独立した状態を作り、さらに`loadable`で非同期処理の進行状態を管理しています。
コンポーネント側では`userLd.state`に応じてローディング・エラー・成功を分岐し、結果を描画します。

これをFlutterで記述してみます。

```dart
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:http/http.dart' as http;
import 'package:riverpod_annotation/riverpod_annotation.dart';

part 'main.freezed.dart';
part 'main.g.dart';

// オブジェクトを定義する
@freezed
abstract class User with _$User {
  @JsonSerializable(fieldRename: FieldRename.snake)
  const factory User({required String avatarUrl}) = _User;
  factory User.fromJson(Map<String, Object?> json) => _$UserFromJson(json);
}

// グローバルステート管理を定義する
@riverpod
Future<User> getGithubUser(Ref ref, String username) async {
  final url = Uri.parse('https://api.github.com/users/$username');
  final response = await http.get(url);
  return User.fromJson(jsonDecode(response.body));
}

// ウィジェット(コンポーネント)を定義する
class GithubUser extends ConsumerWidget {
  const GithubUser({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(getGithubUserProvider('octocat'));
    return switch (user) {
      AsyncData(:final value) => Image.network(value.avatarUrl),
      AsyncError(:final error) => Text('error: $error'),
      AsyncLoading() => const Text('loading...'),
    };
  }
}
```

このコードでは`Freezed`で型安全なデータクラスを定義しています。
`Freezed`はデータクラスを自動生成するライブラリで、型安全なステート管理やシリアライズを簡単に実現できます。
次に、`Riverpod`を使用しユーザーごとに独立した状態(Provider)を作っています。
UI側では`ref.watch`を使ってProviderを監視し、Dart 3から導入された`switch`式で状態を網羅的に分岐しています。
これにより「型で保証された非同期処理のハンドリング」が可能になり、Reactの`loadable`と同じような記述をより堅牢に実現できます。

Dart言語はTypeScriptのような柔軟な型システム、マクロ、メタクラスを持ちませんが、その代わりにコード生成を活用する文化があります。
この例では`_$User`、`_User`、`_$UserFromJson`、`getGithubUserProvider`が自動生成されています。
`Freezed`や`Riverpod`のようなライブラリはこの文化を前提に発展しています。アノテーションを付けるだけで膨大なボイラープレートコードを自動生成でき、強力で安全なプログラミングが可能になります。

## レイアウト構築の思想

ReactではDOM要素にCSS（Flexboxなど）を適用してレイアウトを調整します。
これに対してFlutterでは、HTMLやCSSを直接使うのではなく、`Row`や`Column`といった専用のウィジェットを組み合わせて「レイアウトそのものをコードとして表現する」スタイルを取ります。

React（Tailwind CSS）で簡単なレイアウトの例を見てみましょう。

```jsx
<div className="flex gap-2">
  <span>Hello</span>
  <button>Click</button>
</div>
```

このコードでは、`div`に`flex`と`gap-2`のクラスを付け、Flexboxレイアウトでテキストとボタンを横並びに配置しています。
CSSがレイアウトの役割を担い、HTML要素はあくまで入れ物として使われています。

```dart
Row(
  spacing: 8,
  mainAxisAlignment: MainAxisAlignment.center,
  children: [
    const Text('Hello'),
    ElevatedButton(onPressed: () {}, child: const Text('Click')),
  ],
);
```

Flutterでは同じことを`Row`ウィジェットで表現します。
`spacing: 8`が`gap-2`に相当し、`children`にテキストとボタンを並べています。
スタイルをCSSに委ねるのではなく、レイアウトも含めてすべてコード（Widgetツリー）で記述します。

TailwindのようなユーティリティCSSに比べるとFlutterの記述はやや冗長に見えます。
ただし、スタイルもレイアウトもWidgetとして一元管理できるため、ツリー構造を見ればUIの構成をそのまま理解できるという利点があります。

## まとめ

ここまで見てきたように、FlutterはReactと多くの共通点を持ちながら、型とコード生成を活かした開発体験など独自の強みもあります。
Reactの経験があれば、コンポーネントベースのUI構築やステート管理といった基礎概念をそのまま活かせます。
本書はあくまで入り口です。
次のステップとしては、公式ドキュメントやサンプルアプリのコードリーディングがおすすめです。Flutterは最初の学習コストこそありますが、その分、良い開発体験が得られます。

本書がFlutterを学ぶきっかけになればうれしいです。

<hr class="page-break"/>

## 署名

ちょっとした遊び心です。理論上は検証できます。

```txt
sed -n '/^# Reactエンジニア向けFlutter入門$/,/^## 署名$/{/^## 署名$/d;p;}' yuki.md > body.md
pandoc body.md -f markdown -t plain --strip-comments | tr -d '[:space:]' > normalize.txt 
gpg --armor --detach-sign --yes normalize.txt
sed -i.bak '/^```key[[:space:]]*$/,$d' yuki.md
printf '```key\n%s\n```\n' "$(cat normalize.txt.asc)" >> yuki.md
```

```txt
PGP Fingerprint: EAB5 AF5A A7ED 7A16 C402  7B52 83A8 A5E7 4872 A8AA
```

```key
-----BEGIN PGP SIGNATURE-----

iHUEABYKAB0WIQTqta9ap+16FsQCe1KDqKXnSHKoqgUCaN+adQAKCRCDqKXnSHKo
qrpAAP9yS+BhiJQWfrpIsj7si+5N8P7n/OKTbH5C25eRXDBk5wD9HiPHvBpNNFoR
fwoHuq2536llKZnsbCpM434wEZBEngA=
=oGfY
-----END PGP SIGNATURE-----
```
