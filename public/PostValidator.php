<?php

declare(strict_types=1);

final class PostValidator
{
    private const string LINK_TAG = 'a';
    private const array ALLOWED_TAGS = ['a', 'code', 'i', 'strike', 'strong'];
    private const array ALLOWED_LINK_ATTRIBUTES = ['href', 'title'];

    private const string UTF8_PATTERN = '//u';
    private const string CLOSE_TAG_PATTERN = '/^<\/([a-z]+)\s*>$/';
    private const string OPEN_TAG_PATTERN = '/^<([a-z]+)(.*)>$/s';
    private const string SELF_CLOSING_TAG_PATTERN = '/\/\s*$/';
    private const string ENTITY_PATTERN = '&(?:amp|lt|gt|quot|apos|#[0-9]+|#x[0-9a-fA-F]+);';
    private const string INVALID_ENTITY_PATTERN = '/&(?!amp;|lt;|gt;|quot;|apos;|#[0-9]+;|#x[0-9a-fA-F]+;)/';
    private const string ATTRIBUTE_PATTERN = '/^([a-z]+)\s*=\s*('
    . '"(?:[^<&"]|' . self::ENTITY_PATTERN . ')*"'
    . '|'
    . "'(?:[^<&']|" . self::ENTITY_PATTERN . ")*'"
    . ')\s*/';

    function validatePost($post): bool
    {
        if (!is_string($post)) {
            return false;
        }

        return PostValidator::validate($post);
    }

    public static function validate(string $post): bool
    {
        if (preg_match(self::UTF8_PATTERN, $post) !== 1) {
            return false;
        }

        $stack = [];
        $position = 0;
        $length = strlen($post);

        while ($position < $length) {
            $tagStart = strpos($post, '<', $position);

            if ($tagStart === false) {
                $text = substr($post, $position);

                return self::isValidText($text) && empty($stack);
            }

            $text = substr($post, $position, $tagStart - $position);

            if (!self::isValidText($text)) {
                return false;
            }

            $tagEnd = self::findTagEnd($post, $tagStart);

            if ($tagEnd === null) {
                return false;
            }

            $tag = substr($post, $tagStart, $tagEnd - $tagStart + 1);

            if (!self::isValidTag($tag, $stack)) {
                return false;
            }

            $position = $tagEnd + 1;
        }

        return empty($stack);
    }

    private static function isValidText(string $text): bool
    {
        if ($text === '') {
            return true;
        }

        return preg_match(self::INVALID_ENTITY_PATTERN, $text) === 0;
    }

    private static function findTagEnd(string $post, int $tagStart): ?int
    {
        $quote = null;
        $length = strlen($post);

        for ($i = $tagStart + 1; $i < $length; $i++) {
            $char = $post[$i];

            if ($quote !== null) {
                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === '<') {
                return null;
            }

            if ($char === '>') {
                return $i;
            }
        }

        return null;
    }

    private static function isValidTag(string $tag, array &$stack): bool
    {
        if (preg_match(self::CLOSE_TAG_PATTERN, $tag, $matches)) {
            $tagName = $matches[1];

            if (!in_array($tagName, self::ALLOWED_TAGS, true)) {
                return false;
            }

            if (empty($stack) || end($stack) !== $tagName) {
                return false;
            }

            array_pop($stack);

            return true;
        }

        if (!preg_match(self::OPEN_TAG_PATTERN, $tag, $matches)) {
            return false;
        }

        $tagName = $matches[1];
        $attributes = $matches[2];

        if (!in_array($tagName, self::ALLOWED_TAGS, true)) {
            return false;
        }

        if (preg_match(self::SELF_CLOSING_TAG_PATTERN, $attributes)) {
            return false;
        }

        if ($tagName === self::LINK_TAG) {
            if (!self::isValidLinkAttributes($attributes)) {
                return false;
            }
        } elseif (trim($attributes) !== '') {
            return false;
        }

        $stack[] = $tagName;

        return true;
    }

    private static function isValidLinkAttributes(string $attributes): bool
    {
        $attributes = ltrim($attributes);
        $foundAttributes = [];

        while ($attributes !== '') {
            if (!preg_match(self::ATTRIBUTE_PATTERN, $attributes, $matches)) {
                return false;
            }

            $name = $matches[1];

            if (!in_array($name, self::ALLOWED_LINK_ATTRIBUTES, true)) {
                return false;
            }

            if (isset($foundAttributes[$name])) {
                return false;
            }

            $foundAttributes[$name] = true;
            $attributes = substr($attributes, strlen($matches[0]));
        }

        return true;
    }
}