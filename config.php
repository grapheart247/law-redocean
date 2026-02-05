<?php
// اس فائل کو براہ راست براؤزر میں کھولنے سے روکنے کے لیے
if (count(get_included_files()) <= 1) {
    exit("Direct access denied.");
}

return [
    'secret_key' => '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',
    'db_pass'    => 'your_password_here',
    'app_id'     => 'law-redocean'
];
```

#### اہم ہدایات (Security Tips):
1. **.gitignore:** اگر آپ گٹ استعمال کر رہے ہیں تو اپنی `.env` فائل کو کبھی بھی پش (Push) نہ کریں۔
2. **Permissions:** سرور پر اپنی کنفگ فائل کی پرمیشنز کو `600` یا `640` پر رکھیں تاکہ کوئی دوسرا صارف اسے نہ پڑھ سکے۔
