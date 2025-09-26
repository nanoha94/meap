# 認証系単体テスト不足分仕様書

## 概要

現在の 02_Auth フォルダには認証コントローラーのテストケースは含まれていますが、以下の認証関連コンポーネントのテストケースが不足しています。これらのコンポーネントは認証システムの重要な部分を担っているため、包括的なテストが必要です。

## 不足しているテストケース

### 1. HTTP Requests テストケース

#### 1.1 LoginRequest テストケース

**対象ファイル**: `server/app/Http/Requests/Auth/LoginRequest.php`

| ID    | テスト名             | 種別         | 入力条件               | 期待される出力       | 検証ポイント             |
| ----- | -------------------- | ------------ | ---------------------- | -------------------- | ------------------------ |
| LR-1  | 正常なバリデーション | 正常系       | 有効な email, password | バリデーション成功   | rules()メソッドの確認    |
| LR-2  | メールアドレス未入力 | 異常系       | email が空             | バリデーションエラー | required 検証            |
| LR-3  | パスワード未入力     | 異常系       | password が空          | バリデーションエラー | required 検証            |
| LR-4  | 無効なメール形式     | 異常系       | 無効な email 形式      | バリデーションエラー | email 検証               |
| LR-5  | カスタムメッセージ   | 正常系       | バリデーションエラー   | 国際化メッセージ     | messages()メソッド       |
| LR-6  | 認証成功             | 正常系       | 有効な認証情報         | 認証成功             | authenticate()メソッド   |
| LR-7  | 認証失敗             | 異常系       | 無効な認証情報         | ValidationException  | 認証失敗処理             |
| LR-8  | Remember Me 機能     | 正常系       | remember=true          | 永続ログイン         | boolean()メソッド        |
| LR-9  | レート制限チェック   | セキュリティ | 5 回失敗後             | レート制限適用       | ensureIsNotRateLimited() |
| LR-10 | レート制限解除       | セキュリティ | 成功後                 | レート制限クリア     | RateLimiter::clear()     |
| LR-11 | Lockout イベント発火 | セキュリティ | レート制限時           | Lockout イベント     | event(new Lockout())     |
| LR-12 | スロットルキー生成   | セキュリティ | email+IP               | 一意キー生成         | throttleKey()メソッド    |
| LR-13 | 文字列変換処理       | セキュリティ | 大文字・特殊文字含む   | 正規化されたキー     | Str::transliterate()     |

#### 1.2 EmailVerificationRequest テストケース

**対象ファイル**: `server/app/Http/Requests/Auth/EmailVerificationRequest.php`

| ID    | テスト名              | 種別   | 入力条件                                  | 期待される出力        | 検証ポイント               |
| ----- | --------------------- | ------ | ----------------------------------------- | --------------------- | -------------------------- |
| EVR-1 | 正常な認証            | 正常系 | 有効なユーザー、正しい ID、正しいハッシュ | 認証成功              | authorize()メソッド        |
| EVR-2 | 未認証ユーザー        | 異常系 | 未ログイン状態                            | HttpResponseException | 未認証チェック             |
| EVR-3 | 無効なユーザー ID     | 異常系 | 異なるユーザー ID                         | HttpResponseException | ユーザー ID 検証           |
| EVR-4 | 無効なハッシュ        | 異常系 | 間違ったハッシュ値                        | HttpResponseException | ハッシュ検証               |
| EVR-5 | メール確認実行        | 正常系 | 未確認ユーザー                            | メール確認完了        | fulfill()メソッド          |
| EVR-6 | 既に確認済み          | 正常系 | 確認済みユーザー                          | 処理スキップ          | hasVerifiedEmail()チェック |
| EVR-7 | Verified イベント発火 | 正常系 | メール確認時                              | Verified イベント     | event(new Verified())      |
| EVR-8 | リダイレクト URL 確認 | 異常系 | エラー時                                  | 正しいリダイレクト    | frontend_url 設定          |

### 2. Middleware テストケース

#### 2.1 EnsureEmailIsVerified テストケース

**対象ファイル**: `server/app/Http/Middleware/EnsureEmailIsVerified.php`

| ID    | テスト名                         | 種別   | 入力条件             | 期待される出力   | 検証ポイント             |
| ----- | -------------------------------- | ------ | -------------------- | ---------------- | ------------------------ |
| EEV-1 | 確認済みユーザー                 | 正常系 | 確認済みユーザー     | 次の処理へ       | hasVerifiedEmail()=true  |
| EEV-2 | 未認証ユーザー                   | 異常系 | 未ログイン           | エラーレスポンス | user()=null              |
| EEV-3 | 未確認ユーザー                   | 異常系 | 未確認ユーザー       | エラーレスポンス | hasVerifiedEmail()=false |
| EEV-4 | JSON リクエスト                  | 異常系 | expectsJson()=true   | JSON 形式エラー  | expectsJson()チェック    |
| EEV-5 | 非 JSON リクエスト               | 異常系 | expectsJson()=false  | エラーレスポンス | ApiResponse トレイト     |
| EEV-6 | MustVerifyEmail インターフェース | 正常系 | インターフェース実装 | 正常処理         | instanceof チェック      |

#### 2.2 SetLocale テストケース

**対象ファイル**: `server/app/Http/Middleware/SetLocale.php`

| ID   | テスト名                       | 種別   | 入力条件         | 期待される出力         | 検証ポイント           |
| ---- | ------------------------------ | ------ | ---------------- | ---------------------- | ---------------------- |
| SL-1 | 認証済みユーザーのロケール設定 | 正常系 | 認証済みユーザー | ユーザー設定適用       | setLocaleFromUser()    |
| SL-2 | 未認証ユーザーのロケール設定   | 正常系 | 未認証ユーザー   | リクエストヘッダー適用 | setLocaleFromRequest() |
| SL-3 | LocalizationHelper 呼び出し    | 正常系 | 各種条件         | 適切なメソッド呼び出し | ヘルパー使用確認       |
| SL-4 | ミドルウェア通過               | 正常系 | 正常処理         | 次の処理へ             | $next($request)        |

### 3. CustomLogFormatter テストケース

**対象ファイル**: `server/app/Helpers/CustomLogFormatter.php`

| ID    | テスト名                 | 種別   | 入力条件               | 期待される出力  | 検証ポイント           |
| ----- | ------------------------ | ------ | ---------------------- | --------------- | ---------------------- |
| CLF-1 | 基本的なログフォーマット | 正常系 | LogRecord              | JSON 形式出力   | format()メソッド       |
| CLF-2 | オブジェクト展開         | 正常系 | オブジェクト含むデータ | 展開された JSON | expandObjects()        |
| CLF-3 | 配列展開                 | 正常系 | 配列含むデータ         | 展開された JSON | 再帰処理               |
| CLF-4 | ネストしたオブジェクト   | 正常系 | 深いネスト             | 完全展開        | 再帰的展開             |
| CLF-5 | JSON 整形オプション      | 正常系 | 各種データ             | 整形された JSON | JSON_PRETTY_PRINT 等   |
| CLF-6 | Unicode 文字             | 正常系 | 日本語等               | 正しい文字表示  | JSON_UNESCAPED_UNICODE |
| CLF-7 | 改行追加                 | 正常系 | 任意のデータ           | 末尾改行付き    | "\n"追加               |

### 4. Custom Auth Components テストケース

#### 4.1 CustomPasswordBroker テストケース

**対象ファイル**: `server/app/Custom/Auth/CustomPasswordBroker.php`

| ID     | テスト名                 | 種別   | 入力条件           | 期待される出力        | 検証ポイント           |
| ------ | ------------------------ | ------ | ------------------ | --------------------- | ---------------------- |
| CPB-1  | 正常なリセットリンク送信 | 正常系 | 有効なユーザー     | RESET_LINK_SENT       | sendResetLink()        |
| CPB-2  | 無効なユーザー           | 異常系 | 存在しないユーザー | INVALID_USER          | getUser()失敗          |
| CPB-3  | レート制限               | 異常系 | 短時間での再送信   | RESET_THROTTLED       | recentlyCreatedToken() |
| CPB-4  | トークン生成失敗         | 異常系 | トークン生成 null  | RETRY_TOKEN           | カスタム定数           |
| CPB-5  | コールバック実行         | 正常系 | コールバック指定   | コールバック結果      | callback 実行          |
| CPB-6  | イベント発火             | 正常系 | 正常送信           | PasswordResetLinkSent | event dispatch         |
| CPB-7  | 正常なパスワードリセット | 正常系 | 有効なトークン     | PASSWORD_RESET        | reset()                |
| CPB-8  | 無効なトークン           | 異常系 | 期限切れトークン   | INVALID_TOKEN         | トークン検証           |
| CPB-9  | トークンからメール取得   | 正常系 | 有効なトークン     | メールアドレス        | Hash::check()          |
| CPB-10 | ユーザー存在確認         | 異常系 | 存在しないユーザー | INVALID_USER          | User::where()          |
| CPB-11 | トークン削除             | 正常系 | リセット完了後     | トークン削除          | tokens->delete()       |

#### 4.2 CustomPasswordBrokerManager テストケース

**対象ファイル**: `server/app/Custom/Auth/CustomPasswordBrokerManager.php`

| ID     | テスト名                  | 種別   | 入力条件              | 期待される出力                | 検証ポイント            |
| ------ | ------------------------- | ------ | --------------------- | ----------------------------- | ----------------------- |
| CPBM-1 | CustomPasswordBroker 生成 | 正常系 | 正常な設定            | CustomPasswordBroker          | resolve()               |
| CPBM-2 | 無効な設定名              | 異常系 | 存在しない設定        | InvalidArgumentException      | getConfig()失敗         |
| CPBM-3 | TokenRepository 生成      | 正常系 | database 設定         | CustomDatabaseTokenRepository | createTokenRepository() |
| CPBM-4 | Cache TokenRepository     | 正常系 | cache 設定            | CacheTokenRepository          | cache driver            |
| CPBM-5 | 設定値読み込み            | 正常系 | 各種設定              | 正しい設定適用                | config 読み込み         |
| CPBM-6 | base64 キー処理           | 正常系 | base64:プレフィックス | デコード済みキー              | base64_decode()         |

#### 4.3 CustomDatabaseTokenRepository テストケース

**対象ファイル**: `server/app/Custom/Auth/CustomDatabaseTokenRepository.php`

| ID     | テスト名             | 種別   | 入力条件         | 期待される出力   | 検証ポイント         |
| ------ | -------------------- | ------ | ---------------- | ---------------- | -------------------- |
| CDTR-1 | 正常なトークン生成   | 正常系 | 有効なユーザー   | ユニークトークン | create()             |
| CDTR-2 | 既存トークン削除     | 正常系 | 既存トークンあり | 削除後新規作成   | deleteExisting()     |
| CDTR-3 | トークン重複チェック | 正常系 | 重複トークン     | 再生成実行       | while loop           |
| CDTR-4 | 最大試行回数超過     | 異常系 | 5 回試行失敗     | null 返却        | maxAttempts          |
| CDTR-5 | データベース挿入     | 正常系 | 正常なトークン   | DB 挿入成功      | getTable()->insert() |
| CDTR-6 | ペイロード生成       | 正常系 | email, token     | 正しいペイロード | getPayload()         |

#### 4.4 CustomPasswordBroker Interface テストケース

**対象ファイル**: `server/app/Custom/Auth/Interfaces/CustomPasswordBroker.php`

| ID     | テスト名                 | 種別   | 入力条件             | 期待される出力          | 検証ポイント    |
| ------ | ------------------------ | ------ | -------------------- | ----------------------- | --------------- |
| CPBI-1 | インターフェース実装確認 | 正常系 | CustomPasswordBroker | インターフェース準拠    | implements 確認 |
| CPBI-2 | RETRY_TOKEN 定数         | 正常系 | 定数アクセス         | 'passwords.retry_token' | 定数値確認      |

### 5. Notifications テストケース

#### 5.1 CustomResetPasswordNotification テストケース

**対象ファイル**: `server/app/Notifications/Auth/CustomResetPasswordNotification.php`

| ID     | テスト名           | 種別   | 入力条件         | 期待される出力 | 検証ポイント         |
| ------ | ------------------ | ------ | ---------------- | -------------- | -------------------- |
| CRPN-1 | 通知チャンネル確認 | 正常系 | 通知対象         | ['mail']       | via()メソッド        |
| CRPN-2 | メール内容生成     | 正常系 | トークン         | MailMessage    | toMail()             |
| CRPN-3 | リセット URL 生成  | 正常系 | トークン         | 正しい URL     | frontend_url + token |
| CRPN-4 | トークン保存       | 正常系 | コンストラクタ   | トークン保存   | $this->token         |
| CRPN-5 | Queueable 使用     | 正常系 | 通知送信         | キュー対応     | use Queueable        |
| CRPN-6 | 配列表現           | 正常系 | toArray 呼び出し | 空配列         | toArray()            |

#### 5.2 CustomVerifyEmailNotification テストケース

**対象ファイル**: `server/app/Notifications/Auth/CustomVerifyEmailNotification.php`

| ID      | テスト名                  | 種別   | 入力条件               | 期待される出力         | 検証ポイント                |
| ------- | ------------------------- | ------ | ---------------------- | ---------------------- | --------------------------- |
| CVEN-1  | 確認 URL 生成             | 正常系 | 通知対象ユーザー       | 署名付き URL           | verificationUrl()           |
| CVEN-2  | カスタム URL コールバック | 正常系 | createUrlCallback 設定 | コールバック結果       | static::$createUrlCallback  |
| CVEN-3  | ローカル環境 URL 処理     | 正常系 | local 環境             | ポート番号処理         | app()->environment()        |
| CVEN-4  | 署名付き URL 生成         | 正常系 | 正常なユーザー         | temporarySignedRoute   | URL::temporarySignedRoute() |
| CVEN-5  | ハッシュ生成              | 正常系 | ユーザーメール         | SHA1 ハッシュ          | sha1()処理                  |
| CVEN-6  | URL 置換処理              | 正常系 | ベース URL             | 正しい URL             | str_replace()               |
| CVEN-7  | メール内容生成            | 正常系 | 通知対象               | 日本語メール           | toMail()                    |
| CVEN-8  | メール件名                | 正常系 | メール生成             | 'メールアドレスの確認' | subject()                   |
| CVEN-9  | 挨拶文                    | 正常系 | ユーザー名             | '〇〇 様'              | greeting()                  |
| CVEN-10 | アクションボタン          | 正常系 | 確認 URL               | 'メールアドレスを確認' | action()                    |

### 6. Service Provider テストケース

#### 6.1 CustomPasswordResetServiceProvider テストケース

**対象ファイル**: `server/app/Providers/Custom/CustomPasswordResetServiceProvider.php`

| ID      | テスト名                    | 種別   | 入力条件                 | 期待される出力         | 検証ポイント                    |
| ------- | --------------------------- | ------ | ------------------------ | ---------------------- | ------------------------------- |
| CPRSP-1 | サービス登録                | 正常系 | register()呼び出し       | バインド完了           | app->bind()                     |
| CPRSP-2 | インターフェースバインド    | 正常系 | PasswordBroker           | CustomPasswordBroker   | インターフェースバインド        |
| CPRSP-3 | PasswordBroker 登録         | 正常系 | registerPasswordBroker() | シングルトン登録       | app->singleton()                |
| CPRSP-4 | ブローカー取得              | 正常系 | auth.password.broker     | ブローカーインスタンス | app->make()                     |
| CPRSP-5 | CustomPasswordBrokerManager | 正常系 | auth.password            | Manager 生成           | new CustomPasswordBrokerManager |

## テスト実装の優先順位

### 高優先度

1. **LoginRequest** - 認証の入り口として重要
2. **CustomPasswordBroker** - パスワードリセット機能の中核
3. **EnsureEmailIsVerified** - セキュリティ要件

### 中優先度

4. **EmailVerificationRequest** - メール確認機能
5. **CustomDatabaseTokenRepository** - トークン管理
6. **SetLocale** - 国際化対応

### 低優先度

7. **CustomLogFormatter** - ログ機能
8. **Notifications** - 通知機能
9. **Service Provider** - DI 設定

## 実装時の注意点

1. **モック使用**: 外部依存（メール送信、データベース）はモックを使用
2. **セキュリティテスト**: レート制限、トークン検証等のセキュリティ機能を重点的にテスト
3. **エラーハンドリング**: 異常系のテストケースを充実させる
4. **国際化**: 日本語メッセージの確認
5. **設定値**: 環境設定に依存する部分のテスト

## テスト実行環境

```bash
cd server
./vendor/bin/pest --group=auth-components
```

## 関連ドキュメント

-   [既存認証テスト仕様](../tests/docs/02_Auth/)
-   [リファクタリング計画](./VERIFY_EMAIL_CONTROLLER_REFACTORING_PLAN.md)
-   [トレイト設計計画](./TRAIT_DESIGN_REFACTORING_PLAN.md)
