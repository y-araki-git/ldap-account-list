//// 共通部品
// タイムスタンプ
function ts () {
  var d = new Date();
  var year  = d.getFullYear();
  var month = ( d.getMonth()+1 < 10 ) ? '0' + (d.getMonth()+1) : d.getMonth()+1;
  var day   = ( d.getDate()    < 10 ) ? '0' + d.getDate()      : d.getDate();
  var hour  = ( d.getHours()   < 10 ) ? '0' + d.getHours()     : d.getHours();
  var min   = ( d.getMinutes() < 10 ) ? '0' + d.getMinutes()   : d.getMinutes();
  var sec   = ( d.getSeconds() < 10 ) ? '0' + d.getSeconds()   : d.getSeconds();

  return "" + year + month + day + hour + min + sec;
}

//// コールバック関数群
// フォームの一覧をロード
function form_list_load (params) {
  // 各表示領域のエレメントIDを取得
  var ids = form_params['ids'];
  var objFormSelect = document.getElementById (ids['FormSelect']);

  // フォームの一覧を表示
  var func = function (response) {
    var resolt = response['resolt'];

    var selectOptions = '';
    //console.log(resolt);
    for (var i=0; i<resolt['formList'].length; i++) {
      selectOptions += '<option value="' + resolt['formList'][i][0] + '">' + resolt['formList'][i][1] + '</option>';
    }

    objFormSelect.innerHTML = selectOptions;
  };

  // APIの実行結果受取り
  return function (response) {
    if ( response['status'] !== true ) {
        alert ( 'APIの処理に失敗' );
      } else {
        func (response);
      }
  };
}
// LDAPアカウント情報をCSVでダウンロート
function get_tsv_data (params) {
  // 各表示領域のエレメントIDを取得

  // LDAPアカウントのCSVをダウンロード
  var func = function (response) {
    var resolt = response['resolt'];
    //console.log(resolt);

    //CSV作成
    var bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
    var attributes = resolt['attributes'];
    var search     = resolt['search'];
    var content = "dn\t" + attributes.join("\t") + "\n";
    for (var i=0; i<search['count']; i++) {
      content = content + search[i]['dn'] + "\t";
      for (var j=0; j<attributes.length; j++) {
        var attr = attributes[j];
        if ( attr in search[i] ) {
          var val = "";
          for (var k=0; k<search[i][attr]['count']; k++) {
            if (k<search[i][attr]['count']-1) {
              val = val + search[i][attr][k] + ';';
            } else {
              val = val + search[i][attr][k];
            }
          }
          content = content + val + "\t";
        } else {
          content = content + "\t";
        }
      }
      content = content + "\n";
    }
    var blob = new Blob([ bom, content ], { "type" : "text/tsv" });

    // CSVダウンロード用の開始
    var aNode       = document.createElement('a');
    aNode.download  = 'all_ldap_user-' + ts() + '.tsv';
    aNode.href      = window.URL.createObjectURL(blob);
    aNode.click();
  };

  // APIの実行結果受取り
  return function (response) {
    if ( response['status'] !== true ) {
        alert ( 'APIの処理に失敗' );
      } else {
        func (response);
      }
  };
}

// フォームデータのロード
function form_load (params) {
  // 各表示領域のエレメントIDを取得
  var ids = form_params['ids'];
  var objLoginUser = document.getElementById (ids['LoginUser']);
  var objContent = document.getElementById (ids['Content']);

  // APIの実行に成功した場合の処理
  var func = function (response) {
    switch (params['para']['load_form']) {
      case 'all_ldap_user':
        all_ldap_user_form (response);
        break;
      case 'managed_ldap_user':
        managed_ldap_user_form (response);
        break;
      case 'ldap_user_exclude_reserv_list':
        ldap_user_exclude_reserv_list (response);
        break;
      case 'non_managed_ldap_user_list':
        non_managed_ldap_user_list (response);
        break;
    }
  };

  //// フォーム作成メイン部分
  // LDAPユーザ一覧表示フォーム
  var all_ldap_user_form = function (response) {
    var resolt = response['resolt'];
    console.log(resolt);

    // ログインユーザ名を表示
    objLoginUser.innerHTML = '<h3>' + resolt['LoginUser'] + 'の権限で画面を表示しています.</h3>';
    // フォームの内容を表示
    var button = '<input type="button" value="Download TSV"'
                             + ' onclick="call_form_action('
                             + '\'all_ldap_user_form\''
                             + ',\'get_tsv_data\''
                             + ',\'all_ldap_user\')">';
    var table_html = '';
    table_html += '<tr>';
    table_html += '<th>OU名</th>';
    table_html += '<th>利用者氏名(uid)</th>';
    table_html += '<th>Mail</th>';
    table_html += '<th>SUDO権限</th>';
    table_html += '<th>アクセス権限</th>';
    table_html += '<th>パスワード有効期限</th>';
    table_html += '<th>管理者</th>';
    table_html += '<th>会社名</th>';
    table_html += '</tr>';
    for (var i=0; i<resolt['OuList'].length; i++) {
      var index_list = [];
      var ou = resolt['OuList'][i];
      for (var j=0; j<resolt['LdapUserList'].length; j++)
        if ( ou === resolt['LdapUserList'][j]['ou'] )
          index_list.push (j);
      var row = '';
      for (var j=0; j<index_list.length; j++) {
        var LdapUser = resolt['LdapUserList'][index_list[j]];
        var td_style = '<td>';
        if ( j !== index_list.length-1 )
          td_style = '<td style="border-bottom-style:none;">';
        row += "<tr>";
        if ( j === 0 )
          row += "<td rowspan=" + index_list.length + ">" + LdapUser['ou'] + "</td>";
        row += td_style + LdapUser['sn'] + " " + LdapUser['givenname'] + "<br>(" + LdapUser['uid'] + ")</td>";
        row += td_style + LdapUser['mail'] + "</td>";
        row += td_style + LdapUser['grade'] + "</td>";
        row += td_style + LdapUser['description'].join('<br>') + "</td>";
        if ( LdapUser['isPwdExpir'] )
          row += td_style + "<b><font color=red>" + LdapUser['pwdExpirDate'] + "</font></b></td>";
        else
          row += td_style + LdapUser['pwdExpirDate'] + "</td>";
        if ( LdapUser['manager_uid'] != "" ) {
          row += td_style + LdapUser['manager_name'] + "<br>(" + LdapUser['manager_uid'] + ")</td>";
        } else {
          row += td_style + "</td>"
        }
        row += td_style + LdapUser['o'] + "</td>";
        row += "</tr>";
      }
      table_html += row;
    }
    objContent.innerHTML = button + "<table border=1>" + table_html + "</table>";
  };
  // 管理対象のLDAPアカウントを一覧表示
  var managed_ldap_user_form = function (response) {
    // すべてのチェックボックスを無効にする関数
    var disableCheckbox = function () {
      var inputObj = document.getElementsByTagName('input');
      for (var i=0; i<inputObj.length; i++) {
        if ( inputObj[i].type == "checkbox" )
          inputObj[i].disabled = true;
      }
    }

    var resolt = response['resolt'];
    //console.log(resolt);

    // ログインユーザ名を表示
    objLoginUser.innerHTML = '<h3>' + resolt['LoginUser'] + 'の権限で画面を表示しています.</h3>';
    // フォームの内容を表示
    var button_value = "";
    if ( resolt['excludeReserv']['Status'] == 'confirmed' ) {
      button_value = "申請を取り下げる";
    } else {
      button_value = "申請を行う";
    }
    var button = '<input type="button" value="' + button_value + '"'
                                     + ' onclick="call_form_action('
                                               + '\'managed_ldap_user_list\''
                                               + ',\'reserv_status_switch\''
                                               + ',\'' + resolt['excludeReserv']['Status'] + '\')">';
    var table_html = '';
    table_html += '<tr>';
    table_html += '<th>OU名</th>';
    table_html += '<th>除外</th>';
    table_html += '<th>利用者氏名(uid)</th>';
    table_html += '<th>権限</th>';
    table_html += '<th>会社名</th>';
    table_html += '</tr>';
    for (var i=0; i<resolt['OuList'].length; i++) {
      var index_list = [];
      var ou = resolt['OuList'][i];
      for (var j=0; j<resolt['LdapUserList'].length; j++)
        if ( ou === resolt['LdapUserList'][j]['ou'] )
          index_list.push (j);
      var row = '';
      for (var j=0; j<index_list.length; j++) {
        var LdapUser = resolt['LdapUserList'][index_list[j]];
        var td_style = '<td>';
        if ( j !== index_list.length-1 )
          td_style = '<td style="border-bottom-style:none;">';
        row += "<tr>";
        if ( j === 0 )
          row += "<td rowspan=" + index_list.length + ">" + LdapUser['ou'] + "</td>";
        var excludeReservUsers = resolt['excludeReserv']['UidList'];
        // ユーザIDが申請対象かを確認
        var checked = '';
        for (var k=0; k<excludeReservUsers.length; k++) {
          if ( excludeReservUsers[k] == LdapUser['uid'] ) {
            checked = ' checked="checked" ';
          }
        }
        input = '<input type=checkbox id="'
              + LdapUser['uid']
              + '" value="" '
              + checked
              + 'onclick="call_form_action('
                          + '\'managed_ldap_user_list\''
                          + ',\'apply_exclusion_switch\''
                          + ',\'' + LdapUser['uid'] + '\')">';
        row += td_style + input + "</td>";
        row += td_style + LdapUser['sn'] + " " + LdapUser['givenname'] + "<br>(" + LdapUser['uid'] + ")</td>";
        row += td_style + LdapUser['grade'] + "</td>";
        row += td_style + LdapUser['o'] + "</td>";
        row += "</tr>";
      }
      table_html += row;
    }
    objContent.innerHTML = button + "<table border=1>" + table_html + "</table>";

    // チェックボックスの無効化処理
    if ( resolt['excludeReserv']['Status'] == 'confirmed' ) {
      disableCheckbox ();
    }
  };
  // LDAPユーザの管理除外申請一覧
  var ldap_user_exclude_reserv_list = function (response) {
    var resolt = response['resolt'];
    //console.log (resolt);

    // ログインユーザ名を表示
    objLoginUser.innerHTML = '<h3>' + resolt['LoginUser'] + 'の権限で画面を表示しています.</h3>';
    // フォームの内容を表示
    var table_html = '';
    table_html += '<tr>';
    table_html += '<th>申請者(uid)</th>';
    table_html += '<th>更新日</th>';
    table_html += '<th>申請内容を処理</th>';
    table_html += '</tr>';
    for (var i=0; i<resolt['LdapUserExcludeReservList'].length; i++) {
      var app = resolt['LdapUserExcludeReservList'][i];
      var button = '<input type="button" value="　申請の実行　"'
                                     + ' onclick="call_form_action('
                                               + '\'ldap_user_exclude_reserv_list\''
                                               + ',\'process_application\''
                                               + ',\'' + app['ApplicantUid'] + '\''
                                               + ',\'' + app['ApplicantJPN'] + '\')">';
      table_html += '<tr>';
      table_html += '<td>' + app['ApplicantJPN'] + '<br>(' + app['ApplicantUid'] + ')</td>';
      table_html += '<td>' + app['UpdateTime'] + '</td>';
      table_html += '<td>' + button + '</td>';
      table_html += '</tr>';
    }
    objContent.innerHTML = resolt['LdapUserExcludeReservList'];
    objContent.innerHTML = "<table border=1>" + table_html + "</table>";
  };
  // LDAPユーザ一覧表示フォーム
  var non_managed_ldap_user_list = function (response) {
    var resolt = response['resolt'];

    // ログインユーザ名を表示
    objLoginUser.innerHTML = '<h3>' + resolt['LoginUser'] + 'の権限で画面を表示しています.</h3>';
    // フォームの内容を表示
    var button = '<input type="button" value="ユーザの削除実行"'
                                     + ' onclick="call_form_action('
                                               + '\'non_managed_ldap_user_list\''
                                               + ',\'delete_user\')">';
    var table_html = '';
    table_html += '<tr>';
    table_html += '<th>OU名</th>';
    table_html += '<th>削除</th>';
    table_html += '<th>利用者氏名(uid)</th>';
    table_html += '<th>権限</th>';
    table_html += '<th>パスワード有効期限</th>';
    table_html += '<th>管理者</th>';
    table_html += '<th>会社名</th>';
    table_html += '</tr>';
    for (var i=0; i<resolt['OuList'].length; i++) {
      var index_list = [];
      var ou = resolt['OuList'][i];
      for (var j=0; j<resolt['LdapUserList'].length; j++)
        if ( ou === resolt['LdapUserList'][j]['ou'] )
          index_list.push (j);
      var row = '';
      for (var j=0; j<index_list.length; j++) {
        var LdapUser = resolt['LdapUserList'][index_list[j]];
        var td_style = '<td>';
        if ( j !== index_list.length-1 )
          td_style = '<td style="border-bottom-style:none;">';
        row += "<tr>";
        if ( j === 0 )
          row += "<td rowspan=" + index_list.length + ">" + LdapUser['ou'] + "</td>";
        var checkbox = '<input type="checkbox" value="' + LdapUser['uid'] + '">';
        row += td_style + checkbox + "</td>";
        row += td_style + LdapUser['sn'] + " " + LdapUser['givenname'] + "<br>(" + LdapUser['uid'] + ")</td>";
        row += td_style + LdapUser['grade'] + "</td>";
        if ( LdapUser['isPwdExpir'] )
          row += td_style + "<b><font color=red>" + LdapUser['pwdExpirDate'] + "</font></b></td>";
        else
          row += td_style + LdapUser['pwdExpirDate'] + "</td>";
        if ( LdapUser['manager_uid'] != "" ) {
          row += td_style + LdapUser['manager_name'] + "<br>(" + LdapUser['manager_uid'] + ")</td>";
        } else {
          row += td_style + "</td>"
        }
        row += td_style + LdapUser['o'] + "</td>";
        row += "</tr>";
      }
      table_html += row;
    }
    objContent.innerHTML = button + "<table border=1>" + table_html + "</table>";
  };

  // APIの実行結果受取り
  return function (response) {
    if ( response['status'] !== true ) {
        alert ( 'APIの処理に失敗' );
      } else {
        func (response);
      }
  };
}
