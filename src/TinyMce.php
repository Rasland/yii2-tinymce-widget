<?php
/**
 * @copyright Copyright (c) 2013-2017 2amigOS! Consulting Group LLC
 * @link http://2amigos.us
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace dosamigos\tinymce;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 *
 * TinyMCE renders a tinyMCE js plugin for WYSIWYG editing.
 *
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 * @link http://www.ramirezcobos.com/
 * @link http://www.2amigos.us/
 */
class TinyMce extends InputWidget
{
    /**
     * @var string the language to use. Defaults to null (en).
     */
    public $language;
    /**
     * @var array the options for the TinyMCE JS plugin.
     * Please refer to the TinyMCE JS plugin Web page for possible options.
     * @see http://www.tinymce.com/wiki.php/Configuration
     */
    public $clientOptions = [];
    /**
     * @var bool whether to set the on change event for the editor. This is required to be able to validate data.
     * @see https://github.com/2amigos/yii2-tinymce-widget/issues/7
     */
    public $triggerSaveOnBeforeValidateForm = true;

    /**
     * @return string
     */
    public function getInputId()
    {
        return $this->options['id'];
    }

    /**
     * @return string
     */
    protected function getInlineId()
    {
        return $this->getInputId() . "-inline";
    }

    /**
     * @return bool
     */
    protected function isInline()
    {
        return isset($this->clientOptions['inline']) && $this->clientOptions['inline'] === true;
    }

    /**
     * @return string
     */
    protected function renderInline()
    {
        return Html::tag('div', '', ['id' => $this->getInlineId()]);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $inline = '';
        if ($this->isInline()) {
            $this->options['style'] = "display: none;";
            $this->clientOptions['hidden_input'] = false;
            $inline = $this->renderInline();
        }
        $result = $this->hasModel() ?
            Html::activeTextarea($this->model, $this->attribute, $this->options) :
            Html::textarea($this->name, $this->value, $this->options);
        $this->registerClientScript();
        return $result . $inline;
    }

    /**
     * Registers tinyMCE js plugin
     */
    protected function registerClientScript()
    {
        $js = [];
        $view = $this->getView();

        TinyMceAsset::register($view);

        $editorId = $this->getInputId();
        if ($this->isInline()) {
            $hiddenInputId = $editorId;
            $editorId = $this->getInlineId();
            $js[] = "$('#{$editorId}').parents('form').on('afterValidate', function() { $('#{$hiddenInputId}').val(tinyMCE.get('{$editorId}').getContent()); });";
        }

        $this->clientOptions['selector'] = "#{$editorId}";

        // @codeCoverageIgnoreStart
        if ($this->language !== null && $this->language !== 'en') {
            $langFile = "langs/{$this->language}.js";
            $langAssetBundle = TinyMceLangAsset::register($view);
            $langAssetBundle->js[] = $langFile;
            $this->clientOptions['language_url'] = $langAssetBundle->baseUrl . "/{$langFile}";
        }
        // @codeCoverageIgnoreEnd

        $options = Json::encode($this->clientOptions);

        $js[] = "tinymce.init($options);";
        if ($this->triggerSaveOnBeforeValidateForm) {
            $js[] = "$('#{$editorId}').parents('form').on('beforeValidate', function() { tinymce.triggerSave(); });";
        }
        $view->registerJs(implode("\n", $js));
    }
}
