<?php
$options = get_option($this->core->domain_name);
//var_dump($options);

// 暫定処理
if (!array_key_exists('uninstall_options', $options)) $options['uninstall_options'] = false;
if (!array_key_exists('resume_options', $options)) $options['resume_options'] = false;
if (!array_key_exists('enable_core_tables', $options)) $options['enable_core_tables'] = false;
if (!array_key_exists('debug_mode', $options)) $options['debug_mode'] = false;
if (!array_key_exists('default_db_engine', $options)) $options['default_db_engine'] = 'InnoDB';
if (!array_key_exists('default_per_records', $options)) $options['default_per_records'] = 10;

$default_action = 'update';
?>
<div class="wrap">
  <h2><?php esc_html_e('CDBT Plugin Options', $this->core->domain_name); ?></h2>
  
  <div class="introduction">
    <p><?php /* esc_html_e('The "Custom DataBase Tables" is ...', $this->core->domain_name); */ ?>
      この設定ページでは、「Custom DataBase Tables」プラグインの動作全体に影響する<strong>共通設定</strong>を編集できます。<br>
    </p>
  </div>
  
  <div class="cdbt-general-options">
    <form method="post" action="<?php echo esc_url(add_query_arg([ 'page' => $this->query['page'] ])); ?>">
      <input type="hidden" name="page" value="<?php echo $this->query['page']; ?>">
      <input type="hidden" name="action" value="<?php echo $default_action; ?>">
      <?php wp_nonce_field( 'cdbt_management_console-' . $this->query['page'] ); ?>
      <h3 class="title">プラグインの設定</h3>
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label for="option-item-1">設定値のクリーニング</label>
            </th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span>設定値のクリーニング</span></legend>
                <label for="option-item-1">
                  <input name="<?php echo $this->core->domain_name; ?>[cleaning_options]" type="checkbox" id="option-item-1" value="1" <?php checked('1', $options['cleaning_options']); ?>>
                  共通設定を保存する時に、データベースに存在しないテーブル設定を削除するなど、設定値のクリーニングを行う。<br>
                </label>
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="option-item-4">アンインストール設定</label>
            </th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span>アンインストール設定</span></legend>
                <label for="option-item-4">
                  <input name="<?php echo $this->core->domain_name; ?>[uninstall_options]" type="checkbox" id="option-item-4" value="1" <?php checked('1', $options['uninstall_options']); ?>>
                  このプラグインをアンインストールする時に、プラグインに関わるすべての設定情報を削除する（作成したテーブルは削除されません）。<br>
                </label>
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="option-item-7">管理テーブルの復元</label>
            </th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span>管理テーブルの復元</span></legend>
                <label for="option-item-7">
                  <input name="<?php echo $this->core->domain_name; ?>[resume_options]" type="checkbox" id="option-item-7" value="1" <?php checked('1', $options['resume_options']); ?>>
                  過去のプラグイン設定から管理対象テーブルを再設定します。ただし、復元時にデータベースに存在していないテーブルは復旧できません。<br>
                </label>
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="option-item-10">WordPressコアテーブル管理</label>
            </th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span>WordPressコアテーブル管理</span></legend>
                <label for="option-item-10">
                  <input name="<?php echo $this->core->domain_name; ?>[enable_core_tables]" type="checkbox" id="option-item-10" value="1" <?php checked('1', $options['enable_core_tables']); ?>>
                  WordPressのコアテーブルを管理対象テーブルにします。テーブル管理から、データの閲覧、登録、編集、インポート／エクスポートが行えるようになります。 <?php \CustomDataBaseTables\Common\during_trial('enable_core_tables'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="option-item-15">デバッグモード</label>
            </th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span>デバッグモード</span></legend>
                <label for="option-item-15">
                  <input name="<?php echo $this->core->domain_name; ?>[debug_mode]" type="checkbox" id="option-item-13" value="1" <?php checked('1', $options['debug_mode']); ?>>
                  デバッグモードを有効にすると、プラグインで発生したエラーがログとして出力されます。不具合の調査を行う時などに利用してください。 <?php \CustomDataBaseTables\Common\during_trial('debug_mode'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
        </tbody>
      </table>
      <br>
      <h3 class="title">テーブル作成時の設定</h3>
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label for="option-item-21">テーブル接頭辞</label>
            </th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span>テーブル接頭辞</span></legend>
                <label for="option-item-21">
                  <input name="<?php echo $this->core->domain_name; ?>[use_wp_prefix]" type="checkbox" id="option-item-21" value="1" <?php checked('1', $options['use_wp_prefix']); ?>>
                  新しいテーブルを作成する時にテーブル名にWordPressの設定（wp-config.php）で定義されているテーブル接頭辞<code><?php global $wpdb; echo $wpdb->prefix; ?></code>を自動付与する。<br>
                </label>
                <p class="helper-notice">なお、この設定はテーブル作成時に個別に変更可能です。</p>
              </fieldset>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="option-item-22">テーブルの文字コード</label>
            </th>
            <td>
              <input id="option-item-22" type="text" name="<?php echo $this->core->domain_name; ?>[charset]" value="<?php esc_attr_e($options['charset']); ?>" aria-describedby="item-22-description" class="reqular-text code">
              <p class="description" id="item-22-description">このプラグインで作成するテーブルの文字コードの初期値となります。<a href="#foot-note-1" class="note-link"><span class="dashicons dashicons-info"></span></a> <?php \CustomDataBaseTables\Common\during_trial('default_charset'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="option-item-23">データ登録時のタイムゾーン</label>
            </th>
            <td>
              <input id="option-item-23" type="text" name="<?php echo $this->core->domain_name; ?>[timezone]" value="<?php esc_attr_e($options['timezone']); ?>" aria-describedby="item-23-description" class="reqular-text code" __disabled="disabled">
              <span class="inline-description">現在利用中のMySQLデータベースのタイムゾーン: <code><?php echo apply_filters( 'sanitize_option_timezone_string', $options['timezone'], 'timezone_string'); ?></code></span>
              <p class="description" id="item-23-description">このプラグインからdatetime型のデータをテーブルに格納する時に、設定されたタイムゾーンに応じて値をローカライズします。<?php \CustomDataBaseTables\Common\during_trial('localize_timezone'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="option-item-24">初期データベースエンジン</label>
            </th>
            <td>
              <select id="option-item-24" name="<?php echo $this->core->domain_name; ?>[default_db_engine]" aria-describedby="item-24-description">
                <option value="InnoDB" <?php selected('InnoDB', $options['default_db_engine']); ?>>InnoDB</option>
                <option value="MyISAM" <?php selected('MyISAM', $options['default_db_engine']); ?>>MyISAM</option>
              </select>
              <p class="description" id="item-24-description">このプラグインで作成するテーブルのデータベースエンジンの初期値となります。<a href="#foot-note-1" class="note-link"><span class="dashicons dashicons-info"></span></a> <?php \CustomDataBaseTables\Common\during_trial('default_db_engine'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="option-item-25">初期表示レコード数</label>
            </th>
            <td>
              <input id="option-item-25" type="number" name="<?php echo $this->core->domain_name; ?>[default_per_records]" step="1" min="1" value="<?php echo intval($options['default_per_records']); ?>" aria-describedby="item-25-description" class="small-text">
              <p class="description" id="item-25-description">このプラグインで作成するテーブルの1ページに表示されるレコード数の初期値となります。<a href="#foot-note-1" class="note-link"><span class="dashicons dashicons-info"></span></a> <?php \CustomDataBaseTables\Common\during_trial('default_per_records'); ?></p>
            </td>
          </tr>

<!--
input.reguler-text { width: 25em; }
input.code { padding-top: 6px; } # for URL safe alnum
input[type=email], input[type=url], input.ltr { direction: ltr; } # for email, password
input.small-text { width: 50px; padding: 1px 6px }
input[type=number].small-text { width: 65px } # maxlength 4
textarea.code { line-height: 1.4; padding: 4px 6px 1px; }
input.large-text, textarea.large-text { width: 99%; }
-->
<!--
# for text field
<input id="option-item-1" type="text" name="" value="" aria-describedby="***-description" class="regular-text">
-->
<!--
# for numlic
<input name="posts_per_page" type="number" step="1" min="1" id="***" value="10" class="small-text">
-->
<!--
# for textarea
<fieldset>
  <legend class="screen-reader-text"><span>コメントブラックリスト</span></legend>
  <p>
    <label for="blacklist_keys">コメントの内容、名前、URL、メールアドレス、IP に以下の単語のうちいずれかでも含んでいる場合、そのコメントはスパムとしてマークされます。各単語や IP は改行で区切ってください。単語内に含まれる語句にもマッチします。例: “press” は “WordPress” にマッチします。</label>
  </p>
  <p>
    <textarea name="blacklist_keys" rows="10" cols="50" id="blacklist_keys" class="large-text code"></textarea>
  </p>
</fieldset>
-->
<!--
# for single checkbox
<fieldset>
  <legend class="screen-reader-text"><span>{label name}</span></legend>
  <label for="option-item-n">
    <input name="" type="checkbox" id="option-item-n" value="1">
    ********
  </label>
</fieldset>
-->
<!--
# for multi checkbox
<fieldset>
  <legend class="screen-reader-text"><span>整形</span></legend>
  <label for="use_smilies">
    <input name="use_smilies" type="checkbox" id="use_smilies" value="1" checked="checked">
    <code>:-)</code> や <code>:-P</code> のような顔文字を画像に変換して表示する
  </label>
  <br>
  <label for="use_balanceTags">
    <input name="use_balanceTags" type="checkbox" id="use_balanceTags" value="1">
     不正にネスト化した XHTML を自動的に修正する
  </label>
</fieldset>
-->
<!--
# .inline-description { padding-left: 25px; } # must add to style

# for selectbox
<select name="default_role" id="default_role">
  <option selected="selected" value="subscriber">購読者</option>
  <option value="contributor">寄稿者</option>
  <option value="author">投稿者</option>
  <option value="editor">編集者</option>
  <option value="administrator">管理者</option>
</select><span id="inline-description">*****</span>
-->
<!--
# for radio button
<fieldset>
  <legend class="screen-reader-text"><span>日付のフォーマット</span></legend>
  <label title="Y年n月j日"><input type="radio" name="date_format" value="Y年n月j日" checked="checked"> 2015年5月11日</label><br>
  <label title="Y-m-d"><input type="radio" name="date_format" value="Y-m-d"> 2015-05-11</label><br>
  <label title="m/d/Y"><input type="radio" name="date_format" value="m/d/Y"> 05/11/2015</label><br>
  <label title="d/m/Y"><input type="radio" name="date_format" value="d/m/Y"> 11/05/2015</label><br>
  <label><input type="radio" name="date_format" id="date_format_custom_radio" value="\c\u\s\t\o\m"> カスタム:<span class="screen-reader-text"> 以下の欄にカスタマイズした日付の書式を入力してください</span></label>
  <label for="date_format_custom" class="screen-reader-text">カスタム日付書式:</label>
  <input type="text" name="date_format_custom" id="date_format_custom" value="Y年n月j日" class="small-text"> <span class="screen-reader-text">例: </span><span class="example"> 2015年5月11日</span> <span class="spinner"></span>
</fieldset>
-->
        </tbody>
      </table>
      <ul id="foot-note-1" class="foot-note">
        <li><span class="dashicons dashicons-info"></span> すでに作成済みのテーブルには反映されません。変更したい場合は個別にテーブル設定を変更してください。</li>
      </ul>
      <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes', $this->core->domain_name); ?>">
      </p>
    </form>
<!--
    <form method="post" action="" novalidate="novalidate">
      <input type="hidden" name="admin_page" value="">
      <input type="hidden" name="action" value="resume">
      <input type="hidden" id="_wpnonce" name="_wpnonce" value="">
      <input type="hidden" name="_wp_http_referer" value="">
      <p class="resume" style="position: relative; top: -60px; left: 140px;">
        <input type="submit" name="resume" id="resume" class="button button-default" value="<?php _e('Resume Tables', $this->core->domain_name); ?>">
      </p>
    </form>
-->
  </div>
  
</div><!-- /.wrap -->