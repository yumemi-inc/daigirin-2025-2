---
class: content
---

<div class="doc-header">
  <div class="doc-title">Reactエンジニア向けFlutter入門</div>
  <div class="doc-author">ふぁ</div>
</div>
---

# Reactエンジニア向けFlutter入門

## はじめに

はじめまして、ゆめみ25卒のふぁと申します。
業務では主にFlutter開発を行っていますが、趣味でReactを触っているのでその知見をもとに
Reactエンジニア向けFlutter入門を執筆しました。

本書では、ReactとFlutterの共通点や違いを比較しながら解説します。
普段Reactを使っている方がFlutterに興味を持って頂けたらと思います。
また、ReactもFlutterも触れたことのない方で理解できるように構成しています。

## そもそもFlutterとは

FlutterとはUIフレームワークでDart言語によって記述します。
DartはもともとJavaScriptの代替を目指して設計された静的型付け言語ですが、現在ではFlutterを中心にクロスプラットフォーム開発で広く使われています。
そのため構文が似ており、JavaScriptの不便な部分が改良されています。

デメリットでもメリットでもありますがどちらもGoogleによって開発されています。

ReactはDOMやブラウザの描画エンジンを前提としていますが、FlutterはSkiaという描画エンジンで直接レンダリングします。そのためプラットフォームに依存しない一貫したUIを提供できるのが大きな特徴です。
Web向けビルドではデフォルトでCanvasKit（SkiaベースのWebAssemblyレンダラーでキャンバスに描画される）が使われますが、軽量なHTMLレンダラーも選択可能です。

また、FlutterもReactと同じくHot Reloadを備えており、状態を保持したままコードの変更を即座に反映できます。これにより開発効率が大幅に向上します。

## ローカルステート管理

まずはサンプルアプリとしてはおなじみの「カウンターアプリ」を例にします。
Reactでは宣言的なUIと単方向データフローを使って、シンプルかつ直感的に状態管理を記述できます。

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

Reactでは関数コンポーネントとuseStateを組み合わせて状態を扱いますが、FlutterではStatefulWidgetクラスとsetStateを使って同じことを実現します。
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

Reactに比べると記述がやや冗長に感じられるかもしれません。次に紹介する、`flutter_hooks`という状態管理ライブラリを利用すればReactに近くなります。
これはReact Hooksに着想を得たライブラリで、useStateやuseEffectなどReactに馴染みのある書き方でFlutterの状態管理を記述できます。

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
`useEffect`なども存在し、基本的にはReactと同じように使えます。
ReactのuseStateは配列を返しますが、FlutterのuseStateはValueNotifierオブジェクトを返します。
ValueNotifierオブジェクトのvalueプロパティに代入すると自動的に再レンダリングが発生します。
つまり「関数の実行」ではなく「オブジェクトのプロパティ更新」で状態の更新を通知します。

## グローバルステート管理

ローカルステートは小規模なアプリには便利ですが、画面をまたいでデータを共有するには限界があります。
そこで登場するのがグローバルステート管理で、FlutterではRiverpodがデファクトスタンダードです。
RiverpodはRecoilやJotaiのように「小さな状態を組み合わせる」設計思想を持っています。
違いとして、RiverpodはDartの型安全性やコード生成を活用でき、依存関係の追跡や非同期処理をより明示的に扱えるのが特徴です。
また、非同期処理や依存性注入もサポートしており、UIロジックからビジネスロジックを分離しやすいという点でも共通しています。

まずはReact（Jotai + Valibot）でGitHub APIを呼び出すサンプルを見てみましょう。
atomFamilyで「ユーザー名ごと」の状態を作り、loadableで非同期状態を安全に扱います。

```jsx
import { atom, useAtomValue } from 'jotai';
import { atomFamily, loadable } from 'jotai/utils';
import * as v from 'valibot';

// オブジェクトを定義する
const UserSchema = v.object({
  avatar_url: v.string(),
});

// グローバル状態管理を定義する
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
このコードでは、まずValibotでAPIレスポンスの型を定義しています。
そのうえで、atomFamilyを使ってユーザーごとに独立した状態を作り、さらにloadableで非同期処理の進行状態を管理しています。
コンポーネント側では userLd.state に応じてローディング・エラー・成功を分岐し、結果を描画します。


これをFlutterで記述してみます。
Freezedはデータクラスを自動生成するライブラリで、型安全な状態管理やシリアライズを簡単に実現できます。

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

// グローバル状態管理を定義する
@riverpod
Future<User> getGithubUser(Ref ref, String username) async {
  final url = Uri.parse('https://api.github.com/users/$username');
  final response = await http.get(url);
  return User.fromJson(jsonDecode(response.body));
}

// コンポーネントを定義する
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

このコードではFreezedで型安全なデータクラスを定義し、@riverpodアノテーションでGitHubユーザー取得用の非同期Providerを宣言しています。
UI側ではref.watchを使ってProviderを監視し、Dart 3から導入されたswitch式で状態を網羅的に分岐しています。
これにより「型で保証された非同期処理のハンドリング」が可能になり、Reactのloadableと同じような記述をより堅牢に実現できます。

Dart言語は高度な型システムやマクロ、メタクラスを持ちませんが、その代わりにコード生成を活用する文化があります。
FreezedやRiverpodのようなライブラリはこの文化を前提に発展しています。アノテーションを付けるだけで膨大なボイラープレートコードを自動生成でき、強力で安全なプログラミングが可能になります。

## レイアウト構築の思想

ReactではDOM要素にCSS（Flexboxなど）を適用してレイアウトを調整します。
これに対してFlutterでは、HTMLやCSSを直接使うのではなく、RowやColumnといった専用のウィジェットを組み合わせて「レイアウトそのものをコードとして表現する」スタイルを取ります

```jsx
<div className="flex gap-2">
  <span>Hello</span>
  <button>Click</button>
</div>
```

このコードでは、divに flex と gap-2 のクラスを付け、Flexboxレイアウトでテキストとボタンを横並びに配置しています。
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
Flutterでは同じことをRowウィジェットで表現します。
spacing: 8 が gap-2 に相当し、children にテキストとボタンを並べています。
スタイルをCSSに委ねるのではなく、レイアウトも含めてすべてコード（Widgetツリー）で記述します。

TailwindのようなユーティリティCSSに比べるとFlutterの記述はやや冗長に見えます。
ただし、スタイルもレイアウトもWidgetとして一元管理できるため、ツリー構造を見ればUIの構成をそのまま理解できるという利点があります。

## まとめ

ここまで見てきたように、FlutterはReactと多くの共通点を持ちつつ、独自の思想や強みも備えています。
Reactの経験があれば、コンポーネントベースのUI構築やステート管理といった基礎概念をそのまま活かせます。
一方で、すべてをウィジェットとして表現する思想や、型とコード生成を前提としたエコシステムはFlutterならではの特徴です。
本書で紹介したのは、あくまで入り口にすぎません。
実際に手を動かしてカウンターアプリやAPI呼び出しを実装してみると、Reactとの違いや共通点がより鮮明に見えてくるはずです。
まずは簡単なUIから始め、少しずつ状態管理やレイアウト構築に挑戦してみてください。
次のステップとしては、公式ドキュメントやサンプルアプリのコードリーディングがおすすめです。
Flutterは学習コストこそありますが、その分、型安全で再現性の高い開発体験を得られるフレームワークです。
本書がFlutterを学ぶきっかけになれば幸いです。