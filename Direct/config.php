<?php

$API = array();
foreach (glob("classes/*.php") as $class) {
  $content = file_get_contents($class);
  preg_match("/class ([\S^{]+)/", $content, $m);
  preg_match_all("/function ([^ (]+) *\(\s*(.*)\s*\)\s*\/*\/*(.*)/", $content, $f);
  for ($i = 0; $i < count($f[0]); $i++) {
    $API[$m[1]]['methods'][$f[1][$i]]['len'] = empty($f
    [2][$i]) ? 0 : count(preg_split("/,/", $f[2][$i]));
    if (preg_match("/formHandler/", $f[3][$i]))
      $API[$m[1]]['methods'][$f[1][$i]]['formHandler'] = true;
  }
}
//$API = array(
//'VisualPanelBackEnd' => array(
//'methods' => array(
//'Get_URLVisualPanelMakets' => array(
//'len' => 0
//),
// 'GetVisualPanelList' => array(
//'len' => 0
//)
//)
//),
// 'TestAction' => array(
//'methods' => array(
//'doEcho' => array(
//'len' => 1
//),
// 'multiply' => array(
//'len' => 1
//),
// 'getTree' => array(
//'len' => 1
//),
// 'getGrid' => array(
//'len' => 1
//),
// 'showDetails' => array(
//'params' => array(
//'firstName',
// 'lastName',
// 'age'
//)
//)
//)
//)
//
//    'Profile'=>array(
//        'methods'=>array(
//            'getBasicInfo'=>array(
//                'len'=>2
//            ),
//            'getPhoneInfo'=>array(
//                'len'=>1
//            ),
//            'getLocationInfo'=>array(
//                'len'=>1
//            ),
//            'updateBasicInfo'=>array(
//                'len'=>0,
//                'formHandler'=>true
//            )
//        )
//    )
//);
