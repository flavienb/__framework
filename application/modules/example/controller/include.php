<?php

class example_controller_include extends __controller
{
    /**
     * @var example_helper_myHelper
     */
    protected $myHelper;

    /**
     * Shows you how to use an external file
     * Please follow the class naming convention to get the benefit of autoloading
     */
    public function init()
    {
        $this->myHelper = new example_helper_myHelper();
    }

    /**
     * Using the included helper
     * @return array
     */
    public function sumAction() {
        $sum = $this->myHelper->sum(2, 3);

        return ['sum' => $sum];
    }

}
