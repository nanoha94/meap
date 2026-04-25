# SocialLoginController テストケース詳細仕様

## 概要

Google アカウントによるソーシャルログイン機能のテストスイートです。Laravel Socialite を用いた OAuth リダイレクトフロー（redirect → callback）を検証し、新規ユーザー作成・既存アカウント連携・エラーハンドリングの各シナリオをカバーします。

## テストケース一覧表

| ID     | テスト名                                                                  | 種別   | 入力条件                                         | 期待される出力                                                     | 該当メソッド                                  |
| ------ | ------------------------------------------------------------------------- | ------ | ------------------------------------------------ | ------------------------------------------------------------------ | --------------------------------------------- |
| 2-7-1  | 【リダイレクト】 Google OAuth 画面へリダイレクト                          | 正常系 | 未認証状態で GET /auth/google/redirect            | HTTP 302 Google OAuth URL へリダイレクト                            | `SocialLoginController::redirectToGoogle()`   |
| 2-7-2  | 【リダイレクト】 認証済みユーザーはリダイレクトできない                    | 異常系 | 認証済み状態で GET /auth/google/redirect          | HTTP 302 フロントエンドへリダイレクト（guest ミドルウェア）         | `SocialLoginController::redirectToGoogle()`   |
| 2-7-3  | 【コールバック】 新規ユーザー作成＋ログイン                               | 正常系 | 未登録メールアドレスの Google ユーザー            | ユーザー・グループ・SocialAccount 作成、セッションログイン、/plan へ 302 | `SocialLoginController::handleGoogleCallback()` |
| 2-7-4  | 【コールバック】 既存 SocialAccount でログイン                            | 正常系 | 紐付け済み Google アカウント                      | セッションログイン、/plan へ 302                                   | `SocialLoginController::handleGoogleCallback()` |
| 2-7-5  | 【コールバック】 同一メール既存ユーザーに SocialAccount を紐付けてログイン | 正常系 | 既存ユーザーのメールと一致する Google アカウント   | SocialAccount 作成、セッションログイン、/plan へ 302               | `SocialLoginController::handleGoogleCallback()` |
| 2-7-6  | 【コールバック】 メール未確認の既存ユーザーに紐付け時 email_verified_at を設定 | 正常系 | email_verified_at が null の既存ユーザー          | email_verified_at が設定される                                     | `SocialLoginController::handleGoogleCallback()` |
| 2-7-7  | 【コールバック】 Google から名前が空の場合メールのローカルパートを使用    | 正常系 | Google ユーザーの名前が空                         | メールアドレスの @ 前がユーザー名になる                            | `SocialLoginController::handleGoogleCallback()` |
| 2-7-8  | 【コールバック】 セッション再生成の確認                                   | 正常系 | コールバック成功後                                | セッション ID が再生成される                                       | `SocialLoginController::handleGoogleCallback()` |
| 2-7-9  | 【コールバック】 InvalidStateException でエラーリダイレクト               | 異常系 | OAuth state 不一致（InvalidStateException）       | /login?error=oauth_state_invalid へ 302                            | `SocialLoginController::handleGoogleCallback()` |
| 2-7-10 | 【コールバック】 Google API エラーでエラーリダイレクト                    | 異常系 | Socialite::user() が例外スロー                    | /login?error=oauth_failed へ 302                                   | `SocialLoginController::handleGoogleCallback()` |
| 2-7-11 | 【コールバック】 Google からメールが返らない場合エラーリダイレクト        | 異常系 | Google ユーザーの email が null                   | /login?error=oauth_no_email へ 302                                 | `SocialLoginController::handleGoogleCallback()` |

## テスト実行方法

### Sail 環境での実行

```bash
cd server
./tests/sh/02_run_auth_tests.sh
```
