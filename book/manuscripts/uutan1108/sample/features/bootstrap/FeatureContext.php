<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Uutan1108\BddSample\RegisterSystem;

class FeatureContext implements Context
{
    private RegisterSystem $register;

    /**
     * @Given レジシステムを起動する
     */
    public function startRegisterSystem(): void
    {
        $this->register = new RegisterSystem();
    }

    /**
     * @When :item を :quantity 個 スキャンする
     */
    public function scanItem(string $item, int $quantity): void
    {
        $this->register->addItem($item, $quantity);
    }

    /**
     * @Then 合計金額は :amount 円 であること
     */
    public function assertTotalAmount(int $amount): void
    {
        $actualTotal = $this->register->getTotal();
        Assert::assertEquals($amount, $actualTotal, "合計金額が期待値と一致しません");
    }
}
