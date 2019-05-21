<?php
  require('./conf.php');
  require('./lib/ldap.php');
  require('./lib/reserv.php');

  // ログ出力関数
  function logging ($msg) {
    $ts = date('Y/m/d H:i:s');
    $fp = fopen (APPLOG_DIR . '/' . APPLOG_FILE, "a+");
    fwrite ($fp, "{$ts} {$msg}\n");
    fclose ($fp);
  }
?>

<?php
  $response = array();          // API呼出し元への戻り値
  $login_user;                  // WEBにアクセスしているユーザID

  // ユーザ認証状態及びPOST値の値を確認
  if ( empty($_SERVER['REMOTE_USER']) || empty($_POST['params']) )
    return;
  $login_user = $_SERVER['REMOTE_USER'];

  // JSONでPOSTされたデータのエンコード
  $params = $_POST['params'];
  $params = mb_convert_encoding ($params, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
  $params = json_decode ($params, true);

  // 要求された処理を実行
  switch ($params['func']) {
    // メニュー一覧を取得
    case 'form_list_load':
      logging ($login_user."に参照権限のあるフォームのリストを返答.");
      $response = get_form_list ();
      break;
    // LDAPアカウント情報のCSVを作成するための取得
    case 'get_tsv_data':
      logging ($login_user."がLDAP情報のCSV作成のためのデータを要求しました.");
      $response = get_tsv_data ($params['para']);
      break;
    // フォームをロード（呼出し部分）
    case 'form_load':
      logging ($login_user."がフォームのロードデータを要求しました.");
      $response = form_load ($params['para']);
      break;
    // 管理除外対象ユーザの申請処理（呼出し部分）
    case 'reserv_status_switch':
      $response = reserv_status_switch ($params['para']);
      break;
    // 管理除外対象ユーザの変更処理（呼出し部分）
    case 'apply_exclusion_switch':
      $response = apply_exclusion_switch ($params['para']);
      break;
    // 申請内容を処理（呼出し部分）
    case 'process_application':
      $response = process_application ($params['para']);
      break;
    // ユーザの削除処理（呼出し部分）
    case 'delete_user':
      logging ($login_user."がアカウントの削除を要求しました.");
      $response = delete_user ($params['para']);
      break;
  }

  // 処理結果
  $json = json_encode ( $response );
  logging ($login_user."へ応答しました.");
  echo $json;
?>


<?php
  // フォームの一覧を取得
  function get_form_list () {
    // 変数の初期化
    global $login_user;
    $resolt   = array();
    $response = array();
    $response['status'] = true;

    // ユーザのDN値を取得
    $basedn = 'dc=test,dc=org';
    $o_ldap = new ldap();
    $o_ldap->bind (LDAP_SERVER);
    $login_user_entry = $o_ldap->uid_info ($basedn, $login_user);
    $login_user_dn = $login_user_entry['dn'];

    // メニューの取得
    $infradn = 'ou=ops,dc=test,dc=org';
    $formList = array ();
    if(strpos($login_user_dn, $infradn) !== false) {
      // インフラOUに存在するユーザの場合のメニュー
      array_push ($formList, array('all_ldap_user',                 'LDAPアカウントの一覧表示'));
      array_push ($formList, array('managed_ldap_user',             '管理対象のLDAPアカウントを一覧表示'));
      array_push ($formList, array('ldap_user_exclude_reserv_list', 'LDAPアカウントの管理除外申請の一覧'));
      array_push ($formList, array('non_managed_ldap_user_list',    '管理者のいないLDAPアカウントの一覧'));
    } else {
      // インフラOU以外に存在するユーザの場合のメニュー
      array_push ($formList, array('managed_ldap_user',             '管理対象のLDAPアカウントを一覧表示'));
    }

    $resolt['formList'] = $formList;
    $response['resolt'] = $resolt;
    return $response;
  }
  // LDAPアカウント情報のCSVを作成するための取得
  function get_tsv_data ($para) {
    // 変数の初期化
    global $login_user;
    $resolt   = array();
    $response = array();
    $response['status'] = true;

    // LDAPへの接続
    $basedn = 'dc=test,dc=org';
    $o_ldap = new ldap();
    $o_ldap->bind (LDAP_SERVER);

    // 取得する情報の選択
    $taget = $para['taget'];
    switch ($taget) {
      // 全LDAPアカウントの登録情報を取得
      case 'all_ldap_user':
        logging ($login_user."に返答するCSV作成用のLDAPアカウントの一覧データを作成.");
        $filter = '(&(objectClass=posixAccount)(uid=*))';
        $attributes = array ('sn', 'givenname', 'uid', 'mail', 'o', 'manager', 'description');
        $resolt['attributes'] = $attributes;
        $resolt['search'] = $o_ldap->search ($basedn, $filter, $attributes);
        break;
      default:
        logging ($login_user."が存在しないCSVデータターゲットを要求したため、処理に失敗.");
        $response['status'] = false;
        break;
    }

    $response['resolt'] = $resolt;
    return $response;
  }

  // フォーム画面の初期化データ取得関数
  function form_load ($para) {
    // 変数の初期化
    global $login_user;
    $resolt   = array();
    $response = array();
    $response['status'] = true;
    $resolt['LoginUser'] = $login_user;

    // フォームのデータロード
    $load_form = $para['load_form'];
    switch ($load_form) {
      // LDAPアカウントの一覧とOUのリストを作成（呼出し部分）
      case 'all_ldap_user':
        logging ($login_user."に返答するLDAPアカウントの一覧とOUのリストを作成.");
        $resolt = array_merge($resolt, all_ldap_user());
        break;
      // 管理対象のLDAPアカウントの一覧とOUのリストを作成（呼出し部分）
      case 'managed_ldap_user':
        logging ($login_user."に返答する管理対象のLDAPアカウントの一覧とOUのリストを作成.");
        $resolt = array_merge($resolt, managed_ldap_user());
        break;
      // LDAPアカウントの管理除外申請一覧を作成（呼出し部分）
      case 'ldap_user_exclude_reserv_list':
        logging ($login_user."に返答するLDAPアカウントの管理除外申請一覧を作成.");
        $resolt = array_merge($resolt, ldap_user_exclude_reserv_list());
        break;
      // 管理者のいないLDAPアカウントの一覧を作成（呼出し部分）
      case 'non_managed_ldap_user_list':
        logging ($login_user."に返答する管理者のいないLDAPアカウントの一覧を作成.");
        $resolt = array_merge($resolt, non_managed_ldap_user_list());
        break;
      default:
        logging ($login_user."が存在しないフォームデータを要求したため、処理に失敗.");
        $response['status'] = false;
        break;
    }

    $response['resolt'] = $resolt;
    return $response;
  }

  // LDAPアカウントの一覧とOUのリストを作成（共通部）
  function search_ldap_user ($filter) {
    $resolt = array();
    $LdapUserList = array();
    $basedn = 'dc=test,dc=org';
    $attributes = array ('sn', 'givenname', 'uid', 'mail', 'o', 'pwdchangedtime', 'manager', 'description');

    // LDAPユーザ検索実行
    $o_ldap = new ldap();
    $o_ldap->bind (LDAP_SERVER);
    $user_list = $o_ldap->search ($basedn, $filter, $attributes);

    // LDAPユーザリストとOUリストを作成する
    $ou_list = array();
    // DN値からOU部分の取り出し及び権限（manager, common, admin）
    for ($user_num=0; $user_num<$user_list['count']; $user_num++) {
      $explode_dn = explode(',', $user_list[$user_num]['dn']);
      $id_cmd="id ".$user_list[$user_num]['uid'][0]." -nG";
      $groups_name = shell_exec("id test-user -nG | sed -e 's/^users //'");
      $lock_search_cmd="ldapsearch -x -LLL -H  ldap://".LDAP_SERVER."/ -b '".$basedn."'  'uid=".$user_list[$user_num]['uid'][0]."' pwdAccountLockedTime";
      $pwdAccountLockedTime = shell_exec($lock_search_cmd);
      $Locked="";
      if(strpos($pwdAccountLockedTime,'pwdAccountLockedTime') !== false){
          $Locked="(Locked)";
      }
      $grade=str_replace(" ","<br>",$groups_name);
      #$grade = $explode_dn[1];
      #$grade = explode('=', $grade)[1];
      #switch ($grade) {
      #  case 'manager':
      #  case 'common':
      #  case 'admin':
      #    array_splice($explode_dn, 0, 2);
      #    if ( $explode_dn[0] === 'ou=dev')
      #      array_splice($explode_dn, 0, 1);
      #    break;
      #  default:
      #    $grade = 'none';
          array_splice($explode_dn, 0, 1);
      #}
      //$ou = join (',', $explode_dn);
      $ou = str_replace("ou=", "", $explode_dn[0]);
      // 取得したOUを、一旦配列に追加する
      $ou_list[] = $ou;
      // パスワードの有効期限を算出
      $pwdChangeDate = substr ($user_list[$user_num]['pwdchangedtime'][0], 0, 4) . "/"
                     . substr ($user_list[$user_num]['pwdchangedtime'][0], 4, 2) . "/"
                     . substr ($user_list[$user_num]['pwdchangedtime'][0], 6, 2);
      $pwdExpirDate  = date("Y/m/d", strtotime("{$pwdChangeDate} +90 day")).$Locked;
      $nowDate       = date("Y/m/d");
      if ( strtotime($pwdExpirDate) <= strtotime($nowDate) ) {
        $isPwdExpir    = true;
      } else {
        $isPwdExpir    = false;
      }

      // LDAPユーザ情報を保持するの配列を作成
      $LdapUser = array();
      $LdapUser['sn'] = $user_list[$user_num]['sn'][0];
      $LdapUser['givenname'] = $user_list[$user_num]['givenname'][0];
      $LdapUser['uid'] = $user_list[$user_num]['uid'][0];
      $LdapUser['mail'] = $user_list[$user_num]['mail'][0];
      $LdapUser['o'] = $user_list[$user_num]['o'][0];
      $LdapUser['ou'] = $ou;
      $LdapUser['grade'] = $grade;
      $LdapUser['pwdExpirDate'] = $pwdExpirDate;
      $LdapUser['isPwdExpir'] = $isPwdExpir;
      $LdapUser['manager_name'] = array();
      $LdapUser['manager_uid'] = array();
      if ( array_key_exists('manager', $user_list[$user_num]) )
        for ($count=0; $count<$user_list[$user_num]['manager']['count']; $count++) {
          $manager_dn = $user_list[$user_num]['manager'][$count];
          $manager = $o_ldap->dn_info ($manager_dn);
          $LdapUser['manager_name'][] = "{$manager['sn'][0]} {$manager['givenname'][0]}";
          $LdapUser['manager_uid'][] = "{$manager['uid'][0]}";
        }
      $LdapUser['description'] = array();
      if ( array_key_exists('description', $user_list[$user_num]) )
        for ($count=0; $count<$user_list[$user_num]['description']['count']; $count++) {
          $LdapUser['description'][] = $user_list[$user_num]['description'][$count];
        }
      $LdapUserList[] = $LdapUser;
    }
    $o_ldap->unbind ();

    // OUリストの重複排除及びソートを実施
    $ou_list = array_unique ($ou_list);
    $ou_list = array_values ($ou_list);
    sort ($ou_list);

    // 応答値を設定
    $resolt['LdapUserList']   = $LdapUserList;
    $resolt['OuList']         = $ou_list;

    return $resolt;
  }

  // 全てのLDAPアカウントの一覧とOUのリストを作成（実装部分）
  function all_ldap_user () {
    $filter = '(&(objectClass=posixAccount)(uid=*))';

    // 全てのLDAPアカウント情報を取得
    return search_ldap_user($filter);
  }

  // 管理対象のLDAPアカウントの一覧とOUのリストを作成（実装部分）
  function managed_ldap_user () {
    // 変数の初期化
    global $login_user;

    // 申請ファイルの状態をロード
    $o_exReserv = new excludeReserv(RESERV_DIR, HISTORY_DIR);
    $arr_exReserv = array('excludeReserv' => $o_exReserv->getReservContent($login_user));

    // ユーザのDN値を取得
    $basedn = 'dc=test,dc=org';
    $o_ldap = new ldap();
    $o_ldap->bind (LDAP_SERVER);
    $login_user_entry = $o_ldap->uid_info ($basedn, $login_user);
    $login_user_dn = $login_user_entry['dn'];

    // 管理対象のLDAPアカウント情報を取得フィルター
    $filter = '(&(objectClass=posixAccount)(manager=' . $login_user_dn . '))';

    // 管理対象のLDAPアカウント情報と申請ファイルの状態を取得
    return array_merge (search_ldap_user($filter), $arr_exReserv);
  }

  // LDAPアカウントの管理除外申請一覧を作成（実装し部分）
  function ldap_user_exclude_reserv_list () {
    // 申請ファイルへのアクセス準備
    $o_exReserv = new excludeReserv(RESERV_DIR, HISTORY_DIR);

    // 申請済みの一覧を作成
    $resolt['LdapUserExcludeReservList'] = $o_exReserv->getFixedReservContent ();
    return $resolt;
  };

  // 管理者のいないLDAPアカウントの一覧
  function non_managed_ldap_user_list () {
    // 変数の初期化
    global $login_user;

    // ユーザのDN値を取得
    $basedn = 'dc=test,dc=org';
    $o_ldap = new ldap();
    $o_ldap->bind (LDAP_SERVER);
    $login_user_entry = $o_ldap->uid_info ($basedn, $login_user);
    $login_user_dn = $login_user_entry['dn'];

    // 管理対象のLDAPアカウント情報を取得
    $filter = '(&(objectClass=posixAccount)(!(manager=*)))';
    return search_ldap_user($filter);
  }

  // 管理除外対象ユーザの申請処理関数
  function reserv_status_switch ($para) {
    // 変数の初期化
    global $login_user;
    $basedn = 'dc=test,dc=org';

    // 申請者の氏名を取得
    $o_ldap = new ldap();
    $o_ldap->bind (LDAP_SERVER);
    $login_user_entry = $o_ldap->uid_info ($basedn, $login_user);
    $login_user_dn = "{$login_user_entry['sn'][0]} {$login_user_entry['givenname'][0]}";
    $o_ldap->unbind ();
    $applicantJP = $login_user_dn;

    // 申請ファイルへのアクセス準備
    $o_exReserv = new excludeReserv(RESERV_DIR, HISTORY_DIR);

    // 現在の申請状態を反転させる
    logging ("[{$para['status']}]");
    if ( $para['status'] == 'confirmed' ) {
      logging ($login_user."が申請状態を未申請に変更しました.");
       $o_exReserv->updateReservStatus ($login_user, $applicantJP, 'unconfirmed');
    } else {
      logging ($login_user."が申請状態を申請中に変更しました.");
       $o_exReserv->updateReservStatus ($login_user, $applicantJP, 'confirmed');
    }
  }

  // 管理除外対象ユーザの変更処理関数
  function apply_exclusion_switch ($para) {
    // 変数の初期化
    global $login_user;

    // 申請ファイルへのアクセス準備
    $o_exReserv = new excludeReserv(RESERV_DIR, HISTORY_DIR);

    // チェック状態の更新
    if ( $para['apply'] == 1 ) {
      logging ($login_user."が".$para['uid']."を申請対象に含めました.");
    } else {
      logging ($login_user."が".$para['uid']."を申請対象から除外しました.");
    }
    $o_exReserv->updateReservUser($login_user, $para['uid'], $para['apply']);
  }

  // 申請内容を処理関数
  function process_application ($para) {
    // 変数の初期化
    global $login_user;
    $basedn = 'dc=test,dc=org';

    // 作業ユーザのDN値を取得
    $o_ldap = new ldap();
    $o_ldap->bind (LDAP_SERVER);
    $login_user_entry = $o_ldap->uid_info ($basedn, $login_user);
    $login_user_dn = $login_user_entry['dn'];
    $o_ldap->unbind ();

    // 作業ユーザの権限でLDAPをオープン
    $o_ldap->bind (LDAP_SERVER, $login_user_dn, $para['pass']);

    // 申請内容の確認
    $applicant  = $para['applicant'];
    $o_exReserv = new excludeReserv(RESERV_DIR, HISTORY_DIR);
    $reservContent = $o_exReserv->getReservContent ($applicant);

    // 管理対象外の申請されたユーザの管理者情報を更新
    $managerDN = ($o_ldap->uid_info ($basedn, $applicant))['dn'];
    foreach ( $reservContent['UidList'] as $uid ) {
      logging ($login_user."が" . $uid. "の管理者情報から" .$applicant . "の除外を開始.");
      $tagetEntry      = ($o_ldap->uid_info ($basedn, $uid));
      $tagetDN         = $tagetEntry['dn'];
      $tagetDnManagers = $tagetEntry['manager'];
      if ( array_search($managerDN, $tagetDnManagers) !== FALSE ) {
        $o_ldap->dropManager ($tagetDN, $managerDN);
        logging ($login_user."が" . $uid. "の管理者情報から" .$applicant . "の除外を完了.");
      } else {
        logging ($uid. "の管理者情報に" .$applicant . "は含まれていません.");
      }
    }

    // 申請ファイルをヒストリに移動
    $o_exReserv->moveHistory ($applicant);
  }

  // ユーザの削除処理関数
  function delete_user ($para) {
    // 変数の初期化
    global $login_user;
    $basedn = 'dc=test,dc=org';

    // 作業ユーザのDN値を取得
    $o_ldap = new ldap();
    $o_ldap->bind (LDAP_SERVER);
    $login_user_entry = $o_ldap->uid_info ($basedn, $login_user);
    $login_user_dn = $login_user_entry['dn'];
    $o_ldap->unbind ();

    // 作業ユーザの権限でLDAPをオープン
    $o_ldap->bind (LDAP_SERVER, $login_user_dn, $para['pass']);

    // ユーザの削除処理
    foreach ( $para['uids'] as $uid ) {
      // uidからユーザ情報の取得
      $del_user_entry = $o_ldap->uid_info ($basedn, $uid);
      $del_user_entry_text = $o_ldap->entry2text ($del_user_entry);

      // 削除対象ユーザのLDAPエントリ情報をファイルに保存
      $fp = fopen (LDAP_ENTRY_DUMP_DIR."/".date('YmdHis')."_".$uid, "w");
      if ($fp) {
        fwrite ($fp, $del_user_entry_text);
        fclose ($fp);
      }

      // ユーザのLDAPアカウントを削除
      logging ($login_user."が要求したアカウント[" . $uid. "]の削除を開始.");
      $o_ldap->delete ($del_user_entry['dn']);
      logging ($login_user."が要求したアカウント[" . $uid. "]の削除を完了.");
    }
  }
?>
