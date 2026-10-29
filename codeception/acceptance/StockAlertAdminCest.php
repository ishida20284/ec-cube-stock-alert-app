<?php

/*
 * 在庫アラート機能(課題)の受け入れテスト: 管理画面編
 *
 * このテストファイルは変更不要です。すべて通れば管理画面側の実装は完了です。
 */

use Page\Admin\ProductClassEditPage;
use Page\Admin\ProductEditPage;
use Page\Admin\ProductManagePage;

/**
 * @group admin
 * @group stock_alert
 */
class StockAlertAdminCest
{
    /** デモデータの商品名(規格1: フレーバー, 規格2: サイズ) */
    const 商品名 = '彩のジェラートCUBE';

    public function _before(AcceptanceTester $I)
    {
        $I->loginAsAdmin();
    }

    /**
     * 商品一覧から対象商品を選び, 商品規格編集画面を開く.
     */
    private function 商品規格編集画面を開く(AcceptanceTester $I): ProductClassEditPage
    {
        ProductManagePage::go($I)
            ->検索(self::商品名)
            ->検索結果_選択(1);
        ProductEditPage::at($I)->規格管理();

        return ProductClassEditPage::at($I);
    }

    public function 商品規格ごとに在庫アラート閾値を入力保存できる(AcceptanceTester $I)
    {
        $I->wantTo('商品規格編集画面で在庫アラート閾値を入力・保存できる');

        $ProductClassEditPage = $this->商品規格編集画面を開く($I);

        // チョコ×16mm×16mm: 在庫を少なくして閾値以下にする
        $ProductClassEditPage
            ->無効_在庫数無制限(1)
            ->入力_個数(1, 1)
            ->入力_在庫アラート閾値(1, 3)
            // バニラ×16mm×16mm: 在庫は閾値より十分多い
            ->無効_在庫数無制限(7)
            ->入力_個数(7, 100)
            ->入力_在庫アラート閾値(7, 3)
            ->登録();

        $I->waitForElement(ProductClassEditPage::$登録完了メッセージ);
        $I->see('保存しました', ProductClassEditPage::$登録完了メッセージ);

        // 保存した値が画面に反映されていることを確認する(再読み込みしても残っている = DBに保存されている)
        $this->商品規格編集画面を開く($I);

        $I->seeInField(['id' => 'product_class_matrix_product_classes_0_stock_alert_threshold'], '3');
        $I->seeInField(['id' => 'product_class_matrix_product_classes_6_stock_alert_threshold'], '3');
    }

    public function 在庫アラート閾値に負の値は保存できない(AcceptanceTester $I)
    {
        $I->wantTo('在庫アラート閾値に負の値を入力すると保存されない');

        // まず正常な値(3)を保存しておく. この値が上書きされないことで, 保存されていないことを確認する.
        $this->商品規格編集画面を開く($I)
            ->無効_在庫数無制限(1)
            ->入力_個数(1, 1)
            ->入力_在庫アラート閾値(1, 3)
            ->登録();
        $I->waitForElement(ProductClassEditPage::$登録完了メッセージ);

        // ブラウザ側の入力制限を解除したうえで負の値を送信する.
        // min属性による制限は開発者ツールで回避できるため, サーバー側で弾く必要がある.
        $this->商品規格編集画面を開く($I)
            ->クライアント側入力制限を解除()
            ->入力_在庫アラート閾値(1, -1);

        // 送信がサーバーまで届いたことを確かめるための目印を付けておく.
        // ブラウザの入力検証で送信がブロックされた場合はページが再読み込みされず, 目印が残ったままになる.
        $I->executeJS("document.body.setAttribute('data-e2e-submitted', '1');");

        ProductClassEditPage::at($I)->登録();

        // ページが再読み込みされた = 不正な値がサーバーまで送信された
        $I->waitForJS("return !document.body.hasAttribute('data-e2e-submitted');", 10);

        // 保存完了にならないこと
        $I->waitForElement(['id' => 'product_class_matrix_product_classes_0_stock_alert_threshold']);
        $I->dontSee('保存しました', ProductClassEditPage::$登録完了メッセージ);

        // 画面を開き直しても負の値は保存されておらず, 元の値のままであること
        $this->商品規格編集画面を開く($I);
        $I->seeInField(['id' => 'product_class_matrix_product_classes_0_stock_alert_threshold'], '3');
    }

    public function 商品一覧を在庫僅少のみで絞り込める(AcceptanceTester $I)
    {
        $I->wantTo('商品一覧画面で「在庫僅少のみ」にチェックすると、閾値以下の在庫を持つ商品だけが表示される');

        // 前のテストで「チョコ×16mm×16mm」の在庫(1) <= 閾値(3) になっている状態を前提にする。
        // 万一単体で実行された場合に備え、ここでも同じ設定を行っておく。
        $this->商品規格編集画面を開く($I)
            ->無効_在庫数無制限(1)
            ->入力_個数(1, 1)
            ->入力_在庫アラート閾値(1, 3)
            ->登録();
        $I->waitForElement(ProductClassEditPage::$登録完了メッセージ);

        ProductManagePage::go($I)
            ->詳細検索_在庫僅少のみ();

        $I->see(self::商品名, ProductManagePage::$検索結果_一覧);
    }
}
