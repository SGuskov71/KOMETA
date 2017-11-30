CSSLoad(_URLProjectRoot + 'Lib/upload/css/upload.css');

Ext.Loader.setPath({
  'Ext.ux': _URLProjectRoot + 'Lib'
});
Ext.define('KOMETA.Operation.File_operation', {
  Upload: function (Grid, Operation) { //показывает диалог отгрузки файлов для регистрации файла в БД
    var uploadPanel = Ext.create('Ext.ux.upload.Panel', {
      uploaderOptions: {
        url: _URLProjectRoot + 'FileUpload/FileUploadMd5NameBackEnd.php'
      },
      filenameEncoder: 'Ext.ux.upload.header.Base64FilenameEncoder',
      synchronous: true
    });

    var uploadDialog = Ext.create('Ext.ux.upload.Dialog', {
      dialogTitle: 'Загрузка файлов с регистрацией в БД',
      panel: uploadPanel
    });

    uploadDialog.addListener('uploadcomplete', function (uploadPanel, manager, items, errorCount) {
      // this.uploadComplete(items);
      if (!errorCount) {
        Grid.ReloadGrid();
        ;
      }
    });
    //  uploadDialog.addEvents('GridRefresh');
    uploadDialog.show();
    return uploadDialog;
  }
  ,
  FileUploadDialogServerDir: function () { //показывает диалог отгрузки файлов загрузки в указанную директорию сервера без регистрации файла в БД
    var uploadPanel = Ext.create('Ext.ux.upload.Panel', {
      uploadUrl: _URLProjectRoot + 'FileUpload/FileUploadServerDirBackEnd.php',
      filenameEncoder: 'Ext.ux.upload.header.Base64FilenameEncoder',
      uploadParams: {ServerDir: ''},
      synchronous: true
    });

    var uploadDialog = Ext.create('Ext.ux.upload.Dialog', {
      dialogTitle: 'Загрузка файлов в директорию сервера',
      panel: uploadPanel
    });

    var DirStore = Ext.create('Ext.data.Store', {
      fields: ['name'],
      data: []
    });

    FileUpload_class.GetServerDirListBackEnd(function (response, opts) {
      data = response;
      DirStore.loadData(data, false);
      var recordSelected = uploadDialog.DirCombobox.getStore().getAt(0);
      if (recordSelected != undefined)
        uploadDialog.DirCombobox.setValue(recordSelected.get('name'));
    });

    uploadDialog.DirCombobox = Ext.create('Ext.form.ComboBox', {
      store: DirStore,
      queryMode: 'local',
      displayField: 'name',
      valueField: 'name',
      triggerAction: 'all',
      selectOnFocus: true,
      editable: false,
      listeners: {
        afterrender: function (combo) {
          var recordSelected = combo.getStore().getAt(0);
          if (recordSelected != undefined)
            combo.setValue(recordSelected.get('name'));
        },
        change: function (field, newValue, oldValue, eOpts) {
          var uploader = uploadDialog.panel.uploadManager.getUploader();
          if (uploader != undefined)
            uploader.setParams({ServerDir: newValue})
          else
            Ext.MessageBox.alert('Загрузка файлов на сервер', 'Нетуть аплодера');
        }
      }
    });

    uploadDialog.addDocked({
      xtype: 'toolbar',
      dock: 'top',
      items: [
        '->',
        'Директория сервера',
        '  ',
        uploadDialog.DirCombobox
      ]
    });

    uploadDialog.show();
  }
  ,
  Delete: function (Grid, Operation) { //удаляет ссылки на файл в БД и сам файл при отсутствии ссылок
    var sm = Grid.getSelectionModel().getSelection()[0].raw;
    if ((sm == undefined) || (sm == null)) {
      Ext.MessageBox.alert('Не выбрана запись');
      return;
    }
    var _codeFile = sm['code'];

    var win = Ext.Msg.show({title: 'Удаление файла ' + sm['filename'],
      msg: 'Удалить файл?',
      buttons: Ext.Msg.YESNO,
      closable: false,
      fn: function (btn) {
        FileUpload_class.DeleteFileByMd5Name(_codeFile, function (response) {
          var result = response;
          if ((result.success === false) && (result.result == 're_connect')) {
            Ext.MessageBox.alert('Подключение', result.msg);
                                window.onbeforeunload = null;    
            findFirstWindow().window.location.href = __first_page;
            return;
          }
          if (result.success) {
            Grid.ReloadGrid();
            Ext.MessageBox.alert("Результат выполнения ", result.msg);
          } else {
            Ext.MessageBox.alert("Ошибка удаления: " + result.msg + ' ' + result.result);
          }
        });
      } // fn
    }); // show
    //win.addEvents('GridRefresh');
    return win;
  }
  ,
  DownloadFileByServerName: function (serverFileName, filename, filetype) {//загружает файл по его полному имени на сервере
    Ext.create('Ext.form.Panel', {
      standardSubmit: true,
      url: _URLProjectRoot + 'FileUpload/FileDownloadBackEnd.php'
    }).submit({params: {DownloadFile: true,
        serverFileName: serverFileName,
        filename: filename,
        filetype: filetype
      }});
  }
  ,
  Download: function (Grid, Operation) {//загружает файл по его хэш имени с проверкой регистрации в БД
    var me = this;
    var sm = Grid.getSelectionModel().getSelection()[0].raw;
    if ((sm == undefined) || (sm == null)) {
      Ext.MessageBox.alert('Не выбрана запись');
      return;
    }
    var _codeFile = sm['code'];
    FileUpload_class.DownloadFileByMd5Name(_codeFile, function (response, options) {
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
                                window.onbeforeunload = null;    
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        Ext.MessageBox.alert("Результат выполнения ", result.msg);
//двойной вызов сделан через жопу чтоб был возврат ошибок от сервера, т.к. аджакс колл неи дает вернуть поток через резалт
        me.DownloadFileByServerName(result.result.serverFileName, result.result.filename, result.result.filetype);
      } else {
        Ext.MessageBox.alert("Ошибка загрузки: " + result.msg + ' ' + result.result);
      }
    });
  }
});