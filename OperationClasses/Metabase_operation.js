Ext.define('KOMETA.Operation.Metabase_operation', {
  ImportMetaDefinition: function (Grid, Operation) {
    var me = this;
    Ext.MessageBox.wait({
      msg: 'Выполняется загрузка метаописания.\r Пожалуйста подождите.',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Metabase_class.ImportMetaDefinition(function (response) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success === true) {
        buildW_desktop_report_print('ImportMetaDefinition'
                , result.result, 'Результат загрузки метаописания', '', ''
                , function (response) {


                  Ext.MessageBox.show({
                    title: 'Перезагрузка',
                    msg: 'Для продолжения работы необходимо перезагрузить приложение!',
                    buttons: Ext.MessageBox.YESNO,
                    buttonText: {
                      yes: "Выполнить сейчас",
                      no: "Выполнить позже"
                    },
                    fn: function (btn) {
                      if (btn == "yes") {
                        window.onbeforeunload = null;
                        findFirstWindow().window.location.reload();
                      }
                    }
                  });
                })
      }
      else {
        Ext.MessageBox.alert("Ошибка загрузки метаописания ", response.msg);

      }
    }
    )
  }
  ,
  ImportXSD: function (Grid, Operation) {
    Ext.MessageBox.wait({
      msg: 'Загрузка схем входной информации, ждите... ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Metabase_class.ImportXSD(function (response) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success === true) {
        buildW_desktop_report_print('ImportXSD'
                , result.result, 'Результат схем входной информации', '', ''
                , function (response) {
                  Ext.MessageBox.show({
                    title: 'Автоимпорт',
                    msg: '',
                    buttons: Ext.MessageBox.YESNO,
                    buttonText: {
                      yes: "Запустить",
                      no: "Выход"
                    },
                    fn: function (btn) {
                      if (btn == "yes")
                        alert('11111');
                    }
                  })


                }
        )

      } else {
        Ext.MessageBox.alert("Ошибка загрузки метаописания ", response.msg);
      }

    })
  }
  ,
  ImportAppTask: function (Grid, Operation) {
    Ext.MessageBox.wait({
      msg: 'Выполняется загрузка меню приложения, Пожалуйста подождите.',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Metabase_class.ImportTask(function (response) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success === false) {
        if ((result.result == '') || (result.result == undefined))
          Ext.MessageBox.alert("Результат загрузки пользовательского меню", response.msg);
        else
          buildW_desktop_report_print('ImportTask'
                  , result.result, response.msg, '', '')

      } else {
        Ext.MessageBox.alert("Результат загрузки пользовательского меню", response.msg);
        Ext.MessageBox.show({
          title: 'Перезагрузка',
          msg: 'Для продолжения работы необходимо перезагрузить приложение!',
          buttons: Ext.MessageBox.YESNO,
          buttonText: {
            yes: "Выполнить сейчас",
            no: "Выполнить позже"
          },
          fn: function (btn) {
            if (btn == "yes") {
              window.onbeforeunload = null;
              findFirstWindow().window.location.reload();
            }
          }
        });

      }

    })
  }
  ,
  ImportSysTask: function (Grid, Operation) {
    Ext.MessageBox.wait({
      msg: 'Загрузка системного меню, ждите... ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Metabase_class.ImportSysTask(function (response) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success === false) {
        buildW_desktop_report_print('ImportSysTask'
                , result.result, 'Результат загрузки системного меню', '', '')

      } else {
        Ext.MessageBox.alert("Результат загрузки системного меню", response.msg);
        Ext.MessageBox.show({
          title: 'Перезагрузка',
          msg: 'Для продолжения работы необходимо перезагрузить приложение!',
          buttons: Ext.MessageBox.YESNO,
          buttonText: {
            yes: "Выполнить сейчас",
            no: "Выполнить позже"
          },
          fn: function (btn) {
            if (btn == "yes") {
              window.onbeforeunload = null;
              findFirstWindow().window.location.reload();
            }
          }
        });

      }

    })
  }
  ,
  ImportAllFiles: function (Grid, Operation) {
    // Загрузка файлов приложения
    Ext.MessageBox.wait({
      msg: 'Загрузка файлов, ждите... ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Metabase_class.ImportFiles(function (response) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success === true) {
        Ext.MessageBox.alert("Результат загрузки файлов", response.msg);
        if (Grid != undefined) {
          Grid.ReloadGrid();
        }
      } else {
        Ext.MessageBox.alert("Результат загрузки файлов", response.msg);
      }

    })
  }
  ,
  ImportAppFiles: function (Grid, Operation) {
    // Загрузка файлов системных из каталога автозагрузки метабазы
    Ext.MessageBox.wait({
      msg: 'Регистрация файлов приложения для загрузки, ждите... ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Metabase_class.RegistrationInputFile(2, function (response) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success === true) {
        Ext.MessageBox.wait({
          msg: 'Загрузка файлов приложения, ждите... ждите...',
          width: 300,
          wait: true,
          waitConfig: {interval: 100}
        });
        Metabase_class.ImportFiles(function (response) {
          Ext.MessageBox.hide();
          var result = response;
          if ((result.success === false) && (result.result == 're_connect')) {
            Ext.MessageBox.alert('Подключение', result.msg);
            window.onbeforeunload = null;
            findFirstWindow().window.location.href = __first_page;
            return;
          }
          if (result.success === true) {
            Ext.MessageBox.alert("Результат загрузки файлов", response.msg);
          } else {
            Ext.MessageBox.alert("Результат загрузки файлов", response.msg);
          }

        })
      } else {
        Ext.MessageBox.alert("Результат регистрации файлов для загрузки", response.msg);
      }

    })
  }
  , ImportSysFiles: function (Grid, Operation) {
    // Загрузка файлов системных из каталога автозагрузки метабазы
    Ext.MessageBox.wait({
      msg: 'Регистрация файлов метабазы для загрузки, ждите... ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Metabase_class.RegistrationInputFile(1, function (response) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success === true) {
        Ext.MessageBox.wait({
          msg: 'Загрузка файлов метабазы, ждите... ждите...',
          width: 300,
          wait: true,
          waitConfig: {interval: 100}
        });
        Metabase_class.ImportFiles(function (response) {
          Ext.MessageBox.hide();
          var result = response;
          if ((result.success === false) && (result.result == 're_connect')) {
            Ext.MessageBox.alert('Подключение', result.msg);
            window.onbeforeunload = null;
            findFirstWindow().window.location.href = __first_page;
            return;
          }
          if (result.success === true) {
            Ext.MessageBox.alert("Результат загрузки файлов", response.msg);
          } else {
            Ext.MessageBox.alert("Результат загрузки файлов", response.msg);
          }

        })
      } else {
        Ext.MessageBox.alert("Результат регистрации файлов для загрузки", response.msg);
      }

    })
  }
  ,
  ImportFiles_Grid: function (Grid, Operation) {
    Ext.MessageBox.wait({
      msg: 'Загрузка файлов, ждите... ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Metabase_class.RegistrationInputFile(0, function (response) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success === true) {
        Run_operation(null, {func_name: 'ShowMasterDetailGridWindow'
          , func_class_name: 'Grid_operation'
          , param_list: {sysname: 'sv_mbi_xml_new'
          }
        });
      } else {
        Ext.MessageBox.alert("Результат регистрации файлов для загрузки", response.msg);
      }

    })
  }
})
          