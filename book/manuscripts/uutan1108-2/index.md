---
class: content
---

<div class="doc-header">
  <div class="doc-title">SwiftWasmでブラウザで動くSwiftプレイグラウンドを構築</div>
  <div class="doc-author">うーたん（@uutan1108）</div>
</div>

# SwiftWasmでブラウザで動くSwiftプレイグラウンドを構築

## はじめに

Web エンジニアとして日々 Web のバックエンドを担当している中で、Swift という言語に興味を持ちました。Web が好きなので、Web をきっかけに Swift を学ぼうと思い、SwiftWasm を使ってオンラインプレイグラウンドを開発してみました。

このプレイグラウンドでは、ブラウザ上でSwiftコードを入力し、コンパイル・実行できる環境を提供しています。ユーザーがSwiftコードを書いて実行すると、サーバーサイドでSwiftWasmコンパイラがWebAssemblyバイナリを生成し、ブラウザ上で実行されます。

本記事では、このプレイグラウンドの構築過程を通じて、利用したNext.jsフレームワークについて、SwiftWasmによるコンパイル処理、WebAssemblyとWASIの仕組み、そして実際の実装コードについて詳しく解説していきます。

## Next.js について

このプレイグラウンドは、Next.jsというReactベースのフレームワークを使用して構築されています。Next.jsを選択した理由は、API Routes機能により、Swiftコードのコンパイル処理をサーバーサイドで実行できることです。

具体的には、`/api/compile`エンドポイントでSwiftコードを受け取り、SwiftWasm SDKを使用してWebAssemblyバイナリを生成します。コンパイル処理では、一時ディレクトリにSwiftパッケージを作成し、Package.swiftファイルとmain.swiftファイルを生成してから、`swift build`コマンドを実行します。

また、`/api/wasm/[id]`エンドポイントでは、生成されたWASMファイルをIDベースで配信しています。ファイルベースルーティングにより、これらのAPIエンドポイントが実装されています。

### APIエンドポイント一覧

| エンドポイント | メソッド | 機能 | 入力 | 出力 |
|---------------|----------|------|------|------|
| `/api/compile` | POST | SwiftコードをWASMにコンパイル | Swiftソースコード | コンパイル結果とWASM ID |
| `/api/wasm/[id]` | GET | コンパイル済みWASMファイルを配信 | WASM ID | WASMバイナリファイル |

## SwiftWasmの技術的仕組み

SwiftWasmは、Swift言語で書かれたコードをWebAssemblyにコンパイルするためのツールチェーンです。SwiftコードをWASMバイナリに変換することで、ブラウザ上でSwiftプログラムを実行できるようになります。

## SwiftコードからWASMへの変換処理

`/api/compile`エンドポイントでは、ブラウザから送信されたSwiftコードを受け取り、SwiftWasmコンパイラを使用してWebAssemblyバイナリを生成します。以下が実際の実装コードです：

```typescript
export async function POST(request: NextRequest) {
  try {
    const { code }: CompileRequest = await request.json();
    
    // 一時ディレクトリを作成
    const tempDir = await mkdtemp(join(os.tmpdir(), 'swift-compile-'));
    const packageDir = join(tempDir, 'swift-package');
    await mkdir(packageDir, { recursive: true });
    
    // Package.swiftファイルを生成
    const packageSwift = `// swift-tools-version: 5.9
import PackageDescription

let package = Package(
    name: "swift-playground",
    platforms: [.macOS(.v13)],
    products: [
        .executable(name: "main", targets: ["main"])
    ],
    targets: [
        .executableTarget(
            name: "main",
            path: "Sources/main"
        )
    ]
}`;
    
    // ディレクトリ構造を作成
    const sourcesDir = join(packageDir, 'Sources', 'main');
    await mkdir(sourcesDir, { recursive: true });
    
    // main.swiftファイルにユーザーコードを書き込み
    const mainSwift = `import Foundation

${code}`;
    
    await writeFile(join(sourcesDir, 'main.swift'), mainSwift);
    await writeFile(join(packageDir, 'Package.swift'), packageSwift);
    
    // SwiftWasmコンパイラを実行
    const wasmSDKName = 'wasm32-unknown-wasi';
    const result = execSync(
      `swift build --swift-sdk "${wasmSDKName}" --package-path "${packageDir}" --scratch-path "${join(packageDir, '.build')}"`,
      {
        cwd: packageDir,
        encoding: 'utf8',
        timeout: 30000,
        env: {
          ...process.env,
          PATH: '/opt/swift/usr/bin:' + (process.env.PATH ? ':' + process.env.PATH : ''),
        }
      }
    );
    
    // 生成されたWASMファイルを探してコピー
    const wasmFile = join(packageDir, '.build', wasmSDKName, 'debug', 'main.wasm');
    if (!existsSync(wasmFile)) {
      throw new Error('WASMファイルの生成に失敗しました');
    }
    
    const wasmId = generateId();
    const outputPath = join(process.cwd(), 'public', 'wasm', `${wasmId}.wasm`);
    await copyFile(wasmFile, outputPath);
    
    // 一時ディレクトリを削除
    await rm(tempDir, { recursive: true, force: true });
    
    return NextResponse.json({ success: true, wasmId });
    
  } catch (error) {
    return NextResponse.json(
      { success: false, error: error.message },
      { status: 500 }
    );
  }
}
```

このAPIの処理フローは次の通りです。まず、ブラウザからPOSTリクエストでSwiftコードを受け取ります。次に、コンパイル用の一時的な作業ディレクトリを作成し、Package.swiftとmain.swiftファイルを生成してSwiftパッケージ構造を作成します。その後、SwiftWasmコンパイラ（`swift build`）を実行してWASMバイナリを生成し、生成されたWASMファイルを適切な場所にコピーして一意のIDを割り当てます。最後に、一時ディレクトリを削除してリソースを解放します。

コンパイル処理では、SwiftWasmのWebAssemblyターゲット（`wasm32-unknown-wasi`）を使用し、WASI対応のWASMバイナリを生成します。生成されたファイルは`/public/wasm/`ディレクトリに保存され、後で`/api/wasm/[id]`エンドポイントから配信されます。

## WebAssembly（WASM）とは

WebAssemblyは、Webブラウザ上で実行できるバイナリ形式のコードです。従来のJavaScriptと比較して、ネイティブに近い実行速度を実現します。

簡単に言うと、C++、Rust、Swiftなどの言語で書かれたコードをWebAssemblyのバイナリに変換してブラウザ上で実行できるようになります。これにより、これらの言語で書かれたコードをブラウザ上で実行できます。

身近な例として、YouTubeの動画再生やオンラインゲーム、ブラウザ版の画像・動画編集ツールなどでWebAssemblyが使用されています。

## WASI（WebAssembly System Interface）の役割

WASIは、WebAssemblyがOSの機能にアクセスするための標準インタフェースです。従来のWebAssemblyはブラウザ環境に限定されていましたが、WASIによりファイルシステムへのアクセス、ネットワーク通信、プロセス管理などのシステムレベルの操作が可能になります。

### システムコールとは

システムコールとは、アプリケーションプログラムがOSのカーネルに直接サービスを要求する仕組みです。例えば、ファイルの読み書き、プロセスの終了、時刻の取得などがこれに該当します。通常、WebAssemblyはブラウザ環境で実行されるため、これらのシステムコールにアクセスできませんが、WASIにより標準化されたインタフェースを通じてシステムコールを模倣できるようになります。

### このプレイグラウンドでの実装

このプレイグラウンドでは、Swiftコードが`print()`や`exit()`などの関数を呼び出すと、WASMモジュールがWASIの関数（`fd_write`、`proc_exit`など）を呼び出します。これらのWASI関数は、実際のOSのシステムコールではなく、TypeScriptで実装された対応する処理（標準出力への表示、プロセス終了の処理など）を実行します。

### SwiftコードとWASI関数の対応表

| Swiftコード | WASI関数 | 機能 | 実装内容 |
|------------|----------|------|----------|
| `print("Hello")` | `fd_write` | 標準出力 | ブラウザのコンソールに文字列を表示 |
| `readLine()` | `fd_read` | 標準入力 | ブラウザのpromptダイアログで入力を取得 |
| `exit(0)` | `proc_exit` | プロセス終了 | プログラムの終了処理と終了コードの表示 |
| `Int.random(in: 1...100)` | `random_get` | 乱数生成 | 乱数を生成 |

実際の実装では、WASIの標準関数をTypeScriptで実装しています。これらの関数は、WebAssemblyモジュールがシステムコールを実行しようとした際に、ブラウザ環境で適切に処理されるようになっています。

実際のWASI実装では、以下のような関数が定義されています。

```typescript
export function createWasiImports(setOutput: SetOutputFunction) {
  let instance: WebAssembly.Instance;
  
  return {
    wasi_snapshot_preview1: {
      // print("Hello") → fd_write
      fd_write: (fd: number, iovs: number, iovsLen: number, nwritten: number) => {
        if (fd === 1 || fd === 2) { 
          const memory = (instance.exports.memory as WebAssembly.Memory);
          const buffer = new Uint8Array(memory.buffer);
          let totalWritten = 0;
          for (let i = 0; i < iovsLen; i++) {
            const iovPtr = iovs + i * 8;
            const strPtr = new DataView(memory.buffer).getUint32(iovPtr, true);
            const strLen = new DataView(memory.buffer).getUint32(iovPtr + 4, true);
            const str = new TextDecoder().decode(buffer.slice(strPtr, strPtr + strLen));
            setOutput(prev => prev + str);
            totalWritten += strLen;
          }
          new DataView(memory.buffer).setBigUint64(nwritten, BigInt(totalWritten), true);
          return 0;
        }
        return -1;
      },
      // readLine() → fd_read
      fd_read: (fd: number, iovs: number, iovsLen: number, nread: number) => {
        if (fd === 0) {
          const memory = (instance.exports.memory as WebAssembly.Memory);
          const buffer = new Uint8Array(memory.buffer);
          const input = prompt("標準入力:");
          if (input === null) {
            new DataView(memory.buffer).setUint32(nread, 0, true);
            return 0;
          }
          const inputWithNewline = input + '\n';
          const inputBytes = new TextEncoder().encode(inputWithNewline);
          let totalRead = 0;
          for (let i = 0; i < iovsLen; i++) {
            const iovPtr = iovs + i * 8;
            const bufPtr = new DataView(memory.buffer).getUint32(iovPtr, true);
            const bufLen = new DataView(memory.buffer).getUint32(iovPtr + 4, true);
            const bytesToCopy = Math.min(bufLen, inputBytes.length - totalRead);
            if (bytesToCopy > 0) {
              buffer.set(inputBytes.slice(totalRead, totalRead + bytesToCopy), bufPtr);
              totalRead += bytesToCopy;
            }
            if (totalRead >= inputBytes.length) break;
          }
          new DataView(memory.buffer).setUint32(nread, totalRead, true);
          return 0;
        }
        return 8;
      },
      // exit(0) → proc_exit
      proc_exit: (code: number) => {
        if (code !== 0) {
          setOutput(prev => prev + `\nプロセスが終了コード ${code} で終了しました\n`);
        }
      },
      // Int.random(in: 1...100) → random_get
      random_get: (buf: number, bufLen: number) => {
        const memory = (instance.exports.memory as WebAssembly.Memory);
        const buffer = new Uint8Array(memory.buffer, buf, bufLen);
        crypto.getRandomValues(buffer);
        return 0;
      }
    },
    setInstance: (wasmInstance: WebAssembly.Instance) => {
      instance = wasmInstance;
    }
  };
}
```

この実装により、Swiftコードの`print()`文や`exit()`関数、乱数生成などが、ブラウザ環境で適切に処理されるようになっています。

## WASMモジュールとWASIの連携

WASMモジュールの実行時には、WASIの関数がインポートとして渡されます。以下のコードは、WASMファイルを実行し、WASI関数と連携する流れを示しています。

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

このコードから、WASMとWASIの関係性が明確になります。まず、`/api/compile`エンドポイントでSwiftコードをSwiftWasmでコンパイルしてWASMバイナリを生成し、`/api/wasm/${wasmId}`からコンパイル済みのWASMバイナリを取得します。次に、`createWasiImports`で前章で解説したWASIの標準インタフェースを実装し、`WebAssembly.compile`と`WebAssembly.instantiate`でWASMモジュールを作成してインスタンス化時にWASI関数をインポートとして渡します。最後に、`_start`関数を呼び出してSwiftプログラムの実行を開始します。

## まとめ

このプレイグラウンドの開発を通じて、WebAssemblyの仕組みを学び、実際にブラウザ上でSwiftコードを動作させることができました。

技術的なポイントとして、SwiftWasmによるコンパイル、WASIによるシステムコールの実装、Next.jsによるAPI実装という3つの要素が組み合わさることで、Swiftコードのブラウザ実行が実現できました。

特に、WASIの標準インタフェースをTypeScriptで実装することで、Swiftの`print()`や`readLine()`などの標準的な関数がブラウザ環境で適切に動作するようになった点は、WebAssemblyの仕組みを理解する良い機会となりました。

今後は、この経験を活かして、画像処理やデータ分析などの計算集約的な処理をWASMで実装し、Webアプリケーションのパフォーマンス向上に活用していきたいと思います。
