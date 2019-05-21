<?php
  class ldap
  {
    private $lid;

    function __construct () {
      $this->lid = NULL;
    }
    // エラー発生時の処理
    function on_err ($err_point='') {
      $errno   = ldap_errno ( $this->lid );
      $err2str = ldap_err2str( $errno );

      die ("{$err2str}({$errno}): {$err_point}\n");
    }
    // LDAPサーバにBIND
    function bind ($server, $binddn=NULL, $bindpw=NULL) {
      if ( $this->lid )
        $this->unbind ();

      $this->lid = ldap_connect ($server)
                       or $this->on_err ("ldap_connect");
      ldap_set_option ($this->lid, LDAP_OPT_PROTOCOL_VERSION, 3);
      ldap_bind ($this->lid, $binddn, $bindpw)
          or $this->on_err ("ldap_bind");
    }
    // LDAPサーバとの接続をUNBIND
    function unbind () {
      if ( $this->lid ) {
        ldap_unbind ( $this->lid );
        $this->lid = NULL;
      }
    }
    // LDAPエントリの管理者属性から、指定した管理者を除外する
    function dropManager ($tagetDN, $managerDN) {
      $delAttr['manager'] = $managerDN;
      ldap_mod_del($this->lid, $tagetDN, $delAttr)
          or $this->on_err ('ldap_mod_del');
    }
    // LDAPエントリを削除する
    function delete ($dn) {
      ldap_delete ($this->lid, $dn)
          or $this->on_err ("ldap_delete");
    }
    // uidに対するLDAPエントリの詳細を返す
    function uid_info ($basedn, $uid) {
      $filter="(&(objectClass=posixAccount)(uid={$uid}))";
      $attributes=array('*', '+');

      $r = $this->search ($basedn, $filter, $attributes);
      if ( $r['count'] != 1 )
        return NULL;
      return $r[0];
    }
    // dnに対するLDAPエントリの詳細を返す
    function dn_info ($dn) {
      $filter="(objectClass=posixAccount)";
      $attributes=array('*', '+');

      $r = $this->search ($dn, $filter, $attributes);
      if ( $r['count'] != 1 )
        return NULL;
      return $r[0];
    }
    // LDAPサーバへのサーチ要求と結果を戻す
    function search ($basedn,
                     $filter='(objectClass=*)',
                     $attributes=array('*')) {

      if ( $this->lid ) {
        $rid = ldap_search ($this->lid, $basedn, $filter, $attributes)
                   or $this->on_err ("ldap_search");
        $entries = ldap_get_entries ($this->lid, $rid);

        return $entries;
      }
    }
    // LDAPエントリ情報をTEXT形式に変換する
    function entry2text ( $entry ) {
      $text = $entry['dn']."\n";
      for ($i=0; $i<$entry['objectclass']['count']; $i++) {
        $text .= "  objectclass: " . $entry['objectclass'][$i]."\n";
      }
      for ($i=0; $i<$entry['count']; $i++) {
        $key = $entry[$i];
        for ($j=0; $j<$entry[$key]['count']; $j++)
          $text .= "  " . $key . ": " . $entry[$key][$j]."\n";
      }

      return $text;
    }
  }
?>
