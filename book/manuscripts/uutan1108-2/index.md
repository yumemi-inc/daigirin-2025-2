---
class: content
---

<div class="doc-header">
  <div class="doc-title">WebエンジニアがSwiftをブラウザで動かすプレイグラウンドを作ってみた</div>
  <div class="doc-author">うーたん（@uutan1108）</div>
</div>

# WebエンジニアがSwiftをブラウザで動かすプレイグラウンドを作ってみた

## はじめに

Web エンジニアとして日々 Web のバックエンドを担当している中で、Swift という言語に興味を持ちました。Web が好きなので、Web をきっかけに Swift を学ぼうと思い、SwiftWasm を使ってオンラインプレイグラウンドを開発してみました。

## WebAssembly（WASM）とは

WebAssembly は、Web ブラウザ上で実行できるバイナリ形式のコードです。従来の JavaScript と比較して、ネイティブに近い実行速度を実現します。C++、Rust、Swift などの言語で書かれたコードを WebAssembly のバイナリに変換してブラウザ上で実行できます。

## WASI（WebAssembly System Interface）の役割

WASI は、WebAssembly が OS の機能にアクセスするための標準インタフェースです。従来の WebAssembly はブラウザ環境に限定されていましたが、WASI によりファイルシステムへのアクセス、ネットワーク通信、プロセス管理などのシステムレベルの操作が可能になります。

このプレイグラウンドでは、Swiftコードがファイル入出力やネットワーク処理を行う際に、WASIを通じて適切なシステムリソースにアクセスできるようになっています。これにより、ブラウザ環境でありながら、ネイティブアプリケーションに近い機能を提供できます。

実際の実装では、WASIの標準関数をTypeScriptで模倣しています。たとえば、`fd_write`関数では標準出力（`print()`）の処理を行い、`proc_exit`ではプログラムの終了処理、`random_get`では乱数生成に対応しています。これらの関数は、WebAssemblyモジュールがシステムレベルの操作を実行しようとした際に、ブラウザ環境で適切に処理されるようになっています。

WASMファイルの実行時には、まず`/api/wasm/${wasmId}`エンドポイントからWASMバイナリを取得し、`WebAssembly.compile`と`WebAssembly.instantiate`を使用してモジュールを初期化します。その後、WASIのインポート関数を設定し、WebAssemblyモジュールの`_start`関数を呼び出してSwiftプログラムの実行を開始します。

WASIは、この実行時点でSwiftコードがシステムレベルの操作（標準出力、ファイル操作、乱数生成など）を実行しようとした際に使用されます。たとえば、Swiftコードで`print("Hello")`を実行すると、WASMモジュールはWASIの`fd_write`関数を呼び出し、これがTypeScriptで実装された関数によって処理されてブラウザのコンソールに表示される仕組みになっています。

実際のWASI実装では、以下のような関数が定義されています：

```typescript
export function createWasiImports(setOutput: SetOutputFunction) {
  return {
    wasi_snapshot_preview1: {
      fd_write: (fd: number, iovs: number, iovsLen: number, nwritten: number) => {
        if (fd === 1 || fd === 2) { // stdout or stderr
          // 標準出力の処理
          const str = new TextDecoder().decode(buffer.slice(strPtr, strPtr + strLen));
          setOutput(prev => prev + str);
        }
        return 0;
      },
      proc_exit: (code: number) => {
        // プログラム終了の処理
        setOutput(prev => prev + `\nプロセスが終了コード ${code} で終了しました\n`);
      },
      random_get: (buf: number, bufLen: number) => {
        // 乱数生成の処理
        crypto.getRandomValues(buffer);
        return 0;
      }
    }
  };
}
```

この実装により、Swiftコードの`print()`文や`exit()`関数、乱数生成などが、ブラウザ環境で適切に処理されるようになっています。

## WASMモジュールとWASIの連携

WASMモジュールの実行時には、WASIの関数がインポートとして渡されます。以下のコードは、WASMファイルを実行し、WASI関数と連携する流れを示しています：

```typescript
async function executeWasmById(wasmId: string, setOutput: SetOutputFunction) {
  try {
    // WASMファイルを取得
    const response = await fetch(`/api/wasm/${wasmId}`);
    const wasmBuffer = await response.arrayBuffer();
    
    // WASIのインポート関数を作成
    const wasiImports = createWasiImports(setOutput);
    
    // WASMモジュールをコンパイル・インスタンス化
    const wasmModule = await WebAssembly.compile(wasmBuffer);
    const wasmInstance = await WebAssembly.instantiate(wasmModule, {
      wasi_snapshot_preview1: wasiImports.wasi_snapshot_preview1
    });
    
    // WASIのインスタンスを設定
    wasiImports.setInstance(wasmInstance.instance);
    
    // Swiftプログラムのエントリーポイントを実行
    const startFunction = wasmInstance.instance.exports._start as Function;
    startFunction();
    
  } catch (error) {
    setOutput(`エラー: ${error.message}`);
  }
}
```

このコードから、WASMとWASIの関係性が明確になります：

1. **WASMファイルの取得**: `/api/wasm/${wasmId}`からコンパイル済みのWASMバイナリを取得
2. **WASI関数の準備**: `createWasiImports`でWASIの標準関数を実装
3. **WASMモジュールの初期化**: `WebAssembly.compile`と`WebAssembly.instantiate`でWASMモジュールを作成
4. **WASI関数の注入**: インスタンス化時にWASI関数をインポートとして渡す
5. **実行開始**: `_start`関数を呼び出してSwiftプログラムの実行を開始

Swiftコード内で`print("Hello")`を実行すると、WASMモジュールはWASIの`fd_write`関数を呼び出します。この関数は、ブラウザ環境で標準出力を模倣し、結果を`setOutput`関数を通じてUIに表示します。

## SwiftWasmの技術的仕組み

SwiftWasmは、Swift言語で書かれたコードをWebAssemblyにコンパイルするためのツールチェーンです。従来のSwiftコンパイラは、LLVMバックエンドを使用してネイティブコードを生成していましたが、SwiftWasmではLLVMのWebAssemblyターゲットを活用して、SwiftコードをWASMバイナリに変換します。

コンパイルプロセスは以下のような流れで進行します。まず、SwiftのソースコードがSwiftコンパイラによってLLVM IR（Intermediate Representation）に変換されます。次に、LLVMのWebAssemblyバックエンドがこのIRを処理し、WASMバイナリを生成します。最後に、生成されたWASMファイルがブラウザ上で実行可能な形式になります。

SwiftWasmの利点として、既存のSwiftコードを大幅に修正することなく、WebAssemblyに移植できることが挙げられます。また、Swiftの型安全性やメモリ管理の仕組みがそのまま活かされるため、堅牢なWebアプリケーションの開発が可能になります。

## Next.jsフレームワークの活用

このプレイグラウンドは、Next.jsというReactベースのフレームワークを使用して構築されています。Next.jsを選択した理由は、API Routes機能により、Swiftコードのコンパイル処理をサーバーサイドで実行できることです。

具体的には、`/api/compile`エンドポイントでSwiftコードを受け取り、SwiftWasm SDKを使用してWebAssemblyバイナリを生成します。コンパイル処理では、一時ディレクトリにSwiftパッケージを作成し、Package.swiftファイルとmain.swiftファイルを生成してから、`swift build`コマンドを実行します。

また、`/api/wasm/[id]`エンドポイントでは、生成されたWASMファイルをIDベースで配信しています。ファイルベースルーティングにより、これらのAPIエンドポイントが実装されています。

## アプリケーションの全体構成

プレイグラウンドのアーキテクチャは、フロントエンド、バックエンド、コンパイラエンジンの3つの主要コンポーネントで構成されています。

フロントエンド部分では、Next.jsとReactを使用してユーザーインターフェースを構築しています。コードエディタには、シンタックスハイライトやエラー表示機能を実装し、ユーザーがSwiftコードを快適に記述できる環境を提供しています。

バックエンド部分では、Node.js環境でSwiftWasmコンパイラを実行し、ユーザーが入力したSwiftコードをWebAssemblyに変換します。コンパイル処理は非同期で実行され、進行状況やエラー情報をリアルタイムでフロントエンドに返します。

コンパイラエンジン部分では、SwiftWasmのツールチェーンをDockerコンテナ内で実行することで、環境依存の問題を回避し、一貫したコンパイル結果を提供しています。

## Docker環境による開発環境の統一

開発環境の一貫性を保つため、Dockerコンテナを使用してSwiftWasmの実行環境を構築しています。Dockerfileでは、UbuntuベースのイメージにSwiftWasmの依存関係をインストールし、必要なツールチェーンをセットアップしています。

docker-compose.ymlファイルでは、フロントエンド、バックエンド、コンパイラエンジンの各サービスを定義し、サービス間の通信を設定しています。これにより、開発者がローカル環境で簡単にアプリケーションを起動できるようになっています。

## コンパイルプロセスの詳細

ユーザーがSwiftコードを入力してコンパイルを実行すると、以下のような処理が進行します。

まず、フロントエンドからバックエンドのAPIエンドポイントにSwiftコードが送信されます。バックエンドでは、受け取ったコードを一時ファイルとして保存し、SwiftWasmコンパイラを起動します。

コンパイラは、SwiftコードをLLVM IRに変換し、その後WebAssemblyバイナリを生成します。この過程で、WASIの標準ライブラリとのリンクも行われ、ブラウザ環境で必要なシステムコールが適切に処理されるようになります。

コンパイルが完了すると、生成されたWASMファイルがフロントエンドに返され、ブラウザ上で実行されます。実行時には、WASMランタイムがバイナリコードを解釈し、JavaScriptの関数呼び出しと連携して動作します。

## パフォーマンス最適化の取り組み

WebAssemblyの実行速度を最大限に活用するため、いくつかの最適化技術を実装しています。

まず、WASMファイルのサイズを最小化するため、不要なデバッグ情報の除去や、使用されていない関数の削除を行っています。また、ブラウザのキャッシュ機能を活用して、一度コンパイルされたWASMファイルを効率的に再利用できるようにしています。

さらに、コンパイル処理の並列化により、複数のユーザーが同時にコンパイルを実行しても、レスポンス時間を維持できるようになっています。

## 今後の展望と課題

現在のプレイグラウンドは基本的な機能を実装していますが、今後はより高度な機能の追加を検討しています。

まず、Swiftの標準ライブラリのサポート範囲を拡大し、より多くのSwiftコードが実行できるようにすることを目指しています。また、デバッグ機能の強化により、WASM環境でのエラー解析をより詳細に行えるようにする予定です。

技術的な課題として、WASMファイルのサイズ最適化や、ブラウザ間での互換性の向上があります。これらの課題に対しては、継続的な調査と改善を行い、より安定した実行環境を提供していきたいと考えています。

## まとめ

WebAssemblyとSwiftWasmを活用したプレイグラウンドの構築を通じて、従来のWeb技術の限界を超えた可能性を実感することができました。Swiftの堅牢性とWebの汎用性を組み合わせることで、新しいタイプのWebアプリケーションの開発が可能になります。

このプロジェクトを通じて得られた知見は、今後のWeb開発においても大いに活用できると考えています。特に、パフォーマンスが重要なアプリケーションや、既存のネイティブコードをWebに移植する必要がある場合において、WebAssemblyの技術が重要な役割を果たすことが期待されます。

初心者の方々にも、この技術の可能性を感じていただければ幸いです。WebAssemblyはまだ発展途上の技術ですが、その将来性は非常に高いと確信しています。

