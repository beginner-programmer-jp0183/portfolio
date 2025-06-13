<?php

/*---------------------------------------------------------------------------
			pluginを認識させる
 *---------------------------------------------------------------------------*/


/*
Plugin Name: 巡回プラグラム
Description: 記事を巡回して収集するプラグインです。
Version: 1.0
Author: prayer
*/




/*---------------------------------------------------------------------------
 ダッシュボードウィジェット追加 スクレイピングボタン function.phpに記述しています。
 *---------------------------------------------------------------------------*/

 /*---------------------------------------------------------------------------
 このプログラムについて

 ・自動で、サイトを巡回しながらデータを収集し、記事を自動で作成してくれます。
 ・wordpressのfunction.phpに追加するだけで動作します
 ・ このプログラムは特定のサイトだけに特化したプログラムなため、他のサイトに対応していません。
 
 
 *---------------------------------------------------------------------------*/
//phpqueryを読み込む
 require("phpQuery-onefile.php"); 

 //ダッシュボードにウィジェットを追加
 function add_dashboard_widgets_scrape() {
  wp_add_dashboard_widget(
    'dashboard_widgets2',
    'MyTool2',
    'dashboard_widget_scrape_function_scrape'
  );
}

//////////////////////////////
//URLの情報を取得するための関数
//curlで取得すれば、セキュリティに引っかからず、えらーが起きない
function fetch_content_via_curl($url) {
   $ch = curl_init();

   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

   $content = curl_exec($ch);

   curl_close($ch);

   return $content;
}
///////////////////////////////



//スクレイピングのコード
function dashboard_widget_scrape_function_scrape() {
    
    //記事スクレイピングのボタンが押されたか
    if (isset($_POST["scrape"])) {

    //ページの数値(count)  
    // $num = 0;

    //for文で疑似クローラー
    //pageを変更する
    //画像は番号だけ繰り上げて、urlの型にはめるだけ

    
//===================================================
//下記からクローラー部分
//===================================================
        $num = 0;
        $plus = 1;

        for ($nkst_pc = $num; $nkst_pc < $num+$plus; $nkst_pc++){
            //HTMLデータ取得
            $html = fetch_content_via_curl("https://hogehoge");

            //ページの全体を抽出_DOM操作
            $doc = phpQuery::newDocument($html);
            
                //===================================================
                //タグの抽出
                //===================================================


                // 各項目を格納するための配列を初期化
            $items = array();

            // `listn` クラスを持つ div を検索
            foreach($doc->find('.listn') as $div){
                // phpqueryオブジェクトに変換
                $pqdiv = pq($div);
                
                // 各div内の最初の<a>タグのhref属性を取得
                $check_link = $pqdiv->find('a')->attr('href');

                // URLが"hogehogeかhogehoge2で始まる場合のみ配列に追加
                if (strpos($check_link, "https://test1") === false || strpos($check_link, "http://test2") === false) {
                    $link = $check_link;
                }
                
                // 各div内の最初の<img>タグのsrc属性を取得
                $img = $pqdiv->find('img')->attr('src');
                
                // ユーザー名を取得
                $user = $pqdiv->find('.user a div span')->text();
                
                // それぞれを配列に格納
                $items[] = array('link' => $link, 'img' => $img, 'user' => $user);
            }

            // 結果を表示（あるいは他の操作）
            foreach($items as $item){
                // 各項目の値を取得して操作
                $link = $item['link'];
                $img = $item['img'];
                $user = $item['user'];
    
                // ここで$link, $img, $userを使った操作を行う




                        /////////////////////////////////////
                        //       URLボタン抽出
                        /////////////////////////////////////
                        
                        $html_button = fetch_content_via_curl($link);

                        //ページの全体を抽出_DOM操作
                        $doc1 = phpQuery::newDocument($html_button);

                        $link_button = $doc1->find(' strong a');

                        $href_button = pq($link_button)->attr('href');
                        
                        /* title setting */
                        $p_title = "new page";
                        


    
                        /*記事内に自動で設置するもの */
                        $page_content = <<<EOD
                        <div class="button005">
	                            <a href="{$href_button}">開く</a>
                            </div><br>
                            <div class="button005">
	                            <a href="https://hogehoge/{$user}">詳細</a>
                            </div>
EOD;
                        

                            

                            //連想配列として、代入
                            $my_post2 = array(
                                'post_title'=> $p_title,
                                'post_content' => $page_content,
                                //draft: 非公開状態
                                'post_status'       => 'draft'
                            );

                //===================================================
                //データベースにアップロード
                //===================================================
                            //$p_tagのスペースをカンマに変える
                            //preg_replaceを使わないと複数の空白をカンマに変えれない
                            $comma_separated = preg_replace('/\s+/',',',$user);

                            // 投稿をデータベースへ追加&戻り値からID取得
                            $post_id = wp_insert_post( $my_post2 );



                            //タグをアップロード
                            //$post_idにIDが代入されたら、if文がtrueになり、以下の関数が実行される
                            if($post_id){
                                //tagを追加する
                                wp_set_post_tags($post_id,$comma_separated,true);
                            }
                //===================================================
                //アイキャッチ設定
                //===================================================
                            //アイキャッチの画像URLを取得
                            $image_url = "{$img}";

                            //画像をメディアライブラリに追加
                            //urlの画像をダウンロードし、メディアに追加
                            //この$post_idはメディアと記事を関連付けするもの、実際に投稿されない
                            $media = media_sideload_image($image_url,$post_id,NULL,'id');

                            //成功したら、アイキャッチを記事に設定
                            if(!is_wp_error($media)){
                                //この$post_idはメディアを投稿したい記事のID
                                set_post_thumbnail($post_id,$media);
                            }
                //===================================================
                //時間を開けて、サーバーの負荷を減らす(重要)
                //sleep(1)と設定すると、同じものを抽出するバグは起きにくい、また速く処理できる
                            sleep(1);
                                }
                            
                            }
                        }

    //ボタン追加
    echo '
    <form method="POST">
    <button type="submit" value="val" name="scrape">記事スクレイピング</button>
    </form>
    ';
}

add_action('wp_dashboard_setup', 'add_dashboard_widgets_scrape');


?>
