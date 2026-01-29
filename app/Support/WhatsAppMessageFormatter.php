<?php

namespace App\Support;

class WhatsAppMessageFormatter
{
    public static function format(string $message): string
    {
        if ($message === '') {
            return $message;
        }

        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $parts = preg_split('/(https?:\/\/\S+)/i', $message, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (! $parts) {
            return $message;
        }

        $result = '';

        foreach ($parts as $index => $part) {
            if (preg_match('/^https?:\/\/\S+$/i', $part) === 1) {
                $result = rtrim($result, " \t");

                if (preg_match('/\n*$/', $result, $match) === 1) {
                    $trailingNewlines = strlen($match[0]);
                    if ($trailingNewlines < 2) {
                        $result .= str_repeat("\n", 2 - $trailingNewlines);
                    }
                } else {
                    $result .= "\n\n";
                }

                $result .= $part;

                $nextPart = $parts[$index + 1] ?? '';
                if ($nextPart === '' || ! str_starts_with($nextPart, "\n")) {
                    $result .= "\n";
                }

                continue;
            }

            $result .= $part;
        }

        return $result;
    }
}
