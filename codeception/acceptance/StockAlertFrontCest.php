<?php

/*
 * 在庫アラート機能(課題)の受け入れテスト: フロント編
 *
 * このテストファイルは変更不要です。すべて通ればフロント側の実装は完了です。
 *
 * 前提: StockAlertAdminCest で「彩のジェラートCUBE」の
 *   ・チョコ×16mm×16mm  : 在庫1 / 閾値3 (残りわずかになるはず)
 *   ・バニラ×16mm×16mm  : 在庫100 / 閾値3 (残りわずかにならないはず)
 * が設定されていること。管理画面テストと同じデータを使うため、
 * このCestは StockAlertAdminCest より後に実行する必要がある。
 */

use Page\Admin\ProductClassEditPage;
use Page\Admin\ProductEditPage;
use Page\Admin\ProductManagePage;
use Page\Front\ProductDetailPage;

/**
 * @group stock_alert
 */
class StockAlertFrontCest
{
    const 商品ID = 1; // 彩のジェラートCUBE
    const 商品名 = '彩のジェラートCUBE';

    public function _before(AcceptanceTester $I)
    {
        // 管理画面側のテストと独立に実行されても成立するよう、ここでも管理画面から
        // 在庫と閾値を設定しておく(StockAlertAdminCestと同じ内容)。
        $I->loginAsAdmin();

        ProductManagePage::go($I)
            ->検索(self::商品名)
            ->検索結果_選択(1);
        ProductEditPage::at($I)->規格管理();
        ProductClassEditPage::at($I)
            ->無効_在庫数無制限(1)
            ->入力_個数(1, 1)
            ->入力_在庫アラート閾値(1, 3)
            ->無効_在庫数無制限(7)
            ->入力_個数(7, 100)
            ->入力_在庫アラート閾値(7, 3)
            ->登録();
        $I->waitForElement(ProductClassEditPage::$登録完了メッセージ);

        $I->logoutAsAdmin();
    }

    public function 在庫が閾値以下の規格を選択すると残りわずかと表示される(AcceptanceTester $I)
    {
        $I->wantTo('商品詳細画面で在庫が閾値以下の規格を選ぶと「残りわずか」が表示される');

        ProductDetailPage::go($I, self::商品ID)
            ->規格選択(['チョコ', '16mm × 16mm'])
            ->在庫アラート表示確認();
    }

    public function 規格を切り替えると残りわずかの表示が追従する(AcceptanceTester $I)
    {
        $I->wantTo('規格を切り替えると「残りわずか」の表示/非表示が追従する');

        $page = ProductDetailPage::go($I, self::商品ID)
            ->規格選択(['チョコ', '16mm × 16mm'])
            ->在庫アラート表示確認();

        // フレーバーを「バニラ」に切り替える(サイズは16mm×16mmのまま) = 在庫が十分にある規格
        $page
            ->規格選択(['バニラ', '16mm × 16mm'])
            ->在庫アラート非表示確認();

        // 再度「チョコ」に戻すと表示が復活する
        $page
            ->規格選択(['チョコ', '16mm × 16mm'])
            ->在庫アラート表示確認();
    }
}
