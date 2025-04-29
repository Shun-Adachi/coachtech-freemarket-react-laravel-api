<?php
// config/payment.php

return [
    // デフォルトの支払い方法 ID
    'default_method_id' => 1,

    // 支払い方法一覧（必要に応じてラベルも持たせる）
    'methods' => [
        1 => 'コンビニ払い',
        2 => 'カード支払い',
    ],
];