<?php

return [
    'length' => (int) env('OTP_LENGTH', 6),          // jumlah digit
    'expires_in' => (int) env('OTP_EXPIRES_IN', 10),     // menit
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),    // maks percobaan salah
    'resend_cooldown' => (int) env('OTP_RESEND_COOLDOWN', 60), // detik
];
