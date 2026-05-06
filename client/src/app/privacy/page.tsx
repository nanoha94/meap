import React from 'react';
import type { Metadata } from 'next';
import Image from 'next/image';
import Link from 'next/link';

import { LoginLinks } from '@/components';

export const metadata: Metadata = {
    title: 'プライバシーポリシー',
};

const Page = () => {
    return (
        <div className="min-h-screen bg-primary-background text-black">
            <header
                className="sticky top-0 z-10 bg-white backdrop-blur-sm"
                style={{ boxShadow: 'inset 0 -1px 3px 0 rgba(0, 0, 0, 10%)' }}>
                <div className="flex justify-center px-4 sm:px-6">
                    <div className="flex w-full max-w-5xl items-center justify-between gap-4 py-3">
                        <Link
                            href="/"
                            className="flex items-center gap-2 text-primary-main transition-opacity hover:opacity-80">
                            <Image
                                src="/images/meap-logo2.png"
                                alt="meap"
                                width={1224}
                                height={486}
                                className="w-auto h-[42px]"
                                loading="eager"
                            />
                        </Link>
                        <LoginLinks />
                    </div>
                </div>
            </header>

            <main className="flex justify-center px-4 py-12">
                <article className="w-full max-w-5xl ">
                    <h1 className="mb-8 text-2xl font-bold sm:text-3xl">
                        プライバシーポリシー
                    </h1>

                    <p className="mb-10 leading-relaxed">
                        屋号「nanoha code」（以下「当事業者」といいます。）は、当事業者が提供するサービス「meap」（以下「本サービス」といいます。）におけるお客様の個人情報の取扱いについて、以下のとおりプライバシーポリシー（以下「本ポリシー」といいます。）を定めます。
                    </p>

                    <Section title="1. お客様から取得する情報">
                        <p>
                            当事業者は、お客様から以下の情報を取得します。
                        </p>
                        <ul className="list-disc pl-6 [&>li:not(:last-child)]:mb-1">
                            <li>ユーザー名（お客様が任意で設定する表示名）</li>
                            <li>メールアドレス</li>
                            <li>
                                パスワード（暗号化したうえで保存し、当事業者は元のパスワードを参照することはできません）
                            </li>
                            <li>
                                外部サービス（Google
                                等）でのアカウント識別情報、表示名、メールアドレス、プロフィール画像その他、お客様が外部サービスのプライバシー設定により当事業者への開示を認めた情報
                            </li>
                            <li>
                                Cookie およびこれに類する技術（ローカルストレージ、セッションストレージ等）を用いて生成された識別情報
                            </li>
                            <li>
                                お客様がアップロードした画像。なお、画像のアップロード時に
                                Exif 情報（撮影日時・位置情報等のメタデータ）は自動的に削除します。
                            </li>
                            <li>
                                お客様が本サービス上に登録したデータ（レシピ、レシピの材料・手順、献立、買い物リスト等）
                            </li>
                            <li>
                                お問い合わせの際にお客様から提供される情報（ご連絡先、お問い合わせ内容等）
                            </li>
                        </ul>
                    </Section>

                    <Section title="2. 利用目的">
                        <p>当事業者は、取得した個人情報を以下の目的で利用します。</p>
                        <ul className="list-disc pl-6 [&>li:not(:last-child)]:mb-1">
                            <li>
                                本サービスに関する登録の受付、お客様の本人確認、認証のため
                            </li>
                            <li>本サービスの提供、維持、保護および改善のため</li>
                            <li>
                                お客様からのお問い合わせに対応するため
                            </li>
                            <li>
                                本サービスに関する各種ご案内・通知（メールアドレス認証、パスワードリセット、本サービスの変更・提供中止・終了・契約解除のご連絡、本ポリシーや利用規約の変更通知等）をお送りするため
                            </li>
                            <li>
                                当事業者の利用規約または法令に違反する行為に対応するため
                            </li>
                            <li>
                                不正利用の防止、調査および対応のため
                            </li>
                        </ul>
                    </Section>

                    <Section title="3. Cookie 等の利用">
                        <p>
                            当事業者は、本サービスの提供にあたり、Cookie およびこれに類する技術（ローカルストレージ、セッションストレージ等）を以下の目的で使用します。
                        </p>
                        <ul className="list-disc pl-6 [&>li:not(:last-child)]:mb-1">
                            <li>ログインセッションの維持</li>
                            <li>
                                CSRF（クロスサイトリクエストフォージェリ）攻撃の防止
                            </li>
                            <li>
                                お客様のサービス利用に関する設定（並び順、フィルタ条件等）の保持
                            </li>
                        </ul>
                        <p>
                            お客様は、ブラウザの設定により Cookie の受け入れを拒否することができますが、その場合、本サービスの一部または全部をご利用いただけなくなる可能性があります。
                        </p>
                    </Section>

                    <Section title="4. 安全管理のために講じた措置">
                        <p>
                            当事業者は、お客様の個人情報を安全に管理するため、以下の措置を講じています。
                        </p>
                        <ul className="list-disc pl-6 [&>li:not(:last-child)]:mb-1">
                            <li>
                                個人情報を取り扱う業務に関する基本方針および取扱規程の策定
                            </li>
                            <li>
                                個人情報を取り扱うシステムへのアクセス制御、認証情報の管理、通信経路の暗号化等の技術的安全管理措置
                            </li>
                            <li>
                                パスワードの暗号化（ハッシュ化）保存
                            </li>
                            <li>
                                個人情報の漏えい等の防止に関する従事者への教育・周知
                            </li>
                            <li>
                                外部からの不正アクセスまたは不正なソフトウェアからの保護
                            </li>
                        </ul>
                    </Section>

                    <Section title="5. 第三者提供">
                        <p>
                            当事業者は、法令に定める場合を除き、お客様の同意を得ずに個人情報を第三者に提供しません。ただし、以下の場合は、関連法令に従い、お客様の同意なく提供することがあります。
                        </p>
                        <ul className="list-disc pl-6 [&>li:not(:last-child)]:mb-1">
                            <li>個人データの取扱いを外部に委託する場合</li>
                            <li>
                                当事業者が合併、事業譲渡その他の事由により個人情報の取扱いを承継する場合
                            </li>
                            <li>
                                事業パートナーと共同利用する場合（具体的な共同利用がある場合は、その内容を別途公表します。）
                            </li>
                            <li>
                                その他、法律によって合法的に第三者提供が許されている場合
                            </li>
                        </ul>
                    </Section>

                    <Section title="6. 個人情報の取扱いの委託">
                        <p>
                            当事業者は、利用目的の達成に必要な範囲内において、個人情報の取扱いの全部または一部を第三者に委託することがあります。この場合、当事業者は、委託先の適格性を十分に審査し、契約等を通じて委託先に対して個人情報の適正な取扱いを義務付けます。
                        </p>
                    </Section>

                    <Section title="7. 保有個人データの開示等のご請求">
                        <p>
                            お客様は、当事業者が保有する自己の個人データについて、利用目的の通知、開示、内容の訂正・追加・削除、利用停止、第三者提供の停止のご請求をすることができます。ご請求は、本ポリシー末尾の「お問い合わせ窓口」までご連絡ください。法令に基づき、ご本人確認のうえで対応いたします。
                        </p>
                    </Section>

                    <Section title="8. アクセス解析ツール">
                        <p>
                            当事業者は、お客様のアクセス解析のために、「Googleアナリティクス」を使用しています。Googleアナリティクスは、トラフィックデータの収集のためにCookieを使用しています。トラフィックデータは匿名で収集されており、個人を特定するものではありません。Coockieを無効にすれば、これらの情報の収集を拒否することができます。詳しくはお使いのブラウザの設定をご確認ください。
                        </p>
                        <p>Googleアナリティクスについて、詳しくは以下からご確認ください。<br />
                            <a href="https://marketingplatform.google.com/about/analytics/terms/jp/" target="_blank" rel="noopener noreferrer" className="text-primary-main underline">https://marketingplatform.google.com/about/analytics/terms/jp/</a>
                        </p>
                    </Section>

                    <Section title="9. プライバシーポリシーの変更">
                        <p>
                            当事業者は、必要に応じて本ポリシーを変更することがあります。変更後のプライバシーポリシーは、本サービス上に掲載した時点から効力を生じるものとします。重要な変更については、本サービス上または登録されたメールアドレス宛にお知らせします。
                        </p>
                    </Section>

                    <Section title="10. お問い合わせ窓口">
                        <p>
                            本ポリシーに関するお問い合わせ、保有個人データの開示等のご請求は、以下の窓口までご連絡ください。
                        </p>
                        <div className="p-4 bg-white rounded-md shadow-card">
                            <dl className="grid grid-cols-[120px_1fr] items-start gap-x-3 gap-y-1">
                                <dt className="font-semibold">屋号</dt>
                                <dd className="font-mono">
                                    nanoha code
                                </dd>
                                <dt className="font-semibold">住所</dt>
                                <dd className="font-mono">
                                    〒980-0021 宮城県仙台市青葉区中央4丁目8-17 小林ビル1階
                                </dd>
                                <dt className="font-semibold">メールアドレス</dt>
                                <dd className="font-mono">
                                    {/* TODO: サポート用メールアドレスを記載 */}
                                    meap.support@example.com
                                </dd>
                            </dl>
                        </div>
                    </Section>

                    <p className="text-right text-base text-gray-main">
                        制定日：
                        <span>2026年05月02日</span>
                    </p>
                </article>
            </main>

            <footer
                className="bg-white py-8"
                style={{ boxShadow: 'inset 0 1px 3px 0 rgba(0, 0, 0, 10%)' }}>
                <div className="flex justify-center px-4 sm:px-6">
                    <div className="w-full text-center text-base text-gray-main">
                        © {new Date().getFullYear()} meap
                    </div>
                </div>
            </footer>
        </div>
    );
};

interface SectionProps {
    title: string;
    children: React.ReactNode;
}

const Section = ({ title, children }: SectionProps) => {
    return (
        <section className="mb-8">
            <h2 className="mb-3 text-lg font-bold sm:text-xl">{title}</h2>
            <div className="[&>*:not(:last-child)]:mb-3 leading-relaxed">
                {children}
            </div>
        </section>
    );
};

export default Page;
