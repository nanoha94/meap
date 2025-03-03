<?php

namespace App\Console\Commands;

use App\Mail\TestMail as MailTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:mail {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email for development';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? config('mail.from.address');

        try {
            Mail::to($email)->send(new MailTestMail());
            Log::info('メール送信成功: ' . $email);
            $this->info('メールを送信しました: ' . $email);
        } catch (\Exception $e) {
            Log::error('メール送信エラー: ' . $e->getMessage());
            $this->error('メール送信エラー: ' . $e->getMessage());
        }
    }
}
