<?php

namespace Tests\Traits;

trait TestInProd
{
    protected function skipTestsIfNotInProd($message = '')
    {
        if (!$this->isInProdMode()) {
            $this->markTestSkipped($message);
        }
    }

    private function isInProdMode()
    {
        return env('TESTING_PROD') !== false;
    }

}
