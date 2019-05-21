<?php
  class excludeReserv
  {
    private $dir;
    private $data;

    function __construct ($reserv_dir, $history_dir) {
      $this->dir = array();
      $this->dir['reserv']  = $reserv_dir;
      $this->dir['history'] = $history_dir;
      $this->data = array(
          'UpdateTime'   => NULL,
          'ApplicantUid' => NULL,
          'ApplicantJPN' => NULL,
          'Status'       => 'unconfirmed',
          'UidList'      => array()
      );
    }
    // エラー発生時の処理
    function on_err ($err_point='') {

      die ("{$err_point}\n");
    }
    // 申請者の申請ファイルをロード
    function _load ($applicant) {
      $openfile = $this->dir['reserv'] . '/' . $applicant;
      $fp = fopen ($openfile, "r");
      if ($fp) {
        while ($line = fgets($fp)) {
          $dpoint = strpos ($line, ' ');
          $key    = substr($line, 0, $dpoint);
          $val    = rtrim (substr($line, $dpoint + 1));
          switch ($key) {
            case 'UpdateTime':
              $this->data['UpdateTime'] = $val;
              break;
            case 'ApplicantUid':
              $this->data['ApplicantUid'] = $val;
              break;
            case 'ApplicantJPN':
              $this->data['ApplicantJPN'] = $val;
              break;
            case 'Status':
              $this->data['Status'] = $val;
              break;
            case 'Uid':
              array_push ($this->data['UidList'], $val);
              break;
          }
          $arr = array_unique ($this->data['UidList']);
          $this->data['UidList'] = array_values ($arr);
        }
        fclose ($fp);
      } else {
        return FALSE;
      }
      return TRUE;
    }
    // 申請者の申請ファイルをセーブ
    function _save ($applicant) {
      $this->data['UpdateTime'] = date('Y-m-d H:i:s');

      $openfile = $this->dir['reserv'] . '/' . $applicant;
      $fp = fopen ($openfile, "w");
      if ($fp) {
        fwrite ($fp, "UpdateTime {$this->data['UpdateTime']}\n");
        fwrite ($fp, "ApplicantUid {$this->data['ApplicantUid']}\n");
        fwrite ($fp, "ApplicantJPN {$this->data['ApplicantJPN']}\n");
        fwrite ($fp, "Status {$this->data['Status']}\n");
        foreach ($this->data['UidList'] as $Uid)
          fwrite ($fp, "Uid {$Uid}\n");
        fclose ($fp);
      }
    }

    // 申請者の申請状況を取得する
    function getReservContent ($applicant) {
      $this->_load ($applicant);
      return $this->data;
    }
    // 申請者の申請ファイルを更新する
    function updateReservUser ($applicant, $uid, $checked) {
      $this->_load ($applicant);
      $this->data['ApplicantUid'] = $applicant;
      if ( $checked == 1 ) {
        array_push ($this->data['UidList'], $uid);
      } else {
        $index = array_search ($uid, $this->data['UidList']);
        if ( $index !== FALSE )
          unset ( $this->data['UidList'][$index] );
      }
      $arr = array_unique ($this->data['UidList']);
      $this->data['UidList'] = array_values ($arr);
      $this->_save ($applicant);
    }
    // 申請者の申請状態を更新する
    function updateReservStatus ($applicant, $applicantJP, $Status) {
      $this->_load ($applicant);
      $this->data['ApplicantUid'] = $applicant;
      $this->data['ApplicantJPN'] = $applicantJP;
      $this->data['Status']       = $Status;
      $this->_save ($applicant);
    }
    // 申請済みの申請一覧を取得する
    function getFixedReservContent () {
      $applicantList = array();
      if ( is_dir( $this->dir['reserv'] ) && $handle = opendir( $this->dir['reserv'] ) ) {
        while( ($applicant=readdir($handle)) !== false ) {
          $r = $this->_load ($applicant);
          if ( $r==TRUE && $this->data['Status']=='confirmed') {
            array_push ($applicantList, $this->data);
          }
        }
      }
      return $applicantList;
    }
    // 申請ファイルをヒストリディレクトリに移動
    function moveHistory ($applicant) {
      $fromFile = $this->dir['reserv']  . '/' . $applicant;
      $toFile   = $this->dir['history'] . '/' . date('YmdHis') . "_" . $applicant;

      rename ($fromFile, $toFile)
          or $this->on_err("move err: [from]{$fromFile} [to]{$toFile}");
    }
  }
?>
