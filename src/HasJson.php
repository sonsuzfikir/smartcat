<?php

namespace Drupal\tmgmt_smartcat;

trait HasJson
{
    protected function toJson($response): ?array
    {
        $content = $response->getBody()->getContents();

        if (!$this->isJson($content)) {
            return null;
        }

        return json_decode($content, true);
    }

    protected function isJson($string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}