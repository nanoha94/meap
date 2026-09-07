<?php

namespace App\Helpers;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class CustomLogFormatter extends JsonFormatter
{
    public function __construct()
    {
        parent::__construct(JsonFormatter::BATCH_MODE_JSON, false, true);
    }

    public function format(LogRecord $record): string
    {
        // 基本的なレコード情報を取得
        $formatted = parent::format($record);

        // JSONをデコードして整形
        $data = json_decode($formatted, true);

        // オブジェクトや配列を展開して読みやすくする
        $data = $this->expandObjects($data);

        // 改行とインデントを追加してJSONを整形
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json . "\n";
    }

    private function expandObjects($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_object($value)) {
                    // オブジェクトの場合は、get_object_varsでプロパティを取得
                    $data[$key] = $this->expandObjects(get_object_vars($value));
                } elseif (is_array($value)) {
                    $data[$key] = $this->expandObjects($value);
                }
            }
        }

        return $data;
    }
}
