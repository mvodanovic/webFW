<?php

namespace WebFW\Core\Classes\HTML\Base;

abstract class BaseHTMLItem
{
    protected $id = null;
    protected $image;
    protected $value;
    protected $classes = array();
    protected $styles = array();
    protected $innerHTMLElements = array();
    protected $attributes = array();
    protected $hasClosingTag = true;
    protected $tagName = null;
    protected $skipInnerHTMLDecoration = false;

    protected $innerHTML = '';
    protected $attributesHTML = '';

    const IMAGE_SAVE = '/static/images/webfw/icons/save.png';
    const IMAGE_APPLY = '/static/images/webfw/icons/apply.png';
    const IMAGE_CANCEL = '/static/images/webfw/icons/cancel.png';
    const IMAGE_DELETE = '/static/images/webfw/icons/delete.png';
    const IMAGE_COPY = '/static/images/webfw/icons/copy.png';
    const IMAGE_PRINT = '/static/images/webfw/icons/print.png';
    const IMAGE_ADD = '/static/images/webfw/icons/add.png';
    const IMAGE_EDIT = '/static/images/webfw/icons/edit.png';
    const IMAGE_IMPORT = '/static/images/webfw/icons/import.png';
    const IMAGE_EXPORT = '/static/images/webfw/icons/export.png';
    const IMAGE_NOTICE = '/static/images/webfw/icons/notice.png';
    const IMAGE_SEARCH = '/static/images/webfw/icons/search.png';
    const IMAGE_HELP = '/static/images/webfw/icons/help.png';
    const IMAGE_LOGIN = '/static/images/webfw/icons/login.png';
    const IMAGE_LOGOUT = '/static/images/webfw/icons/logout.png';

    public function __construct($value)
    {
        $this->setValue($value);
    }

    public function setID($id)
    {
        $this->id = $id;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function addClass($class)
    {
        $this->classes[] = $class;
    }

    public function addStyle($key, $value)
    {
        $this->styles[$key] = $value;
    }

    public function addCustomAttribute($key, $value)
    {
        $this->attributes[$key] = $key . '="' . $value . '"';
    }

    public function prepareHTMLChunks()
    {
        if ($this->skipInnerHTMLDecoration === true) {
            $this->innerHTMLElements[] = htmlspecialchars($this->value);
        } else {
            if ($this->image !== null) {
                $this->innerHTMLElements[] = '<img src="' . $this->image . '" alt="" />';
            }

            if ($this->value !== null) {
                $this->innerHTMLElements[] = '<span>' . htmlspecialchars($this->value) . '</span>';
            }
        }

        $this->innerHTML = implode('', $this->innerHTMLElements);

        if ($this->id !== null) {
            $this->addCustomAttribute('id', $this->id);
        }

        $styles = array();
        foreach ($this->styles as $key => $value) {
            $styles[$key] = $key . ': ' . $value . ';';
        }
        if (!empty($styles)) {
            $this->addCustomAttribute('style', implode(' ', $styles));
        }

        if (!empty($this->classes)) {
            $this->addCustomAttribute('class', implode(' ', $this->classes));
        }

        $this->attributesHTML = ' ' . implode(' ', $this->attributes);
    }

    public function parse()
    {
        $this->prepareHTMLChunks();

        if ($this->hasClosingTag === true) {
            return '<' . $this->tagName
                . $this->attributesHTML
                . '>'
                . $this->innerHTML
                . '</' . $this->tagName . '>';
        } else {
            return '<' . $this->tagName
                . $this->attributesHTML
                . ' />';
        }
    }

    public function getID()
    {
        return $this->id;
    }
}