<?php
/**
 * Template : Plugin Option Settings Page
 * URL: `/wp-admin/admin.php?page=cdbt_options`
 *
 * @since 2.0.2
 *
 */

$this->destroy_session();
$_local_code = defined('WPLANG') ? '-' . WPLANG : '';

$contribute_extends = [
  'jQuery' => [ 'url' => 'https://jquery.com/', 'version' => '2.1.4' ], 
  'jQuery UI' => [ 'url' => 'http://jqueryui.com/', 'version' => '1.11.4' ], 
  'modernizr.js' => [ 'url' => 'https://modernizr.com/', 'version' => '3.1.0' ], 
  'Bootstrap' => [ 'url' => 'http://getbootstrap.com/', 'version' => '3.3.6' ], 
  'Underscore.js' => [ 'url' => 'http://underscorejs.org/', 'version' => '1.8.3' ], 
  'Fuel UX' => [ 'url' => 'http://getfuelux.com/', 'version' => '3.11.5' ], 
  'moment.js' => [ 'url' => 'http://momentjs.com/', 'version' => '2.10.6' ], 
  'Font Awesome' => [ 'url' => 'http://fortawesome.github.io/Font-Awesome/', 'version' => '4.4.0' ], 
];

$_contribute_list = [];
foreach ($contribute_extends as $_key => $_val) {
  $_contribute_list[] = sprintf('<li><a href="%s" target="_blank">%s</a> %s </li>', esc_url($_val['url']), $_key, $_val['version']);
}

/**
 * Render html
 * ---------------------------------------------------------------------------
 */
?>
<div class="wrap">
  <h2><i class="image-icon cdbt-icon square32"></i><?php esc_html_e('CDBT Management Console', CDBT); ?></h2>
  
  <div class="introduction">
    <p><?php _e('Welcome to the "Custom DataBase Tables" plugin! In this page is introductions about feature of plugin, and be able to go short trip as the tutorial.', CDBT); ?></p>
  </div>
  
  <?php
  /**
   * Define the localized variables for tab of `wizard`
   */
  $_p_begin = '<p class="paragraph'. $_local_code .'">';
  $_p_fin = '</p>';
  // Step1 section
  $step1_content = '<section class="cdbt-wizard-content"><div class="pull-left" style="margin: 1em 1.5em 1em 0;"><i class="image-icon cdbt-icon-v1 square96 pull-left" style="margin-top: 10px;"></i><i class="fa fa-arrow-right text-danger" style="margin: 50px 10px 0;"></i><i class="image-icon cdbt-logo square128 pull-right"></i></div>';
  //$step1_content .= __('We were waiting very long you! Finally CustomDataBaseTable (hereinafter referred to as CDBT) is a plug-in major version up version V2 appeared.<br>In CDBT V2 finally ability to manage the core table of WordPress has been added. This, it should be to be able to defeat use the WordPress more CMS basis.<br>I aim to start this CDBT plug-ins, you can completely customize the login system, scheduled for release next "CustomLoginSuites (provisional)" or, you can easily add the original setting screen on the management screen "AnythingSetup (provisional)" , by using, for example, to protect the posts and media to cooperation "ProtectPostsPower (provisional)", it is to the WordPress and strongest of CMS.<br>First of all, please enjoy the CDBT plug-in is the first stage of the project!<br>', CDBT);
  //$step1_content .= __('However, it is not yet in the release version.<br>Degree of completion of the current CDBT V2 is about <strong style="font-size: 32px; color: #dc4c3a;">98%</strong>.<br>Please wait for until complete.', CDBT);
  $step1_content .= $_p_begin. 'お待たせしました！ ようやく「CustomDataBaseTable（以降、通称のCDBTと呼称します）」プラグインのメジャーバージョンアップ版「バージョン2（以降、V2と呼称します）」が登場しました。' .$_p_fin;
  $step1_content .= $_p_begin. 'CDBTはWordPressサイトが利用しているMySQLデータベースに独自のテーブルを作成して、データを管理し、テーマや外部サイトとのデータ連携を行うためのプラグインです。このプラグインを利用することで、データベースの詳しい知識を持っていなくても直感的にテーブルやデータを取り扱うことができるようになります。このプラグインによってオリジナルのデータ格納領域が追加されたサイトは、あなたが企図するプロジェクトの幅を驚異的に拡張してくれるでしょう。' .$_p_fin;
  $step1_content .= $_p_begin. 'そして、CDBT V2ではついにWordPressのコアテーブルを管理できる機能が追加されました。これによって、WordPressをよりCMS的に使い倒すことができるようになるはずです。' .$_p_fin;
  //$step1_content .= $_p_begin. '私が目指しているのは、このCDBTプラグインをはじめ、次にリリースを予定しているログイン系の処理をフルカスタマイズできる「Custom Login Suites（仮）」や、管理画面にオリジナルの設定画面を簡単に追加できる「Custom Anything Setup（仮）」、任意の投稿やメディアを強固に保護する「Custom Posts Shield（仮）」などを統合的に組み合わせることで、WordPressを最強のCMSとすることです。' .$_p_fin;
  //$step1_content .= $_p_begin. 'まずは、そのプロジェクトの第一段階であるCDBT V2をご堪能ください！' .$_p_fin;
  $step1_content .= $_p_begin. 'なお、CDBT V2のご利用には<strong>PHP5.4以上の環境</strong>が必要になります。また、本プラグインでは下記の外部ライブラリを使用します。' .$_p_fin;
  $step1_content .= '<ul class="contribute-extends list-inline">'. implode('', $_contribute_list) .'</ul>';
  $step1_content .= '</section>';
  // Step2 section
  $step2_content = '<section class="cdbt-wizard-content"><div class="pull-right"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_1.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step2_content .= $_p_begin. 'CDBT V2ではWordPressのコアテーブル管理の他にも新しい機能がたくさん追加されました。そして、従来の機能についても処理の内部を見直して、使いやすいインターフェースへ刷新することで、大幅な機能強化を行いました。' .$_p_fin;
  $step2_content .= $_p_begin. 'V2で追加された新しい機能は下記の通りです。<br><ol><li>WordPressコアテーブルの管理機能</li><li>テーブルの複製機能</li><li>ショートコードの編集と保存機能</li><li>WebAPIの編集機能</li><li>デバッグモードの実装</li></ol><br>' .$_p_fin;
  $step2_content .= '<div class="clearfix"></div><div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_2.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step2_content .= $_p_begin. 'そして、大幅に強化された機能は下記の通りです。<br><ol style="list-style-position: inside;"><li>テーブルデータのインポート／エクスポートのファイル種類を追加</li><li>テーブルクリエイターの機能強化</li><li>ショートコードの外観をFuelUXのリピーター形式に変更</li><li>テーブルの詳細情報表示の精密化</li><li>管理画面のインターフェースの刷新</li></ol><br>' .$_p_fin;
  $step2_content .= '</section>';
  // Step3 section
  $step3_content = '<section class="cdbt-wizard-content">';
  $step3_content .= '<div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_3.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step3_content .= $_p_begin. 'それでは、まずCDBT V2で新しいテーブルを作成してみましょう。' .$_p_fin;
  $step3_content .= $_p_begin. 'まず、WordPressの管理メニューから「テーブル管理」、「テーブル作成」タブの順にクリックしてください。「データベース用テーブル設定」の画面が表示されたら、作成したいテーブル名を入力しましょう。' .$_p_fin;
  $step3_content .= $_p_begin. 'その後、テーブルの文字コードやデータベースエンジンを選び、テーブル作成SQL欄の右端にある「設定値からSQL作成」ボタンをクリックしてみてください。SQL欄に作成するテーブルのSQL文が自動で生成されました。基本的なテーブル作成操作はこの流れになります。' .$_p_fin;
  $step3_content .= $_p_begin. 'このまま「テーブル作成」を実行してもテーブルが作られますが、それではあなたのデータを格納する棚（カラム）がありません。そこで、次はテーブルにカラムを追加してみましょう。' .$_p_fin;
  $step3_content .= '<div class="clearfix"></div><div class="pull-right"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_4.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step3_content .= $_p_begin. '「テーブル作成SQL」欄の「テーブルクリエーター」タブをクリックすると、新しいダイアログが開いて、カラムを追加・編集できる専用エディッタが表示されます。' .$_p_fin;
  $step3_content .= $_p_begin. 'テーブルクリエーターでは、カラム名を入力し、そのカラムに格納するデータ形式を選ぶことで、設定可能な項目欄が切り替わります。また、カラムの並び順を行ごとにドラッグ＆ドロップで変更することもできます。このエディッタを利用することでデータベースの知識がなくても自由自在にテーブルを編集することができるのです。' .$_p_fin;
  $step3_content .= $_p_begin. 'カラム編集が終わったら、忘れずに「SQLを適用する」をクリックしてください。これによってあなたが編集したカラム設定はブラウザに保存され、ダイアログを閉じて開きなおした時でもカラム編集作業を再開できるようになります。' .$_p_fin;
  $step3_content .= '<div class="clearfix"></div><div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_5.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step3_content .= $_p_begin. '「プラグイン用テーブル設定」のセクションでは、あなたが作成したテーブルをCDBT V2で利用する時の設定を行います。主にショートコードを使ってテーブルを操作する場合向けの設定になります。なお、この設定はテーブル作成後にいつでも変更が可能です。' .$_p_fin;
  $step3_content .= $_p_begin. 'さらに、別のプラグインなどであなたが独自に作成したテーブルをCDBT V2プラグインに取り込んで、CDBT V2で管理できるようにすることもできます。その場合は「既存のテーブルを取り込む」のセクションから、取り込みたいテーブルを選んでください。' .$_p_fin;
  $step3_content .= $_p_begin. '<div style="margin-top: 3em;"><a href="/wp-admin/admin.php?page=cdbt_tables&tab=create_table" class="btn btn-default pull-right">Go To Table Creation</a></div>' .$_p_fin;
  $step3_content .= '</section>';
  // Step4 section
  $step4_content = '<section class="cdbt-wizard-content">';
  $step4_content .= '<div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_6.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step4_content .= $_p_begin. 'CDBT V2で管理しているテーブルは、ショートコードを使用することで、サイトのフロントエンドにデータ内容を表示したり、フロントエンドからデータを登録できたりできます。このショートコードを利用することで、あなたのサイトに訪れるユーザーにインタラクティブなデータベース利用を提供することができます。' .$_p_fin;
  $step4_content .= $_p_begin. 'ユーザーと協力してコンテンツやデータを収集・蓄積したり、独自のユーザー管理テーブルとしてCRM的なテーブルを構築することも可能になるのです。' .$_p_fin;
  $step4_content .= $_p_begin. 'ショートコードによって出力されるコンテンツの外観は利用シーンに合わせて管理画面からフルカスタマイズが可能になっています。さらに、あなたのオリジナル設定のショートコードはプラグインに登録しておくこともでき、登録されたショートコードには複雑な属性設定が省略されたエイリアスコードが発行されます。実際に利用する際は、そのエイリアスコードを投稿や固定ページに貼り付けるだけです。' .$_p_fin;
  $step4_content .= '</section>';
  // Step5 section
  $step5_content = '<section class="cdbt-wizard-content">';
  $step5_content .= '<div class="pull-left"><img src="'. $this->plugin_url .'assets/images/cdbt_v2_image_7.png" class="img-rounded cdbt-short-trip-img"></div>';
  $step5_content .= $_p_begin. 'CDBT V2で管理しているテーブルのデータにはWeb APIを利用することで、外部のサイトからアクセスすることができます。そのためには「WEB API管理」からアクセスを許可するサイトを登録する必要があります。' .$_p_fin;
  $step5_content .= $_p_begin. __('Since not yet written content, please wait.', CDBT) .$_p_fin;
  $step5_content .= '</section>';
  
  $conponent_options = [
    'id' => 'cdbt-wizard', 
    'defaultStep' => 1, 
    'currentStep' => 1, 
    'displayMaxStep' => 5, 
    'stepLabels' => [ __('Step1', CDBT), __('Step2', CDBT), __('Step3', CDBT), __('Step4', CDBT), __('Step5', CDBT) ], 
    'stepContents' => [ 
      [ 'title' => __('Custom DataBase Tables version 2 arrival now!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step1_content ], 
      [ 'title' => __('Version 2 has powered up all feature!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step2_content ], 
      [ 'title' => __('Let&#39;s create a new table!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step3_content ], 
      [ 'title' => __('Let&#39;s use the shortcode!', CDBT), 'bgcolor' => 'bg-default', 'content' => $step4_content ], 
      [ 'title' => __('Cooperation with external site by API', CDBT), 'bgcolor' => 'bg-default', 'content' => $step5_content ], 
    ], 
    'disablePreviousStep' => false, 
  ];
  $this->component_render('wizard', $conponent_options); // by trait `DynamicTemplate`
  
  ?>
  
  <div class="panel panel-default donate-info">
    <div class="panel-heading"><span class="glyphicon glyphicon-heart" style="color: #f33;"></span> <?php esc_html_e( 'About Custom DataBase Tables', CDBT ); ?></div>
    <div class="panel-body">
      <p><?php printf( __('Custom DataBase Tables is provided an extensive %sdocumentations%s. It includes Frequently Asked Questions for you to use in plugins and themes, as well as documentation for further details about how to use for programmers.', CDBT), '<a href="https://ka2.org/cdbt/documentation/" target="_blank" alt="CDBT Documentations">', '</a>' ); ?>
      <?php printf( __('If you wonder how you can help the project, just %sread this%s.', CDBT), '<a href="https://ka2.org/cdbt/tutorials/" target="_blank" alt="CDBT Tutorials">', '</a>' ); ?>
      <?php printf( __('Custom DataBase Table is free of charge and is released under the same license as WordPress, the %sGPL%s.', CDBT), '<a href="http://www.gnu.org/licenses/gpl-2.0.html" target="_blank" alt="GPL 2.0">', '</a>' ); ?></p>
      <p class="pull-left"><?php printf( __('You will also find useful information in the %ssupport forum%s. However don&apos;t forget to make a search before posting a new topic.', CDBT), '<a href="https://wordpress.org/support/plugin/custom-database-tables" target="_blank" alt="CDBT Support Forum">', '</a>' ); ?>
      <?php esc_html_e( 'Finally if you like this plugin or if it helps your business, donations to the author are greatly appreciated.', CDBT ); ?></p>
      <div class="clearfix"></div>
      <ul class="list-inline donate-links">
      <?php if (in_array($_local_code, [ 'ja',  ])) : ?>
        <li class="donate-paypal"><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
          <input type="hidden" name="cmd" value="_donations">
          <input type="hidden" name="business" value="2YZY4HWYSWEWG">
          <input type="hidden" name="lc" value="en_US">
          <input type="hidden" name="currency_code" value="USD">
          <input type="hidden" name="item_name" value="Donate to CustomDataBaseTable">
          <!-- input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - <?php esc_html_e( 'The safer, easier way to pay online!', CDBT ); ?>" -->
          <button type="submit" name="submit" alt="PayPal - <?php esc_html_e( 'The safer, easier way to pay online!', CDBT ); ?>" class="btn btn-primary"><i class="fa fa-paypal"></i> Donate Paypal</button>
          <img alt="" border="0" src="https://www.paypalobjects.com/ja_JP/i/scr/pixel.gif" width="1" height="1">
        </form></li>
      <?php endif; ?>
        <li class="donate-blockchain"><div style="font-size:16px;margin:0 auto;width:300px" class="blockchain-btn" data-address="1821oc4XvWrfiwfVcNCAKEC8gppcrab4Re" data-shared="false">
          <div class="blockchain stage-begin">
            <img src="https://blockchain.info/Resources/buttons/donate_64.png"/>
          </div>
          <div class="blockchain stage-loading" style="text-align:center">
            <img src="https://blockchain.info/Resources/loading-large.gif"/>
          </div>
          <div class="blockchain stage-ready">
            <p align="center"><?php _e('Please Donate To Bitcoin Address:', CDBT);?> <b>[[address]]</b></p>
            <p align="center" class="qr-code"></p>
          </div>
          <div class="blockchain stage-paid">
            Donation of <b>[[value]] BTC</b> Received. Thank You.
          </div>
          <div class="blockchain stage-error">
            <font color="red">[[error]]</font>
          </div>
        </div></li>
<?php /*
        <li class="donate-coinbase hide">
          <a class="coinbase-button" data-code="219e4dae601d44bd7c2766178aff9471" data-button-style="custom_small" data-custom="CDBTV2" href="https://www.coinbase.com/checkouts/219e4dae601d44bd7c2766178aff9471">Donate Bitcoins</a><script src="https://www.coinbase.com/assets/button.js" type="text/javascript"></script>
        </li>
*/ ?>
      </ul>
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
  
  <div class="panel panel-default other-note">
    <div class="panel-heading"><i class="fa fa-check-circle-o"></i> <?php esc_html_e( 'CustomDataBaseTables License Agreement', CDBT ); ?></div>
    <div class="panel-body">
      <p>Copyright (c) 2014 - 2015, ka2 ( <a href="https://ka2.org/" target="_blank">https://ka2.org</a> )</p>
      <p>This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, version 2, as published by the Free Software Foundation.</p>
      <p>This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.</p>
      <p>You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA</p>
    </div><!-- /.panel-body -->
  </div><!-- /.panel -->
  
</div><!-- /.wrap -->