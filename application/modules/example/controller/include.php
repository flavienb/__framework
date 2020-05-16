<?php

class example_controller_include extends __controller
{
    /**
     * @var example_helper_myHelper
     */
    protected $myHelper;

    public function init()
    {
        $this->myHelper = new example_helper_myHelper();
    }

    public function sumAction() {
        $sum = $this->myHelper->sum(2, 3);

        return ['sum' => $sum];
    }

}
