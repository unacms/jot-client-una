/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup	Messenger Messenger
 * @ingroup	UnaModules
 * @{
 */

/**
 * FilePond files uploader integration
 */

;window.oMessengerUploader = class {
    constructor(oOptions) {
        this.aFiles = [];
        this.bClean = false;
        this.bLoading = false;
        this.oUploader = null;
        this.oOptions = oOptions;
        this.isReady = false;
        this.isBlockVersion = +this.setOption('is_block_version');
        this.sMainObject = this.setOption('main_object_name');
        this.oTarget = null;

        this.sInputName = this.setOption('input_name', 'filepond');
        this.sFileUrl = this.setOption('uploader_url');
        this.sTmpFileUrl = this.setOption('remove_temp_file_url');
        this.aRestrictedExtensions = this.setOption('restricted_extensions', []);
        this.maxFiles = this.setOption('number_of_files');
        this.onAddFilesCallback = this.setOption('onAddFilesCallback', null);
        this.onUpdateAttachments = this.setOption('onUpdateAttachments', null);
        this.onUploadingComplete = this.setOption('onUploadingComplete', null);
        this.maxFileSize = this.setOption('file_size') + 'MB';

        /* FilePond plugins begin*/
        FilePond.registerPlugin(
            FilePondPluginImagePreview,
            FilePondPluginMediaPreview,
            FilePondPluginFileValidateSize,
            FilePondPluginFileRename
        );

        this.init();
    }

    setOption(sName, mixedDefault = ''){
        return this.oOptions[sName] ? this.oOptions[sName] : mixedDefault;
    }

    init(){
        const _this = this;

        $(document).on('drop paste', (e) => {
            const { target } = e;
            _this.oTarget = target;
        });

        this.oUploader = FilePond.create($(`[name="${_this.sInputName}"]`).get(0),
        {
                server: {
                    url: `${_this.sFileUrl}`,
                    revert: null,
                },
                fileRenameFunction: file => (0 | Math.random() * 9e6).toString(36) + file.extension,
                beforeDropFile: function(oFile){
                    const sPreg = /\.(jpeg|jpg|gif|png)$/;

                    if (typeof oFile === 'string')
                        return sPreg.test(oFile);

                    return oFile instanceof File && oFile.type !== 'text/html';
                },
                beforeAddFile: ({ file }) => {
                    const { type } = file;
                    if (type){
                        const aTypes = type.split('/');
                        if (typeof aTypes[1] !== 'undefined' && ~_this.aRestrictedExtensions.indexOf(aTypes[1].toLowerCase())){
                            bx_alert(_t('_bx_messenger_file_type_is_not_allowed'));
                            return false;
                        }
                    }

                    if (_this.isBlockVersion && _this.oTarget !== null && !$(_this.oTarget).closest(_this.sMainObject).length)
                        return false;
                    
                    return !(file instanceof Blob && !(file instanceof File) && type === 'text/html');
                },
                allowFileRename: true,
                imagePreviewMaxHeight: 96,
                dropValidation: true,
                dropOnPage: true,
                allowMultiple: true,
                allowBrowse: true,
                dropOnElement: false,
                allowPaste: true,
                checkValidity: true,
                allowFileSizeValidation: true,
                maxFiles: this.maxFiles,
                acceptedFileTypes: [],
                labelInvalidField: _t('_bx_messenger_upload_invalid_file_type'),
                labelMaxFileSizeExceeded: _t('_bx_messenger_file_is_too_large_error'),
                labelMaxFileSize: _t('_bx_messenger_file_is_too_large_error_details'),
                labelFileProcessing: _t('_bx_messenger_uploading_file'),
                labelFileProcessingComplete: _t('_bx_messenger_upload_is_complete'),
                labelFileProcessingAborted: _t('_bx_messenger_upload_cancelled'),
                labelFileProcessingError: _t('_bx_messenger_invalid_server_response'),
                labelButtonRemoveItem: _t('_bx_messenger_uploading_remove_button'),
                maxFileSize: this.maxFileSize,
                onwarning: (oObject) => {
                    const { body, type } = oObject;
                    if (type === 'warning' && body === 'Max files')
                        bx_alert(_t('_bx_messenger_max_files_upload_error', _this.maxFiles));
                    else
                        console.log(oObject);
                },
                onaddfilestart: (file) => {
                    _this.bClean = false;
                    _this.bLoading = true;
					_this.isReady = false;
                },
                onprocessfiles: () => {
                    let iCount = 0;
                    _this.aFiles.map(function(oFile){
                        if (oFile.status === 5) iCount++;
                    });

                    if (typeof _this.onUploadingComplete === 'function' && iCount === _this.aFiles.length) {
                        const aProcessedFiles = _this.aFiles.map(({ file, status, source }) => ({ complete: +(status === 5), name: file.name, realname: source.name }));
                        _this.onUploadingComplete(_this.sInputName, aProcessedFiles, () => _this.oUploader.destroy());
                        _this.isReady = true;
                    }
                },
                onupdatefiles: function(files){
                    _this.aFiles = files;
                    _this.bLoading = false;

                    const aFilteredFiles = _this.aFiles.filter(({ fileType }) => fileType !== 'application/octet-stream');
                    if (typeof _this.onUpdateAttachments === 'function')
                        _this.onUpdateAttachments(!aFilteredFiles.length);

                },
                onremovefile: (error, oFile) => {
                    const { file } = oFile;
                    _this.bLoading = false;
                    if (!_this.bClean && file && file.name)
                        $.ajax({
                            url: _this.sTmpFileUrl,
                            type: 'DELETE',
                            data:  { name: file.name }
                        });
                },                
                onaddfile: (oFile) => {
                    if (typeof _this.onAddFilesCallback === 'function' && !_this.bClean)
                        _this.onAddFilesCallback();
                }
        });
    }

    getUploader(){
       const _this = this;
       return {
                isLoadingStarted:   () => +_this.oUploader.status === 3,
                isReady:            () => _this.isReady,
                getFiles:           () => _this.aFiles.filter((oFile) => oFile.status === 5).map(({ file }) => file.name),
                getAllFiles:        () => _this.aFiles.map(({ file, status, source }) => ({ complete: +(status === 5), name: file.name, realname: source.name })),
                clean:              () => {
                                            _this.bClean = true;
                                            if (typeof _this.onAddFilesCallback === 'function')
                                                _this.onAddFilesCallback();

                                             if (_this.isReady)
                                                _this.oUploader.removeFiles();
                                          },
                name:               () => _this.sInputName,
                move:               (sID) => $(`[name="${_this.sInputName}"]`).closest('.filepond--root').appendTo(sID),
                browse:             () => _this.oUploader.browse(),
                removeFiles:        () => _this.oUploader.removeFiles(),
                destroy :           () => _this.oUploader.destroy()
              };
    }
}