//// 外部jsファイルの読込
//eval (include_js ('form_load.js'));

//// グローバル変数
var form_params;    // フォームのパラメータ群

// 関数群
//// フォームの初期化
function init_form (_form_params) {
  form_params = _form_params;
  var ids = form_params['ids'];

  // フォームの一覧を取得するためにAPIへ渡すパラメータ作成
  var send_params = {
    'func' : 'form_list_load'
  };
  // APIを呼出しフォームの一覧を取得
  call_func (send_params);

  // 初期フォームの表示
  var objFormSelect = document.getElementById (ids['FormSelect']);
  objFormSelect.selectedIndex = 0;  // 初期フォームで表示する情報を選択
  objFormSelect.onchange();         // OnChangeイベントを発生させる.
}

//// フォームを選択するセレクトイベント処理
function reload_form () {
  select_form ();
}
function select_form () {
  var ids = form_params['ids'];
  var objTitle = document.getElementById (ids['Title']);
  var objFormSelect = document.getElementById (ids['FormSelect']);

  // 選択されているオプションを取得
  var index = objFormSelect.selectedIndex;
  var objOption = objFormSelect.options[index];

  // 各表示領域のエレメントIDを取得
  // タイトルを表示
  objTitle.innerHTML = objOption.innerHTML;

  // ロードするフォームの名前取得とAPIへ渡すパラメータ作成
  var send_params = {
    'func' : 'form_load',
    'para' : {
      'load_form' : objOption.value
    }
  };

  // API呼出し
  call_func (send_params);
}

//// フォームのアクションイベント処理
function call_form_action (funcName, actionType) {
  // アクションイベントの実装
  //// ユーザの削除イベント処理
  var non_managed_ldap_user_list_delete_user = function () {
    var notfin_flag = 1;  // 処理の未実行フラグ。フラグが1の場合処理未完了
    if(confirm('チェックしたユーザの削除を実行しますか？')){
      // 実行者のLDAPパスワードを取得
      var pass = prompt ('LDAPパスワード');
      // チェックされたuidの一覧を取得
      var uids = [];
      var inputs = document.getElementsByTagName("input");
      for (var i = 0, l = inputs.length; i < l; i++) {
        var input = inputs[i];
        if (input.type == "checkbox" && input.checked) {
          uids.push( input.value );
        }
      }

      var send_params = {
        'func' : 'delete_user',
        'para' : {
          'pass'  : pass,
          'uids'   : uids,
        }
      };
      notfin_flag = call_func (send_params);
    }
    return notfin_flag;
  }
  //// 運用者による申請内容の処理
  var ldap_user_exclude_reserv_list_process_application = function (applicantUid, applicantJP) {
    var notfin_flag = 1;  // 処理の未実行フラグ。フラグが1の場合処理未完了
    if(confirm(applicantJP+"の申請内容を実行しますか？")){
      // 実行者のLDAPパスワードを取得
      var pass = prompt ('LDAPパスワード');
      // 申請内容を処理するAPIパラメータを作成
      var send_params = {
        'func' : 'process_application',
        'para' : {
          'pass'      : pass,
          'applicant' : applicantUid,
        }
      };
      notfin_flag = call_func (send_params);
    }
    return notfin_flag;
  }
  //// 管理者による管理対象外ユーザの申請ボタンイベント時のAPI呼び出し処理
  var managed_ldap_user_list_reserv_status_switch = function (reserv_status_now) {
    // 申請状態を変更するAPIパラメータを作成
    var send_params = {
      'func' : 'reserv_status_switch',
      'para' : {
        'status'   : reserv_status_now,
      }
    };
    call_func (send_params);
  }
  //// 管理者による管理対象外ユーザの予約チェックボックスイベント時のAPI呼出し処理
  var managed_ldap_user_list_apply_exclusion_switch = function (uid) {
    // ユーザの削除予約の変更するAPIパラメータを作成
    var inputObj = document.getElementById(uid);
    var send_params = {
      'func' : 'apply_exclusion_switch',
      'para' : {
        'uid'   : uid,
        'apply' : inputObj.checked
      }
    };
    call_func (send_params);
  }
  //// CSVダウンロードボタンイベント時のAPI呼出し処理
  var all_ldap_user_form_get_tsv_data_all_ldap_user = function () {
    // ユーザの削除予約の変更するAPIパラメータを作成
    var send_params = {
      'func' : 'get_tsv_data',
      'para' : {
        'taget'   : 'all_ldap_user'
      }
    };
    call_func (send_params);
  }

  // アクションイベントの振分け
  switch (funcName) {
    case 'all_ldap_user_form':
      switch (actionType) {
        case 'get_tsv_data':
          all_ldap_user_form_get_tsv_data_all_ldap_user ();
          break;
      }
      break;
    case 'managed_ldap_user_list':
      switch (actionType) {
        case 'reserv_status_switch':
          reserv_status_now = arguments[2];
          managed_ldap_user_list_reserv_status_switch (reserv_status_now);
          break;
        case 'apply_exclusion_switch':
          uid = arguments[2];
          managed_ldap_user_list_apply_exclusion_switch (uid);
          break;
      }
      break;
    case 'ldap_user_exclude_reserv_list':
      switch (actionType) {
        case 'process_application':
          applicantUid = arguments[2];
          applicantJP  = arguments[3];
          ldap_user_exclude_reserv_list_process_application (applicantUid, applicantJP);
          break;
      }
      break;
    case 'non_managed_ldap_user_list':
      switch (actionType) {
        case 'delete_user':
          non_managed_ldap_user_list_delete_user ();
          break;
      }
      break;
  }
}

//// API呼出し
function call_func (params) {
  var err_flag = 0;
  switch (params['func']) {
    case 'form_list_load':
      err_flag = r_api (params, form_list_load(params));
      break;
    case 'form_load':
      err_flag = r_api (params, form_load(params));
      break;
    case 'reserv_status_switch':
      err_flag = r_api (params, null);
      reload_form ();
      break;
    case 'apply_exclusion_switch':
      err_flag = r_api (params, null);
      break;
    case 'process_application':
      err_flag = r_api (params, null);
      if ( err_flag == 0 ) reload_form ();
      break;
    case 'delete_user':
      err_flag = r_api (params, null);
      if ( err_flag == 0 ) reload_form ();
      break;
    case 'get_tsv_data':
      err_flag = r_api (params, get_tsv_data(params));
      break;
    default:
      err_flag = 1;
      break;
  }
  return err_flag;
}
function dump_func () {
  return function (r) {
    var response = JSON.parse (r);
    alert (r);
    console.log (response);
  };
}


// リモートAPIの実行
function r_api (params, call_back) {
  var err_flag  = 0;
  var r_api_url = "./api.php";
  var json = JSON.stringify (params, null, '\t');

  var xhr = new XMLHttpRequest ();
  xhr.open("POST" , r_api_url, false);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.send("params=" + json);

  if (xhr.status === 0) {
    alert("リモートAPIの呼出し失敗");
    err_flag = 1;
  } else {
    if ((200 <= xhr.status && xhr.status < 300) || (xhr.status == 304)) {
      try {
        var response = JSON.parse (xhr.responseText);
        if (call_back !== null) call_back (response);
      } catch (e) {
        console.log ("JSONのパース処理に失敗");
        console.log (e);
        alert ( "APIの処理に失敗\n" + xhr.responseText );
        err_flag = 1;
      }
    } else {
      alert("予期せぬサーバ応答:" + xhr.status);
      err_flag = 1;
    }
  }

  return err_flag;
}

// 外部jsを取得
function include_js (url) {
  var xhr = new XMLHttpRequest ();
  xhr.open ("GET", url, false);
  xhr.setRequestHeader ('Content-Type', 'application/x-www-form-urlencoded');
  xhr.setRequestHeader ('X-Requested-With', 'XMLHttpRequest');
  xhr.send ("");

  if (xhr.status === 0) {
    alert ("jsのインクルードに失敗: " + url);
  } else {
    if ((200 <= xhr.status && xhr.status < 300) || (xhr.status == 304)) {
      // javascriptとして実行
      return xhr.responseText;
    } else {
      alert ("予期せぬサーバ応答:" + xhr.status);
      alert ("jsのインクルードに失敗: " + url);
    }
  }
}
