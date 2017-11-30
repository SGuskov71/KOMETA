<?php
/**
 * Шаблон формы логина
 * Использует переменные:
 * $actionUrl - атрибут фомы action, который определяет URL обработчика формы на стороне сервера
 * $errorMessage - сообщение об ошибке. Не отображается, если переменной нет или она пустая.
 */
?>
<html>
    <head>

        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">

        <title>Вход пользователя</title>

        <script>
          function pressed(e) {
            // Has the enter key been pressed?
            if ((window.event ? event.keyCode : e.which) == 13) {
              // If it has been so, manually submit the <form>
              document.forms[0].submit();
            }
          }
        </script>

        <style>
            * { 
                margin: 0; 
                padding: 0; 
                border: none; 
            }
            html{
                font-size: 100%;
            }
            body {
                font-size: 1em;
                font-family: Verdana, sans-serif;
            }
            table.layout {
                height: 100%;
                border-collapse: collapse; /* ramove space around the table */
            }
            table.layout td.panel {
                vertical-align: top;
            }
            table.layout td.panel.left {
                background-color: #304788;
            }
            table.layout td.panel.right {
                background-color: #ffffff;

            }
            .text-highlighting-disabled {
                -webkit-touch-callout: none;
                -webkit-user-select: none; /* Webkit */
                -moz-user-select: none;    /* Firefox */
                -ms-user-select: none;     /* IE 10  */
                -o-user-select: none; /* Currently not supported in Opera but will be soon */
                user-select: none;  
            }
            .login-form {
                width: 20em;
                overflow: hidden; /* make it wrap around the margin of the item at the bottom */
            }
            .login-form .logo {
                width: 15em;
                margin: 1em 1em 3em 1em;
            }
            .login-form .field-container {
                background-color: #ffffff;
                margin: 0 1em 1em 1em;
                height: 2.7em;
                position: relative;
            }
            .login-form .field-container .label {
                font-weight: bold;
                position: absolute;
                top: .7em;
                left: .5em;
            }
            .login-form .field-container .field {
                width: 11em;
                position: absolute;
                top: .7em;
                left: 6em;
            }
            .login-form .field-container .field input {
                border: .1em solid #304788;
                width: 100%;
                height: 1.5em;
            }
            .login-form .button.submit {
                background-color: #ce2020;
                color: #ffffff;
                font-weight: bold;
                margin: 0 1em 2em 14em;
                height: 2em;
                line-height: 2em;
                text-align: center;
                cursor: pointer; 
                cursor: hand;
            }
            .login-form .error-message {
                background-color: #ffffff;
                margin: 0 1em 1em 1em;
                padding: .5em;
                color: #da4f49;
            }
            .login-form .content {
                color: #ffffff;
                margin: 0 1em 1em 1em;
            }
            .login-form .content ul {
                padding-left: 1em;
            }
            .login-form .content ul li {
                margin-bottom: 1em;
                text-align: justify;
            }
            .login-form .content p {
                margin-bottom: 1em;
                font-size: .8em;
            }
            .login-form .content a {
                color: #ffffff;
            }
            .login-form .content a.mail {
                text-decoration: none;
            }
            .login-form .content b {
                font-weight: bold;
                font-size: 1em;
            }
            .content {
                padding: 1em;
            }
            .content .title {
                color: #304788;
                font-size: 24pt;
                font-weight: bold;
                line-height: 1em;
                margin-bottom: 1em;
            }
            .content .paragraph {
                margin-bottom: 1em;
                line-height: 1.4em;
            }
            .content .box {
                border: .1em solid #304788;
                margin-bottom: 1em;
                padding: .8em;
            }
            .content .box .title {
                font-size: 12pt;
                color: #000000;
            }
            .content .box ul {
                padding-left: 1em;
            }
            .content .box ul li {
                margin-bottom: .5em;
                list-style-type: none; /* Убираем маркеры у списка */
            }
            .content .box ul li:before {
                content: "✔ "; /* Добавляем в качестве маркера символ */
                color: #304788;
            }
        </style>

    </head>
    <body>
        <table class="layout">
            <tr>
                <td class="panel left">
                    <form class="login-form text-highlighting-disabled" method="POST" action="<?php echo $actionUrl; ?>">
                        <img class="logo" src=<?php echo '"' . $logo_img . '"' ?>/> 
                        <div class="field-container">
                            <div class="label">логин</div>
                            <div class="field">
                                <input type="text" name="log"  onkeydown="pressed(event);" />
                            </div>
                        </div>
                        <div class="field-container">
                            <div class="label">пароль</div>
                            <div class="field">
                                <input type="password" name="pwd" onkeydown="pressed(event);" />
                            </div>
                        </div>
                        <?php if (isset($errorMessage) && !empty($errorMessage)): ?>
                          <div class="error-message">
                              <?php echo $errorMessage; ?>
                          </div>
                        <?php endif; ?>
                        <input type='hidden' name='GO' value='Вход' />
                        <div class="button submit" onclick="document.forms[0].submit();">Вход</div>
                        <div class="content">
                            <?php
                            $s = file_get_contents($_SESSION['APP_INI_DIR'] . 'info.html');
                            $i = strripos($s, '<BODY>');
                            $i1 = strripos($s, '</BODY>');
                            if (($i > 0) && ($i1 > $i)) {
                              $s_ = substr($s, $i + 6, $i1 - $i - 6);
                              echo $s_;
                            }
                            ?>
                        </div>
                    </form>
                </td>
                <td class="panel right">
                    <div class="content">
                        <div class="title">Интеграционный портал<br />Единой системы управления программами</div>
                        <div class="paragraph">Портал предназначен для упрощения процесса информационного обмена между Министерством промышленности и торговли РФ, холдингами и предприятиями</div>
                        <div class="box">
                            <div class="title">Управление этапами государственных капитальных вложений</div>
                            <ul>
                                <li>Сформировать кассовый и организационный планы</li>
                                <li>Внести отчётную информацию по этапам государственных капитальных вложений</li>
                                <li>Проконтролировать исполнение процедур по этапам государственных капитальных вложений</li>
                            </ul>
                        </div>
                        <div class="box">
                            <div class="title">Управление результатами интеллектуальной деятельности</div>
                            <ul>
                                <li>Загрузить результаты интеллектуальной деятельности </li>
                                <li>Управлять правами интеллектуальной собственности</li>
                            </ul>
                        </div>
                        <div class="box">
                            <div class="title">Управление многолетними контрактами</div>
                            <ul>
                                <li>Сформировать дополнительное соглашение к многолетнему контракту</li>
                            </ul>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>