<?php

namespace WebFW\Core\Classes\HTML;

use \WebFW\Core\Classes\HTML\Base\BaseFormItem;

class Option extends BaseFormItem
{
    protected $tagName = 'option';
    protected $skipInnerHTMLDecoration = true;

    public function __construct($selected = false, $value = null, $caption = null, $class = null, $id = null)
    {
        parent::__construct(null, $caption, $id);

        if ($class !== null) {
            $this->classes[] = $class;
        }

        if ($selected === true) {
            $this->addCustomAttribute('selected', $selected);
        }

        if ($value !== null) {
            $this->addCustomAttribute('value', $value);
        }
    }

    public static function get($selected = false, $value = null, $caption = null, $class = null, $id = null)
    {
        $optionObject = new static($selected, $value, $caption, $class, $id);
        return $optionObject->parse();
    }
}