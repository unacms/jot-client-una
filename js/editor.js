/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup	Messenger Messenger
 * @ingroup	UnaModules
 * @{
 */

/**
 * Quill Editor integration
 */

;window.oMessengerEditor = class {
    constructor(oOptions) {
        this.oHtmlEditorObject = oOptions['selector'] ? oOptions['selector'] : '.editor';

        this.oHtmlSendButton = oOptions['button'] ? oOptions['button'] : '.send-button';
        this.oPlaceholder = oOptions['placeholder'] ? oOptions['placeholder'] : '.placeholder';

        this.onEnter = typeof oOptions['onEnter'] === 'function' && oOptions['onEnter'];
        this.onChange = typeof oOptions['onChange'] === 'function' && oOptions['onChange'];
        this.onUp = typeof oOptions['onUp'] === 'function' && oOptions['onUp'];
        this.showToolbar = typeof oOptions['showToolbar'] === 'function' && oOptions['showToolbar'];

        this.aToolbarSettings = [
            ['bold', 'italic', 'underline', 'strike', 'link'],
            ['blockquote', 'code-block'],
            [{ 'color': [] }, { 'background': [] }]
        ];

        if ($(this.oHtmlEditorObject).length){
            this.oEditor = null;
            this.init();
        }
    }

    init(){
        if (typeof Quill === 'undefined')
            bx_get_scripts(['quill/quill.js'], () => {
                 this.initEditor();
            });
        else
            this.initEditor();
    }

    setText(mixedValue){
        if (this.oEditor)
            this.oEditor.setText(mixedValue);
    }

    getText(){
        if (this.oEditor)
            this.oEditor.getText();
    }

    editor(){
        this.init();
        return this;
    }

    html(){
        return this.oEditor && this.oEditor.root && this.oEditor.root.innerHTML;
    }

    focus(){
        if (this.oEditor)
        this.oEditor.focus();
    }

    getContents(){
        return this.oEditor && this.oEditor.getContents();
    }

    get length(){
       return this.oEditor.getLength();
    }

    setContents(aValues){
        if (this.oEditor)
            this.oEditor.setContents(!Array.isArray(aValues) ? [] : aValues);
    }

    getText(){
        return this.oEditor && this.oEditor.getText();
    }

    addToCurrentPosition(sText){
        if (!this.oEditor)
            return false;

        let range = this.oEditor.getSelection(true);
        this.oEditor.insertText(range.index, sText, Quill.sources.USER);
        this.oEditor.setSelection(range.index + sText.length, 1, Quill.sources.API);
    }

    initEditor(){
        const QuillClipboard = Quill.import('modules/clipboard');
        const _this = this;
        class Clipboard extends QuillClipboard {
           onPaste (event) {
             super.onPaste(event);
             if (event.clipboardData.getData('text/plain').length > 0)
                $(this.oHtmlSendButton).fadeIn();
           }
        }

        Quill.register('modules/clipboard', Clipboard, true);

        this.oEditor = new Quill(this.oHtmlEditorObject, {
                placeholder: this.oPlaceholder,
                theme: 'bubble',
                bounds: this.oHtmlEditorObject,
                modules: {
                    toolbar: _this.showToolbar() && _this.aToolbarSettings,
                    clipboard: {
                        matchers: [
                            ['IMG', () => {
                                return { ops: [] }
                            }]
                        ]
                    },
                    keyboard: {
                        bindings: {
                            enter: {
                                key: 13,
                                shiftKey: false,
                                handler: () => this.onEnter()
                            },
                            up: {
                                key: 38,
                                shiftKey: false,
                                handler: () => this.onUp()
                            }
                        }
                    }
                }
            });

           this.oEditor.on('text-change', function(delta, oldDelta, source) {
                  if (source === 'user')
                        _this.onChange();
        });
    }
}