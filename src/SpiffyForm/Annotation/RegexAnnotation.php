<?php

namespace SpiffyForm\Annotation;

abstract class RegexAnnotation extends JsonAnnotation
{
    public function parseRegex($regex, $string)
    {
        if (preg_match($regex, $string, $matches)) {
            return $matches;
        }
        return null;
    }
}