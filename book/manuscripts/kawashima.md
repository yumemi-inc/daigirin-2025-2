---
class: content
---

<div class="doc-header">
  <h1>モバイルアプリで変わったもの・<br>変わらなかったもの</h1>
  <div class="doc-author">川島慶之</div>
</div>

 モバイルアプリで変わったもの・変わらなかったもの
==

## はじめに

筆者は2004年からモバイルアプリ開発に関わってきました。これまでの歴史を振り返って、この20年ほどで変わったものと変わらなかったものを見ていき、この先の20年を考えてみます。

モバイルアプリは大きく二つに分類されます。
CコンパイラベースとJVMベースです<span class="footnote">これらはモバイルアプリの中でもネイティブと呼ばれ携帯電話のプラットフォームをラップせずに直接利用することから、後述の言語の対比の中でそう呼ばれています。ネイティブのほかに、Webベースの jQuery Mobile/Cordova/Ionic/React Native、.NET環境の Xamarin、Flutter 等多数存在しますが、これらはプラットフォームをラップしていて、今回の記事の対象外としました。単純に、これらの方面に対して、筆者の経験が乏しいのも理由の一つです。</span>。
基本的にガラケーの頃はJVMベースのJavaアプリが主流でした。EZアプリ<span class="footnote">EZアプリはJVMベースのJavaアプリとCコンパイラベースのBREWがあります。</span>のみ途中からCコンパイラベースに変わりました。携帯電話に特化したプロファイルとしてMIDPに準拠しており、iアプリのみ独自プロファイル・DoJaを採用していました。その後、スマートフォンが登場して、Cコンパイラベースの iPhone と JVM ベースの Android<span class="footnote">Android では、Java だけでなく、NDK を利用した C/C++ のライブラリを組み込むことも可能です。</span> が登場して、今も続いています。

<img src="./images_kawashima/mobile_history.png">

ガラケー時代のモバイルアプリはJavaアプリが主流でした。Java言語の特性上 `import`<span class="footnote">モジュール・インポート宣言 ttps://docs.oracle.com/javase/jp/23/language/module-import-declarations.html</span> やオーバーライドメソッドが必須になるのですが、紙面では冗長な情報になるため、全体的に文脈と無関係な文は省略しています。現状、ビルド可能なリソース自体入手困難だと思うので検証も難しいですが、そのままではコンパイルが通らないことをご了承ください。

それでは、画面に HelloWorld と出力するアプリを通してこれまでのモバイルアプリの変遷を見ていきます。

### iアプリ

Canvas に直接グラフィックスを描画して、画面に表示していました。

```java
public class MyApp extends IApplication {
    // 起動時に呼ばれる
    public void start() {
        // Canvasを継承したクラスのインスタンスを
        // ディスプレイにセットすることで画面に描画される
        Display.setCurrent(new ContentView());
    }
}

class ContentView extends Canvas {
    // システムから必要に応じて呼ばれる
    public void paint(Graphics g) {
        g.drawString("Hello, World!", 0, 0);
    }
}
```

インタラクティブな描画が必要な時は、スレッドを用意して描画と処理を無限に繰り返すメインループで行っていました。

```java
public class MyApp extends IApplication {
    // 起動時に呼ばれる
    public void start() {
        // Canvasを継承したクラスのインスタンスを
        // ディスプレイにセットすることで画面に描画される
        ContentView v = new ContentView();
        Display.setCurrent(v);
        // インタラクティブな処理のためにスレッドを開始する
        new Thread(v).start();
    }
}

// Runnable を継承してスレッド処理可能にします
class ContentView extends Canvas implements Runnable {
    public void run() {
        // メインループ
        while (true) {
            int keyEvent = getKeypadState();
            // キーイベント処理
            // ...

            // バッファリング
            g.lock();
            g.drawString("Hello, World!", 0, 0);
            // 描画処理
            // ...
            g.unlock(true);

            // メインループでシステムをブロックしないようにスリープを入れる
            try {
                Thread.sleep(100);
            } catch (Exception e) {
            }
        }
    }
    // システムから必要に応じて呼ばれるがスレッド処理するのでここでは何もしない
    public void paint(Graphics g) {
    }
}
```

### MIDP 2.0

iアプリが独自拡張している DoJa と API が多少異なる程度、大きな差異はないです。

```java
public class MyApp extends MIDlet {
    // 起動時に呼ばれる
    public MyApp() {
        Display.getDisplay(this).setCurrent(new ContentView());
    }
}

class ContentView extends Canvas {
    // システムから必要に応じて呼ばれる
    public void paint(Graphics g) {
        g.drawString("Hello, World!", 0, 0, Graphics.LEFT | Graphics.TOP);
    }
}
```

### EZアプリ

```c
int AEEClsCreateInstance(AEECLSID ClsId, IShell * pIShell, IModule * po, void ** ppObj)
{
    if (ClsId == AEECLSID_MYAPP)
    {
        if (AEEApplet_New(sizeof(MyApp),
                          ClsId,
                          pIShell,
                          (IApplet**)po
                          poObj,
                          (AEEHANDER)MyAppEvent),
                          (PNFE)))
    }

    return (EFAILED);
}
```

### Android（Java）

Activity という概念が登場しました。モバイルアプリのライフサイクルの考え方に大きな違いはありません。
ただし、画面回転の概念が誕生したため、`savedInstanceState` のように状態を保存する必要性が生じるようになってきました。
プログラムとレイアウトとリソースが分かれるようになりました。
デバイス側でサポートするアプリサイズが飛躍的に上昇して、ファイルを分割するのが当たり前になりました。
これまでと同じように Canvas を利用して座標に直接描画する方法も可能ではあります。

```java
public class MainActivity extends Activity {
    // 起動時に呼ばれる
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate();
        setContentView(R.layout.main);
    }
}
```

レイアウトファイル

```xml
<?xml version="1.0" encoding="utf-8"?>
<LinerLayout xmlns:android="http://schemas.android.com/apk/res/android"
    android:orientation="vertical"
    android:layout_width="fill_parent"
    android:layout_height="fill_parent"
    >
    <TextView
        android:layout_width="fill_parent"
        android:layout_height="wrap_content"
        android:text="@string/hello_world"
    />
</LinerLayout>
```

テキストリソースファイル

```xml
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="hello_world">Hello, World!</string>
</resources>
```

レイアウトファイルはコンパイルされると `R.java` に変換されてプログラムから `R.layout.main` という形でアクセス可能になっています。

### iPhone OS（Objective-C）

ここまでプラットフォームや SDK が変わっても Java アプリだったので似たような構成でした。
iPhone OS<span class="footnote">初期は iOS ではなく、iPhone OS と呼ばれていました。</span> は Java アプリではないため、がらりと構成が変わります。
Objetive-C は名前に **C** と付いている通り、C言語のスタイルを踏襲しています。そのため、`main` 関数から始まります。

```objc
// 起動時に呼ばれます
int main(int argc, char *argv[]) {
    NSAutoreleasePool * pool = [[NSAutoreleasePool alloc] init];
    int retVal = UIApplicationMain(argc, argv, nil, @"MyApp");
    [pool release];
    return retVal;
}
```

ヘッダファイルと実装ファイルが必要

```objc

@class MainViewController;

@interface MyApp : NSObject <UIApplicationDelegate> {
    UIWindow *window;
    MainViewController *viewController;
}

@property (nonatomic, retain) IBOutlet UIWindow *window;
@property (nonatomic, retain) IBOutlet MainViewController *viewController;

@end
```

```objc
@implementation MyApp

@synthesize window;
@synthesize viewController;

- (void)applicationDidFinishLaunching:(UIApplication *)application {
    window = [[UIWindow alloc] initWithFrame:[[UIScreen mainScreen] bounds]];
    viewController = [[MyViewController alloc] init];

    [window addSubview:viewController.view];
    [window makeKeyAndVisible];
}

- (void)dealloc {
    [viewController release];
    [window release];
    [super dealloc];
}
@end
```

ViewController という考えがモバイルアプリに登場して、Model-View-Controller の MVCアーキテクチャが一般化します。

```objc
@interface MainViewController : UIViewController {
    UITextView *textView;
}
@end
```

```objc
@implementation MainViewController

- (id)init {
    return [super init];
}

- (void)loadView {
    textView = [[UITextView alloc] initWithFrame:[UIScreen mainScreen]];
    textView.text = @"Hello, Wolrd!";
    self.view = textView;
}

- (void)dealloc {
    [textView release];
    [super dealloc];
}
@end
```

### iOS（Swift）

間をかなり飛ばして Swift/SwiftUI での表現は Objective-C から比べるとかなりシンプルになりました。
プログラムとして描画命令の考えから、宣言的な UI 表現へと変わっていきました
Preview も宣言的に書けるようになりました。
このコードの例では一つのプレビューですが、状態によって表示が変わる場合など複数の条件のプレビューを定義することもできます。

```swift
@main
struct MyApp: App {
  var body: some Scene {
    WindowGroup {
      ContentView()
    }
  }
}

struct ContentView: View {
  var body: some View {
    VStack {
      Text("Hello, World!")
    }
  }
}

#Preview {
  ContentView()
}
```

### Android（Kotlin）

こちらも間を飛ばしていますが、 Kotlin/Jetpack Compose で宣言的な UI 表現へと変わっています
SwiftUI 同様にプレビューも宣言的に定義できます。

```kotlin
class MainActivity : ComponentActivity() {
  override fun onCreate(savedInstanceState: Bundle?) {
    super.onCreate(savedInstanceState)
    setContent {
      ContentView()
    }
  }
}

@Composable
fun ContentView() {
  Text("Hello, World!")
}

@Preview
@Composable
fun ContentViewPreview() {
  ContentView()
}
```

### iOS(App Intent)

App Intent<span class="footnote">https://developer.apple.com/documentation/AppIntents</span> により画面を持たずに処理だけを行うイメージです。

```swift
struct MyIntent: AppIntent {
  static let title: LocalizedStringResource = "My Intent"

  @MainActor
  func perform() async throws -> some IntentResult {
    return .result()
  }
}
```

Android 側には、古くから Intent や Broadcast は存在しますが、Siri のような AI と OS の統合の観点だとまだ存在しない

## まとめ



## 参考文献
- 鷲見豊著. プログラミングiモードJavaーiアプリの設計と開発, オライリー・ジャパン, 2001.
- 布留川英一著. iアプリゲーム開発テキストブック, 毎日コミュニケーションズ, 2005.
- 笠野英松著. BREWアドバンスト・プログラミング, 秀和システム, 2005.
- 布留川英一著. MIDP 2.0 携帯Javaアプリ開発ハンドブック, 毎日コミュニケーションズ, 2005.
- 藤田和久著. Java言語によるモバイルゲーム開発, ソフトバンククリエイティブ, 2008.
- 江川崇／藤井大助／麻野耕一／藤田泰介／山田暁通／山岡敏夫／佐野徹郎／竹端進著. Google Androidプログラミング入門、アスキー・メディアワークス, 2009. 
- ジョナサン・ジジアルスキー著, 近藤誠監訳. iPhone SDK アプリケーション開発ガイド, オライリー・ジャパン, 2009.
- SwiftUI Tutorials https://developer.apple.com/tutorials/swiftui/
- Jetpack Compose Tutorial https://developer.android.com/develop/ui/compose/tutorial
