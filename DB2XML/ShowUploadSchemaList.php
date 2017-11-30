        <?php
        set_time_limit(70000);
        session_start();
        require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
        require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
        ?>
<HTML>
    <HEAD>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <TITLE>
            Просмотр данных
        </TITLE>
        <LINK HREF="../css/2gStyles.css" REL="STYLESHEET" TYPE="text/css">
        <!--link href="../css/Filter.css" rel="stylesheet"-->
        <!--link href="../css/FilterBlock.css" rel="stylesheet"-->
        <SCRIPT TYPE="text/javascript">
          var arfiles = new Array();
          var _URLProjectRoot;
          _URLProjectRoot = '<?php echo $_SESSION["URLProjectRoot"]; ?>';
          __first_page = '<?php echo $_SESSION["first_page"]; ?>';
          ID_User =<?php echo $ID_User; ?>;
          function wload()
          {
            w = document.body.clientWidth;
            h = document.body.clientHeight - 10;
            //alert(h);
            //        h=h-document.getElementById("hh").offsetHeight-30;
            //alert(h);
            //
            h1 = 0;
            if (document.getElementById("operation") != null) {
              h2 = document.getElementById("operation").clientHeight;
            }
            else
              h2 = 0;
            document.getElementById("plansTable").style.height = h - h1 - h2 - 60;
          }
          window.onresize = function () {
            setTimeout("wload()", 1000)
          }

          var curID;
          var curObj;
          var curRowNumber;
          curID = -1;
          curRowNumber = -1;


          var startTime = new Date();
          var intervalID;
          var sCell;

          function GetWaitTime()
          {
            var currentTime = new Date();
            var waitTime = currentTime - startTime;
            var min = parseInt(waitTime / 1000 / 60);
            if (min > 0)
              waitTime = waitTime - min * 60000;
            var sec = parseInt(waitTime / 1000);
            if (min < 10)
              min = "0" + min;
            if (sec < 10)
              sec = "0" + sec;
            var ret = min + ":" + sec;
            return ret;
          }

          function SetCurrentTime()
          {
            document.getElementById(sCell).innerHTML = GetWaitTime();
          }

          function setCurRecord(ID, rowNumber, id_object)
          {
            var selectedRowNumber;
            curObj = id_object;

            selectedRowNumber = "row_" + rowNumber;
            //alert(selectedRowNumber);
            if (curRowNumber > -1)
            {
              if (curRowNumber % 2 == 1)
              {
                //   alert(document.getElementById("row_"+curRowNumber).className);
                document.getElementById("row_" + curRowNumber).className = "contentRowStyle1";
                // alert(document.getElementById("row_"+curRowNumber).className);
              }
              else
              {
                document.getElementById("row_" + curRowNumber).className = "contentRowStyle2";
              }
            }
            document.getElementById(selectedRowNumber).className = "contentRowStyle3";
            curID = ID;

            curRowNumber = rowNumber;
            // alert(curRowNumber);
          }

          function InvertCheckedAll()
          {
            for (var i = 0; i < arfiles.length; i++) {
              Elem = document.getElementById("chk_" + i);
              if ((Elem.checked == 'checked') || (Elem.checked == true))
                Elem.checked = '';
              else
                Elem.checked = 'checked';

            }
          }

          function CheckedAll()
          {
            for (var i = 0; i < arfiles.length; i++) {
              Elem = document.getElementById("chk_" + i);
              Elem.checked = 'checked';

            }
          }

          function UnCheckedAll()
          {
            for (var i = 0; i < arfiles.length; i++) {
              Elem = document.getElementById("chk_" + i);
              Elem.checked = '';

            }
          }

          function ShowData()
          {
            //for(var i = 0; i < arfiles.length; i++) {
            Elem = document.getElementById("chk_" + i);
            //          alert(Elem.checked);
            if (Elem == null)
            {
              window.clearInterval(intervalID);
              return;
            }
            if (Elem.checked) {
              startTime = new Date();
              cmd = _URLProjectRoot + 'DB2XML/DB2XML.php?id_xsd=' + arfiles[i];
              //         alert(cmd);
              statusCell = 'td_res_' + i;
              sCell = 'td_res_' + i;
              intervalID = window.setInterval(SetCurrentTime, 1000);
              //document.getElementById(statusCell).innerHTML="Подождите идет процесс выгрузки...";
              document.getElementById(statusCell).scrollIntoView(top);
              //structureXML=new XMLHttpRequest();
              var xmlhttp = new XMLHttpRequest();
              xmlhttp.open('GET', cmd, true);

              xmlhttp.onreadystatechange = function () {
                if (xmlhttp.readyState == 4) {
                  if (xmlhttp.status == 200)
                  {
                    window.clearInterval(intervalID);
                    document.getElementById(sCell).innerHTML = xmlhttp.responseText;
                    i++;
                    ShowData();
                  }
                }
              };
              xmlhttp.send(null);


              //            structureXML.open("GET", cmd, true  );
              //            structureXML.send(null);
              //            if (structureXML.status==200)
              //            {
              //              //window.clearInterval(intervalID);
              //              //alert('1111');
              //              document.getElementById(statusCell).innerHTML=structureXML.responseText;
              //            }
            }
            else
            {
              i++;
              ShowData();
            }

            //}
          }
        </SCRIPT>
    </HEAD>
    <BODY  onload="wload();">
        <?php

        $id_object = $_GET["ID"];
        $sysname = get_sysname($id_object);

        $sqlOut = 'select id_xsd, p.short_name, full_name from mbo_schema o left join mbs_periodicity as p on o.ID_PERIODICITY=p.ID_PERIODICITY';
        if (isset($_GET['FILTER']) && ($_GET['FILTER'] != ''))
          $sqlOut .= " WHERE id_xsd in (SELECT id_xsd FROM $sysname WHERE " . $_GET['FILTER'] . ")";
        $res = kometa_query($sqlOut);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          echo $sqlOut . '<br>' . $s_err . '<br>';
        }

        echo '<DIV ID="plansTable" STYLE="width: 100%; height: 80%; overflow: scroll;">';
        echo "<TABLE border=1 width=100% align=left cellspacing=0 cellpadding=0>";
// формирование колоночки для кнопочки для показа карточки объекта
        echo "<tr>";
        echo "<td width=10%>Выбор</td>";
        echo "<td width=40%>Описание</td>";
        echo "<td width=20%>Периодичность</td>";
        echo "<td width=30%>Результат</td>";
        echo "</tr>";

        $y = 0;
        $b = True;
        while ($row = kometa_fetch_object($res)) {
          $key = $row->id_xsd;
          ?>   
          <SCRIPT TYPE="text/javascript">
            arfiles[<?php echo $y ?>] = <?php echo '"' . $key . '"' ?>;
          </SCRIPT>
          <?php
          if ($b) {
            echo "<tr CLASS=\"contentRowStyle1\" ID=\"row_$y\" onclick=\"setCurRecord($key, $y, 'id_xsd');\">";
            $b = False;
          } else {
            echo "<tr CLASS=\"contentRowStyle2\" ID=\"row_$y\" onclick=\"setCurRecord($key, $y, 'id_xsd');\">";
            $b = True;
          }

          echo "<td><input type=\"checkbox\" ID=\"chk_$y\" name=\"chk_$y\"/></td>";

          $s = $row->full_name;
          if (!isset($s)) {
            $s = "<br>";
          }
          echo "<td>$s</td>";

          $s = $row->short_name;
          if (!isset($s)) {
            $s = "<br>";
          }
          echo "<td>$s</td>";
          echo "<td id=\"td_res_$y\"><br></td>";
          $y++;
          echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        echo '<br>';
        echo "<input type=\"submit\" onclick=\"i=0;ShowData();\" value=\"Выгрузить\" name=\"Btn_UploadDatasetToXML\"/>";
        echo "<input type=\"submit\" onclick=\"CheckedAll();\" value=\"Все\" name=\"Btn_checked_all\"/>";
        echo "<input type=\"submit\" onclick=\"UnCheckedAll();\" value=\"Очистить\" name=\"Btn_checked_all\"/>";
        echo "<input type=\"submit\" onclick=\"InvertCheckedAll();\" value=\"Инвертировать\" name=\"Btn_checked_inv\"/>";
        ?>
    </body>
</html>