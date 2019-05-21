<!DOCTYPE html>
<html lang = "ja">
<head>
<meta charset = "UFT-8">
<title>LDAPアカウントの管理フォーム</title>
</head>
<body onLoad="init_form({
  'ids'       : {
    'Title'             : 'TID_Title',
    'LoginUser'         : 'TID_LoginUser',
    'FormSelect'        : 'TID_FormSelect',
    'Content'           : 'TID_Content'
  }
})
">
<h1 id=TID_Title></h1>
<div id=TID_LoginUser></div>
<select id=TID_FormSelect onChange="select_form()">
</select>
<Hr>
<div id=TID_Content></div>
<Hr>
<script src="form_load.js?uniq=<?php echo date('YmdHis'); ?>"></script>
<script src="form.js?uniq=<?php echo date('YmdHis'); ?>"></script>
</body>
</html>
