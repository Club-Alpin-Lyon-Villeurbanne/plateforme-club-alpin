import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('textarea.ckeditor').forEach((el) => {
        const configData = {
        toolbar: {
        items: [
        'undo',
        'redo',
        '|',
        'heading',
        'style',
        '|',
        'bold',
        'italic',
        'underline',
        'strikethrough',
        'subscript',
        'superscript',
        'removeFormat',
        '|',
        'specialCharacters',
        'link',
        'insertImage',
        'mediaEmbed',
        'insertTable',
        'blockQuote',
        '|',
        'alignment',
        '|',
        'bulletedList',
        'numberedList',
        'outdent',
        'indent'
        ],
        shouldNotGroupWhenFull: true
        },
        // plugins: [
        //     Alignment,
        //     Autoformat,
        //     AutoImage,
        //     Autosave,
        //     BlockQuote,
        //     Bold,
        //     CloudServices,
        //     Essentials,
        //     GeneralHtmlSupport,
        //     Heading,
        //     ImageBlock,
        //     ImageCaption,
        //     ImageInline,
        //     ImageInsert,
        //     ImageInsertViaUrl,
        //     ImageResize,
        //     ImageStyle,
        //     ImageTextAlternative,
        //     ImageToolbar,
        //     ImageUpload,
        //     Indent,
        //     IndentBlock,
        //     Italic,
        //     Link,
        //     LinkImage,
        //     List,
        //     MediaEmbed,
        //     Paragraph,
        //     PasteFromOffice,
        //     RemoveFormat,
        //     SimpleUploadAdapter,
        //     SpecialCharacters,
        //     SpecialCharactersArrows,
        //     SpecialCharactersCurrency,
        //     SpecialCharactersEssentials,
        //     SpecialCharactersLatin,
        //     SpecialCharactersMathematical,
        //     SpecialCharactersText,
        //     Strikethrough,
        //     Style,
        //     Subscript,
        //     Superscript,
        //     Table,
        //     TableCaption,
        //     TableCellProperties,
        //     TableColumnResize,
        //     TableProperties,
        //     TableToolbar,
        //     TextTransformation,
        //     Underline
        // ],
        heading: {
        options: [
        {
          model: 'paragraph',
          title: 'Paragraph',
          class: 'ck-heading_paragraph'
        },
        {
          model: 'heading1',
          view: 'h1',
          title: 'Heading 1',
          class: 'ck-heading_heading1'
        },
        {
          model: 'heading2',
          view: 'h2',
          title: 'Heading 2',
          class: 'ck-heading_heading2'
        },
        {
          model: 'heading3',
          view: 'h3',
          title: 'Heading 3',
          class: 'ck-heading_heading3'
        },
        {
          model: 'heading4',
          view: 'h4',
          title: 'Heading 4',
          class: 'ck-heading_heading4'
        },
        {
          model: 'heading5',
          view: 'h5',
          title: 'Heading 5',
          class: 'ck-heading_heading5'
        },
        {
          model: 'heading6',
          view: 'h6',
          title: 'Heading 6',
          class: 'ck-heading_heading6'
        }
        ]
        },
        htmlSupport: {
        allow: [
        {
          name: /^.*$/,
          styles: true,
          attributes: true,
          classes: true
        }
        ]
        },
        image: {
        toolbar: [
        'toggleImageCaption',
        'imageTextAlternative',
        '|',
        'imageStyle:inline',
        'imageStyle:wrapText',
        'imageStyle:breakText',
        '|',
        'resizeImage'
        ]
        },
        language: 'fr',
        licenseKey: 'GPL',
        link: {
        addTargetToExternalLinks: true,
        defaultProtocol: 'https://',
        decorators: {
        toggleDownloadable: {
          mode: 'manual',
          label: 'Downloadable',
          attributes: {
              download: 'file'
          }
        }
        }
        },
        placeholder: '',
        style: {
        definitions: [
        {
          name: 'Article category',
          element: 'h3',
          classes: ['category']
        },
        {
          name: 'Title',
          element: 'h2',
          classes: ['document-title']
        },
        {
          name: 'Subtitle',
          element: 'h3',
          classes: ['document-subtitle']
        },
        {
          name: 'Info box',
          element: 'p',
          classes: ['info-box']
        },
        {
          name: 'CTA Link Primary',
          element: 'a',
          classes: ['button', 'button--green']
        },
        {
          name: 'CTA Link Secondary',
          element: 'a',
          classes: ['button', 'button--black']
        },
        {
          name: 'Marker',
          element: 'span',
          classes: ['marker']
        },
        {
          name: 'Spoiler',
          element: 'span',
          classes: ['spoiler']
        }
        ]
        },
        table: {
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
        }
        };

        ClassicEditor
            .create(el, {
                language: 'fr',
                // toolbar: {
                //     items: [
                //         'undo',
                //         'redo',
                //         '|',
                //         'heading',
                //         'style',
                //         '|',
                //         'bold',
                //         'italic',
                //         'underline',
                //         'strikethrough',
                //         'subscript',
                //         'superscript',
                //         'removeFormat',
                //         '|',
                //         'specialCharacters',
                //         'link',
                //         'insertImage',
                //         'mediaEmbed',
                //         'insertTable',
                //         'blockQuote',
                //         '|',
                //         'alignment',
                //         '|',
                //         'bulletedList',
                //         'numberedList',
                //         'outdent',
                //         'indent'
                //     ],
                //     shouldNotGroupWhenFull: true
                // },
                // image: {
                //     toolbar: ['imageTextAlternative', 'imageStyle:full', 'imageStyle:side']
                // },
                // table: {
                //     contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
                // },
            })
            .then(editor => {
                el._ckeditorInstance = editor;
            })
            .catch(error => console.error(error))
        ;
    });
});
